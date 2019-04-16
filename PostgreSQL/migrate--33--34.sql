DROP VIEW notification_counter;

DROP TABLE notification_counter_internal;

-- internal counter table, possibly featuring multiple entries for
-- parallel updates.
CREATE TABLE IF NOT EXISTS notification_counter_internal (
  user_id INT NOT NULL REFERENCES mon_user(user_id),
  url_group_id INT NOT NULL REFERENCES url_group(url_group_id),
  -- some random uid to make conflicts reasonably improbable, but still 
  -- allowing us to identify individual entries.
  entry_uid INT NOT NULL DEFAULT floor(random() * 2147483647),
  period_start TIMESTAMP WITHOUT TIME ZONE NOT NULL
    DEFAULT trim_to_notification_period(now()),
  count BIGINT NOT NULL DEFAULT 1,
  PRIMARY KEY (user_id, url_group_id, entry_uid)
);

-- view that shoud be used to query statistics
CREATE OR REPLACE VIEW notification_counter AS
  SELECT
    user_id,
    url_group_id,
    period_start,
    SUM(count)::BIGINT AS count
  FROM notification_counter_internal
  GROUP BY user_id, url_group_id, period_start;

-- re-populate stats from existing notifications
INSERT INTO notification_counter_internal
  (user_id, url_group_id, period_start, count)
SELECT
  user_id,
  url_group_id,
  trim_to_notification_period(creation_ts) AS period_start,
  COUNT(change_id) AS count
FROM v_change
GROUP BY
  user_id,
  url_group_id,
  trim_to_notification_period(creation_ts);


DROP FUNCTION IF EXISTS notification_counter_inc(INT, TIMESTAMP WITH TIME ZONE);
CREATE OR REPLACE FUNCTION notification_counter_inc(
  in_user_id INT,
  in_url_group_id INT,
  in_ts TIMESTAMP WITH TIME ZONE
)
RETURNS void AS $$
DECLARE
  start_ts CONSTANT TIMESTAMP WITHOUT TIME ZONE
    := trim_to_notification_period(in_ts);
  locked_uid INT;
BEGIN
  SELECT entry_uid INTO locked_uid
  FROM notification_counter_internal
  WHERE user_id = in_user_id
    AND url_group_id = in_url_group_id
    AND period_start = start_ts
  FOR UPDATE SKIP LOCKED
  LIMIT 1;

  IF found THEN
    UPDATE notification_counter_internal
    SET count = notification_counter_internal.count + 1
    WHERE
      user_id = in_user_id
      AND url_group_id = in_url_group_id
      AND entry_uid = locked_uid
      AND period_start = start_ts;
  ELSE
    INSERT INTO notification_counter_internal
      (url_group_id, period_start)
    VALUES
      (in_url_group_id, start_ts);
  END IF;
END
$$ LANGUAGE plpgsql RETURNS NULL ON NULL INPUT;


CREATE OR REPLACE FUNCTION materialize_change(
  pendingChangeIds JSON,
  alertMatches JSON,
  sections JSON,
  in_user_id INT,
  in_spider_uuid UUID,
  in_ts TIMESTAMP WITH TIME ZONE
)
RETURNS INT
AS $$
DECLARE
  my_job_id INT;
  my_xfrm_id INT;
  my_check_frequency_id INT;
  new_change_id INT;
  ignored_count INT;
BEGIN
  -- Materialize the change, first.
  WITH
    info_rec AS (
      SELECT key::INT AS alert_id,
             value->>'trigger' = 'true' AS trigger_change,
             value->'matches' AS matches,
             value->'match_positions' AS match_positions
      FROM json_each(alertMatches)
    ),
    affected_pending_change AS (
      SELECT pc.*
      FROM json_array_elements(pendingChangeIds) x
      LEFT JOIN pending_change pc
        ON pc.pending_change_id = x.value::TEXT::INT
    ),
    affected_urls AS (
      SELECT DISTINCT url.*
      FROM affected_pending_change pc
      LEFT JOIN url ON url.url_id = ANY(pc.url_ids)
    ),
    agg_changes AS (
      SELECT
        min(old_doc_id) AS old_doc_id,
        max(new_doc_id) AS new_doc_id
      FROM affected_pending_change
    ),
    change_insert AS (
      INSERT INTO change
        (ts, old_doc_id, new_doc_id, delta,
         user_id, spider_uuid)
      SELECT
        in_ts,
        old_doc_id,
        new_doc_id,
        json_build_object(
            'version', 2,
            'sections', sections,
            'match_positions', json_object_agg(alert_id, match_positions)
        )::jsonb AS delta,
        in_user_id,
        in_spider_uuid
      FROM agg_changes
      INNER JOIN info_rec ON True
      -- This eliminates the output row, if no alert matches. In that
      -- case, we're not creating a change at all.
      WHERE info_rec.trigger_change
      GROUP BY old_doc_id, new_doc_id
      RETURNING change.change_id
    ),
    change_url_link_insert AS (
      INSERT INTO change_x_url (change_id, url_id, url_group_id)
      SELECT ci.change_id, au.url_id, au.url_group_id
      FROM change_insert AS ci
      JOIN affected_urls AS au ON True
      RETURNING change_x_url.change_id, change_x_url.url_id
    ),
    general_info AS (
      SELECT spider_job_id, xfrm_id, check_frequency_id
      FROM affected_urls
      GROUP BY spider_job_id, xfrm_id, check_frequency_id
    )
  SELECT
    change_id, spider_job_id, xfrm_id, check_frequency_id
  INTO
    new_change_id, my_job_id, my_xfrm_id, my_check_frequency_id
  -- These should both yield exactly one row.
  FROM change_url_link_insert
  JOIN general_info ON True;

  IF new_change_id IS NOT NULL THEN
    -- Update the url's latest_change_id hint.
    UPDATE url
    SET url_latest_change_id = new_change_id
    FROM (
      SELECT change_id, url_id
      FROM change_x_url
      WHERE change_id = new_change_id
    ) x
    WHERE url.url_id = x.url_id;

    -- Then create notifications including keywords.
    WITH
      info_rec AS (
        SELECT x.key::INT AS alert_id,
               x.value->'matches' AS matches,
               tc.type_x_cycle_id
        FROM json_each(alertMatches) x
        LEFT JOIN alert_x_type_cycle tc
          ON x.key::INT = tc.alert_id
        WHERE value->>'trigger' = 'true'
      ),
      notification_insert AS (
        INSERT INTO notification (alert_id, change_id, type_x_cycle_id,
                                  is_retained)
        SELECT alert_id, new_change_id, type_x_cycle_id, False
        FROM info_rec
        RETURNING alert_id, type_x_cycle_id
      ),
      keyword_match_expansion AS (
        SELECT alert_id, type_x_cycle_id,
          json_array_elements_text(info_rec.matches) AS keyword
        FROM info_rec
      ),
      notification_keyword_insert AS (
        INSERT INTO notification_x_keyword
          (alert_id, change_id, type_x_cycle_id, alert_keyword_id)
        SELECT e.alert_id, new_change_id, e.type_x_cycle_id,
          kw.alert_keyword_id
        FROM keyword_match_expansion e
        LEFT JOIN alert_keyword kw
          ON kw.alert_id = e.alert_id
          AND kw.alert_keyword = lower(e.keyword)
          AND kw.alert_keyword_active
        GROUP BY
          e.alert_id, e.type_x_cycle_id, kw.alert_keyword_id
        RETURNING alert_id, type_x_cycle_id, alert_keyword_id
      ),
      -- joins the two INSERTs to ensure they get executed and collect
      -- all ids of inserted rows, just in case we ever need them below.
      per_notification_inserted AS (
        SELECT
          n.alert_id AS alert_id,
          n.type_x_cycle_id AS type_x_cycle_id,
          array_agg(alert_keyword_id) AS inserted_alert_keyword_ids
        FROM notification_insert AS n
        LEFT JOIN notification_keyword_insert AS i
          ON n.alert_id = i.alert_id
          AND n.type_x_cycle_id = i.type_x_cycle_id
        GROUP BY
          n.alert_id,
          n.type_x_cycle_id
      )
    SELECT COUNT(notification_counter_inc(a.user_id, aug.url_group_id, now()))
      INTO ignored_count
    FROM per_notification_inserted AS i
    INNER JOIN alert AS a
      ON a.alert_id = i.alert_id AND alert_active
    INNER JOIN alert_x_url_group AS aug
      ON i.alert_id = aug.alert_id;
  END IF;

  DELETE FROM pending_change
  USING json_array_elements(pendingChangeIds) x
  WHERE pending_change_id = x.value::TEXT::INT;

  RETURN new_change_id;
END
$$ LANGUAGE plpgsql;

