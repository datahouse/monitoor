CREATE OR REPLACE FUNCTION trim_to_notification_period(
  in_ts TIMESTAMP WITH TIME ZONE
) RETURNS TIMESTAMP WITHOUT TIME ZONE AS
$$
  SELECT date_trunc('hour', in_ts AT TIME ZONE 'UTC');
$$ LANGUAGE SQL IMMUTABLE;

-- internal counter table, possibly featuring multiple entries for
-- parallel updates.
CREATE TABLE IF NOT EXISTS notification_counter_internal (
  url_group_id INT NOT NULL REFERENCES url_group(url_group_id),
  -- some random uid to make conflicts reasonably improbable, but still 
  -- allowing us to identify individual entries.
  entry_uid INT NOT NULL DEFAULT floor(random() * 2147483647),
  period_start TIMESTAMP WITHOUT TIME ZONE NOT NULL
    DEFAULT trim_to_notification_period(now()),
  count BIGINT NOT NULL DEFAULT 1,
  PRIMARY KEY (url_group_id, entry_uid)
);

-- view that shoud be used to query statistics
CREATE OR REPLACE VIEW notification_counter AS
  SELECT
    url_group_id,
    period_start,
    SUM(count)::BIGINT AS count
  FROM notification_counter_internal
  GROUP BY url_group_id, period_start;

-- pre-populate stats from existing notifications
INSERT INTO notification_counter_internal
  (url_group_id, period_start, count)
SELECT
  url_group_id,
  trim_to_notification_period(creation_ts) AS period_start,
  COUNT(change_id) AS count
FROM v_change
GROUP BY
  url_group_id,
  trim_to_notification_period(creation_ts);


-- As we cannot easily get rid of the table, at least drop the unnecessary
-- id in alert_x_url_group.
ALTER INDEX alert_x_url_group_pkey
  RENAME TO alert_x_url_group_deprecated_pkey;

CREATE UNIQUE INDEX alert_x_url_group_temp_idx
  ON alert_x_url_group (url_group_id, alert_id);

ALTER TABLE alert_x_url_group
  DROP CONSTRAINT alert_x_url_group_deprecated_pkey,
  DROP CONSTRAINT alert_x_url_group_alert_id_url_group_id_key,
  ADD CONSTRAINT alert_x_url_group_pkey
    PRIMARY KEY USING INDEX alert_x_url_group_temp_idx,
  DROP COLUMN alert_x_url_group_id;

DROP SEQUENCE IF EXISTS alert_x_url_group_alert_x_url_group_id_seq;






-- ******************************************************
-- Next attempt at speeding up fetching of changes
-- ******************************************************


ALTER TABLE change_x_url
  ADD COLUMN url_group_id INT,
  ADD COLUMN ts TIMESTAMP WITH TIME ZONE;

UPDATE change_x_url SET
  url_group_id = (
    SELECT url_group_id
    FROM url
    WHERE url.url_id = change_x_url.url_id
  ),
  ts = (
    SELECT ts
    FROM change
    WHERE change.change_id = change_x_url.change_id
  );

-- Note that change_x_url.url_group_id cannot be set to NOT NULL, because
-- there are entries with NULLs is case the url_group has been deleted.

ALTER TABLE change_x_url
  ALTER COLUMN ts SET DEFAULT now(),
  ALTER COLUMN ts SET NOT NULL;


CREATE INDEX change_by_url_group_and_ts_idx
  ON change_x_url (url_group_id, ts);
CREATE INDEX change_x_url_ts_idx
  ON change_x_url (ts);

-- Yet another attempt at a faster v_change.
CREATE OR REPLACE VIEW v_change AS
  SELECT
    n.alert_id,
    n.type_x_cycle_id,
    c.change_id,
    c.new_doc_id,
    c.old_doc_id,
    c.delta,
    cu.ts AS creation_ts,  -- using indexable ts
    u.url_id,
    u.url,
    u.url_title,
    g.url_group_id,
    g.url_group_title,
    a.user_id
  FROM notification n,
    change c,
    change_x_url cu,
    alert a,
    url u,
    url_group g
  WHERE
    -- join conditions
    c.change_id = cu.change_id
    AND n.change_id = c.change_id
    AND a.alert_id = n.alert_id
    AND u.url_id = cu.url_id
    AND cu.url_group_id = g.url_group_id

    -- original where clause
    AND a.alert_active = true
    AND n.is_retained = false
  ;

