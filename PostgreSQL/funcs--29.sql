-- Stolen from:
-- http://stackoverflow.com/questions/10318014/javascript-encodeuri-like-function-in-postgresql
--
-- ..extended to accept and not encode '=' to better match with the
-- extension's C variant.
--
-- TODO: replace this with the url_encode extension!
CREATE OR REPLACE FUNCTION uri_encode(in_str text, OUT _result text)
    STRICT IMMUTABLE AS $uri_encode$
DECLARE
    _i      int4;
    _temp   varchar;
    _ascii  int4;
BEGIN
    _result = '';
    FOR _i IN 1 .. length(in_str) LOOP
        _temp := substr(in_str, _i, 1);
        IF _temp ~ '[0-9a-zA-Z:/@._?#-=]+' THEN
            _result := _result || _temp;
        ELSE
            _ascii := ascii(_temp);
            IF _ascii > x'07ff'::int4 THEN
                RAISE EXCEPTION 'Won''t deal with 3 (or more) byte sequences.';
            END IF;
            IF _ascii <= x'07f'::int4 THEN
                _temp := '%'||to_hex(_ascii);
            ELSE
                _temp := '%'||to_hex((_ascii & x'03f'::int4)+x'80'::int4);
                _ascii := _ascii >> 6;
                _temp := '%'||to_hex((_ascii & x'01f'::int4)+x'c0'::int4)
                            ||_temp;
            END IF;
            _result := _result || upper(_temp);
        END IF;
    END LOOP;
    RETURN ;
END;
$uri_encode$ LANGUAGE plpgsql;


-- Interestingly, there's no binary xor operator by default.
CREATE OR REPLACE FUNCTION xor(a BOOL, b BOOL)
    RETURNS BOOL
    RETURNS NULL ON NULL INPUT
    IMMUTABLE
    AS $xor$
SELECT (a AND NOT b) OR (NOT a AND b);
$xor$ LANGUAGE sql;


DROP VIEW IF EXISTS spider_job_alert_type_cycle;
DROP VIEW IF EXISTS alert_latest_notification;
DROP VIEW IF EXISTS url_group_children;

CREATE VIEW url_group_children AS
  SELECT c.url_group_id, (
    WITH RECURSIVE t(id) AS (
        SELECT c.url_group_id
      UNION ALL
        SELECT g.url_group_id AS id FROM t
          LEFT JOIN url_group g ON t.id = g.parent_url_group_id
          WHERE g.parent_url_group_id IS NOT NULL
    )
    SELECT array_agg(t.id) FROM t
  )::int[] AS child_ids
  FROM url_group c;

CREATE VIEW alert_latest_notification AS
  SELECT
    n.alert_id,
    cu.url_id,
    n.type_x_cycle_id,
    c.new_doc_id AS latest_doc_id,
    c.delta AS latest_delta,
    c.ts AS latest_notification_ts
  FROM (
      SELECT
        n1.alert_id,
        n1.type_x_cycle_id,
        ( SELECT c2.change_id
          FROM notification n2
          INNER JOIN change c1 ON n1.change_id = c1.change_id
          LEFT JOIN change_x_url cu1 ON cu1.change_id = c1.change_id
          INNER JOIN change c2 ON n2.change_id = c2.change_id
          LEFT JOIN change_x_url cu2 ON cu2.change_id = c2.change_id
          WHERE n2.alert_id = n1.alert_id
            AND cu1.url_id = cu2.url_id
            AND n1.type_x_cycle_id = n2.type_x_cycle_id
            -- Disregard retained notifications
            AND NOT is_retained
            -- This ORDER BY clause exactly matches the index
            -- notification_creation_ts_idx and allows quick (reverse)
            -- index scanning to retrieve the max(creation_ts)
          ORDER BY n2.alert_id DESC, cu2.url_id DESC,
                   n2.type_x_cycle_id DESC, c2.ts DESC
          LIMIT 1
        ) AS latest_change_id
        FROM notification n1
        WHERE NOT n1.is_retained
     ) AS a
  LEFT JOIN notification n
    ON n.alert_id = a.alert_id
      AND n.change_id = a.latest_change_id
      AND n.type_x_cycle_id = a.type_x_cycle_id
  LEFT JOIN change c
    ON c.change_id = n.change_id
  LEFT JOIN change_x_url cu
    ON c.change_id = cu.change_id;

CREATE VIEW spider_job_alert_type_cycle AS
  SELECT j.job_id, url.url_id, url.url_active,
         axu.alert_id, alert.alert_active,
         alert.alert_threshold,
         xtc.type_x_cycle_id,
         alert_group.url_group_id AS via_group_id,
         xfrm.xfrm_id, xfrm.xfrm_commands, xfrm.xfrm_args,
         cf.check_frequency_interval
    FROM url
    LEFT JOIN spider_job j ON j.job_id = url.spider_job_id
    INNER JOIN xfrm ON xfrm.xfrm_id = url.xfrm_id
    INNER JOIN check_frequency cf USING(check_frequency_id)
    INNER JOIN url_group ON url_group.url_group_id = url.url_group_id
    INNER JOIN url_group_children alert_group
            ON url_group.url_group_id = ANY(alert_group.child_ids)
    INNER JOIN alert_x_url_group axu
            ON axu.url_group_id = alert_group.url_group_id
    INNER JOIN alert_x_type_cycle xtc ON xtc.alert_id = axu.alert_id
    INNER JOIN alert ON axu.alert_id = alert.alert_id;



-- Called from the spider to register and periodically as a
-- heartbeat. If the passed UUID is NULL, a new UUID is assigned and
-- returned. Otherwise, an existing row is updated and NULL gets
-- returned.
DROP FUNCTION IF EXISTS spider_heartbeat(UUID);
DROP FUNCTION IF EXISTS spider_heartbeat(UUID, TEXT, FLOAT, FLOAT, FLOAT,
     FLOAT, FLOAT, FLOAT, FLOAT, FLOAT, FLOAT,
     BIGINT, BIGINT, BIGINT, BIGINT);
CREATE OR REPLACE FUNCTION spider_heartbeat(
  in_uuid UUID,
  in_fqdn TEXT,
  in_load_one FLOAT,
  in_load_five FLOAT,
  in_load_fifteen FLOAT,
  in_cpu_user_time FLOAT,
  in_cpu_nice_time FLOAT,
  in_cpu_system_time FLOAT,
  in_cpu_idle_time FLOAT,
  in_cpu_iowait_time FLOAT,
  in_cpu_irq_time FLOAT,
  in_bytes_sent BIGINT,
  in_bytes_recv BIGINT,
  in_packets_sent BIGINT,
  in_packets_recv BIGINT
)
RETURNS uuid
AS $$
DECLARE
  out_uuid UUID;
BEGIN
  -- Fast and fancy UPSERT variant, no lost updates.
  WITH
    new_values (uuid, ts, fqdn) AS (
      VALUES (
        coalesce(in_uuid, thirdparty.gen_random_uuid()),
        now(),
        in_fqdn
      )
    ),
    upsert AS (
      UPDATE spider s SET
        spider_uuid = nv.uuid,
        spider_last_seen = nv.ts,
        spider_last_hostname = nv.fqdn
      FROM new_values nv
      WHERE s.spider_uuid = nv.uuid
      RETURNING s.*
    )
  INSERT INTO spider (spider_uuid, spider_last_seen, spider_last_hostname)
  SELECT new_values.*
  FROM new_values
  WHERE NOT EXISTS (SELECT 1 FROM upsert
                    WHERE new_values.uuid = upsert.spider_uuid)
  RETURNING spider_uuid INTO out_uuid;

  INSERT INTO spider_load
    (spider_uuid, load_one, load_five, load_fifteen,
     cpu_user_time, cpu_nice_time, cpu_system_time,
     cpu_idle_time, cpu_iowait_time, cpu_irq_time,
     bytes_sent, bytes_recv, packets_sent, packets_recv)
  VALUES (
    COALESCE(in_uuid, out_uuid),
    in_load_one, in_load_five, in_load_fifteen,
    in_cpu_user_time, in_cpu_nice_time, in_cpu_system_time,
    in_cpu_idle_time, in_cpu_iowait_time, in_cpu_irq_time,
    in_bytes_sent, in_bytes_recv, in_packets_sent, in_packets_recv
  );

  RETURN out_uuid;
END
$$ LANGUAGE plpgsql;

DROP FUNCTION IF EXISTS spider_rebalance_jobs();
CREATE FUNCTION spider_rebalance_jobs()
RETURNS void
AS $$
  -- Assign unassigned jobs and reassign those of inactive spiders.
  WITH
    reassigned_job AS (
      UPDATE spider_job SET job_spider_uuid = (
        -- assign to some random not-suspected backend (or NULL, if
        -- none is available)
        SELECT uuid FROM spider_status
        WHERE NOT suspected
          AND job_id = job_id -- A dependency on the outer select, so
                              -- this subselect will be re-evaluated
                              -- for every row to update.
        LIMIT 1
      )
      FROM spider_status AS s
      WHERE (s.inactive AND job_spider_uuid = s.uuid)
         OR job_spider_uuid IS NULL
      RETURNING
        job_id AS job_id,
        job_spider_uuid IS NOT NULL AS is_assigned
    )
  INSERT INTO spider_change (job_id, change_type)
    SELECT job_id, 'update'::change_type_enum
      FROM reassigned_job
      WHERE is_assigned;
$$ LANGUAGE sql;

-- a little helper method to clear (json) nulls and eliminate
-- duplicates from json arrays
CREATE OR REPLACE FUNCTION json_uniq(in_data json)
  RETURNS json
LANGUAGE SQL IMMUTABLE
AS $$
SELECT COALESCE(json_agg(x.value), '[]'::json)
FROM (
  SELECT DISTINCT value::jsonb
  FROM json_array_elements(in_data)
  WHERE value::TEXT <> 'null'
) AS x;
$$;

-- Generates a simple array from an array of arrays, i.e. strips one
-- level of nesting.
CREATE OR REPLACE FUNCTION json_array_collapse(in_data json)
  RETURNS json
  RETURNS NULL ON NULL INPUT
  LANGUAGE SQL IMMUTABLE
AS $$
SELECT json_agg(unnested2.value)
FROM (
  SELECT json_array_elements(
      CASE json_typeof(unnested.value) WHEN 'null' THEN '[]'::JSON
                                       ELSE unnested.value END
    ) AS value
    FROM (
      SELECT json_array_elements(
        CASE json_typeof(in_data) WHEN 'null' THEN '[]'::JSON
                                  ELSE in_data END
      ) AS value
    ) AS unnested
  ) AS unnested2;
$$;

DROP FUNCTION IF EXISTS spider_update_job_meta(INT, TEXT, TEXT, BYTEA,
                                               TIMESTAMP WITH TIME ZONE);
DROP FUNCTION IF EXISTS spider_update_job_meta(INT, TEXT, TEXT, BYTEA);

-- For a given job and assuming the downloaded resource changed, this
-- functions returns the transformation commands that need to be
-- applied and some per-check-interval information about the latest
-- document.
CREATE FUNCTION spider_update_job_meta(in_job_id INT, in_last_modification TEXT,
                                       in_entity_tag TEXT, in_hash BYTEA)
RETURNS TABLE (
  xfrm_id INT,
  commands TEXT,
  args jsonb,
  latest_doc_id BIGINT,
  latest_doc_contents_hash BYTEA
)
AS $$
DECLARE
  old_hash BYTEA;
BEGIN
  SELECT last_hash INTO old_hash
  FROM spider_job WHERE job_id = in_job_id
  FOR UPDATE;

  UPDATE spider_job
  SET last_check_ts = now(),
      last_modification = in_last_modification,
      last_hash = in_hash,
      last_entity_tag = in_entity_tag
  WHERE job_id = in_job_id;

  RETURN QUERY
    SELECT
      xfrm.xfrm_id,
      xfrm.xfrm_commands,
      xfrm.xfrm_args,
      doc.spider_document_id AS latest_doc_id,
      doc.contents_hash AS latest_doc_contents_hash
    FROM spider_job j
    INNER JOIN url ON url.spider_job_id = j.job_id
    INNER JOIN xfrm ON xfrm.xfrm_id = url.xfrm_id
    -- join the latest document per job and xfrm_id
    LEFT JOIN spider_document_meta doc
      ON doc.job_id = j.job_id
      AND doc.xfrm_id = url.xfrm_id
    WHERE
      j.job_id = in_job_id
      AND url.url_active
      AND j.job_active
   GROUP BY
     xfrm.xfrm_id, xfrm_commands, xfrm_args,
     doc.spider_document_id,
     doc.contents_hash;
END
$$ LANGUAGE plpgsql;


DROP FUNCTION IF EXISTS spider_store_document(INT, TEXT, TEXT, BYTEA);
DROP FUNCTION IF EXISTS spider_store_document(INT, BYTEA);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, UUID, BYTEA, json);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, UUID, BYTEA, INT[]);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, UUID, BYTEA,
                                              TIMESTAMP WITH TIME ZONE, INT[]);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, INT, UUID, BYTEA, BYTEA,
                                              TIMESTAMP WITH TIME ZONE, INT[]);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, INT, UUID,
                                              BYTEA, BYTEA,
                                              TIMESTAMP WITH TIME ZONE,
                                              JSON);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, BIGINT, UUID,
                                              BYTEA, BYTEA,
                                              TIMESTAMP WITH TIME ZONE,
                                              JSON);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, BIGINT, UUID,
                                              BYTEA, BYTEA,
                                              TIMESTAMP WITH TIME ZONE);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, UUID,
                                              BYTEA, BYTEA,
                                              TIMESTAMP WITH TIME ZONE);
CREATE FUNCTION spider_store_document(
  in_job_id INT, in_xfrm_id INT, in_old_doc_id BIGINT, in_spider_uuid UUID,
  in_contents BYTEA, in_contents_hash BYTEA,
  in_ts TIMESTAMP WITH TIME ZONE)
RETURNS TABLE (
  spider_document_id BIGINT
)
AS $$
DECLARE
  inserted_doc_id BIGINT;
  inserted_change_id BIGINT;
BEGIN
  INSERT INTO spider_document (job_id, xfrm_id, contents, contents_hash,
                               reception_ts)
    VALUES (in_job_id, in_xfrm_id, in_contents, in_contents_hash, in_ts)
    RETURNING spider_document.spider_document_id INTO inserted_doc_id;

  IF in_old_doc_id IS NOT NULL THEN
    WITH
      -- Retrieves the url_ids of entries affected by this spider job
      -- as well as the last time a change has been generated per each
      -- required check_frequency_interval.
      per_check_frequency AS (
        SELECT
          cf.check_frequency_id,
          cf.check_frequency_interval,
          u.xfrm_id,
          array_agg(u.url_id) AS affected_url_ids,
          -- there should only ever be one, so max() doesn't change
          -- much.
          max(c.ts) AS latest_change_ts
        FROM url u
        LEFT JOIN check_frequency cf
          ON cf.check_frequency_id = u.check_frequency_id
        LEFT JOIN change c
          ON u.url_latest_change_id = c.change_id
        WHERE u.spider_job_id = in_job_id
          AND u.xfrm_id = in_xfrm_id
          AND u.url_active
        GROUP BY
          cf.check_frequency_id, cf.check_frequency_interval, u.xfrm_id
      )
    -- Then insert a pending change per check_frequency and xfrm_id.
    INSERT INTO pending_change
      (check_frequency_id, url_ids, creation_ts, not_before_ts,
       old_doc_id, new_doc_id)
    SELECT
      check_frequency_id,
      affected_url_ids AS url_ids,
      in_ts AS creation_ts,
      COALESCE(
        latest_change_ts + check_frequency_interval * interval '1 second',
        '01-01-1980T00:00:00'
      ) AS not_before_ts,
      in_old_doc_id, inserted_doc_id
    FROM per_check_frequency;
  END IF;

  RETURN QUERY SELECT inserted_doc_id;
END
$$ LANGUAGE plpgsql;


-- Almost a view... returns the changes that can be materialized by
-- time in_ts, either for all jobs (if in_job_id is null) or just for
-- the given job_id.
DROP FUNCTION IF EXISTS get_pending_changes_for(TIMESTAMP WITH TIME ZONE, INT);
CREATE FUNCTION get_pending_changes_for(
  in_ts TIMESTAMP WITH TIME ZONE,
  in_job_id INT
)
RETURNS TABLE (
  job_id INT,
  check_frequency_id INT,
  xfrm_id INT,
  pending_change_ids JSON,
  provided_ts TIMESTAMP WITH TIME ZONE,
  old_doc_id INT,
  old_doc_contents BYTEA,
  old_doc_contents_hash BYTEA,
  new_doc_id INT,
  new_doc_contents BYTEA,
  new_doc_contents_hash BYTEA,
  agg_delta JSON,
  alert_attrs JSON
)
AS $$
  WITH
    agg_pending_changes AS (
      SELECT
        url.spider_job_id AS job_id,
        url.check_frequency_id,
        url.xfrm_id,
        json_uniq(json_agg(pending_change_id)) AS pending_change_ids,
        min(old_doc_id) AS old_doc_id,
        max(new_doc_id) AS new_doc_id,
        json_array_collapse(json_agg(delta::JSON)) AS agg_delta,
        json_uniq(json_agg(unnested_url_id)) AS all_url_ids,
        max(CASE substr(url.url, 1, 9)
          WHEN 'external:' THEN c.creation_ts
          ELSE NULL
        END) AS provided_ts
      FROM pending_change c
      INNER JOIN unnest(c.url_ids) AS unnested_url_id
        ON True
      INNER JOIN url
        ON unnested_url_id = url.url_id
      -- We're only interested in URLs that actually have at least one
      -- active alert.
      INNER JOIN alert_x_url_group aug
        ON aug.url_group_id = url.url_group_id
      INNER JOIN alert a
        ON aug.alert_id = a.alert_id
      WHERE
        not_before_ts <= in_ts
        AND url.url_active
        AND a.alert_active
        AND (url.spider_job_id = in_job_id OR in_job_id IS NULL)
      GROUP BY
        url.spider_job_id, url.check_frequency_id, url.xfrm_id,
        -- We must not aggregate changes for external URLs, but import
        -- each change separately. Therefore, we add this nifty group
        -- by clause here.
        CASE substr(url, 1, 9)
          WHEN 'external:' THEN pending_change_id
          ELSE NULL
        END
    )
  SELECT
    pc.job_id,
    pc.check_frequency_id,
    pc.xfrm_id,
    pc.pending_change_ids,
    pc.provided_ts,
    pc.old_doc_id,
    old_doc.contents,
    old_doc.contents_hash,
    pc.new_doc_id,
    new_doc.contents,
    new_doc.contents_hash,
    agg_delta,
    (
      -- Lookup all the affected alerts for this change by url.
      SELECT json_object_agg(job_alert.alert_id, ai.per_alert_info)
      FROM json_array_elements(all_url_ids) AS unnested_url_id
      INNER JOIN spider_job_alert_type_cycle AS job_alert
        ON
          job_alert.xfrm_id = pc.xfrm_id
          AND job_alert.url_id = unnested_url_id::TEXT::INT
      INNER JOIN (
          -- This nifty piece of SQL assembles a json object somewhat
          -- like {"threshold": x, "keywords": [...]} per alert_id.
          SELECT a1.alert_id, (SELECT row_to_json(t) AS per_alert_info
            FROM (
              SELECT
                a2.alert_threshold AS threshold,
                json_uniq(json_agg(kw.alert_keyword)) AS keyword_lists
              FROM alert a2
              LEFT JOIN alert_keyword kw
                ON kw.alert_id = a2.alert_id
              WHERE a1.alert_id = a2.alert_id
                AND kw.alert_keyword_active
              GROUP BY a2.alert_id
              LIMIT 1
            ) t)
          FROM alert a1
        ) ai
        ON job_alert.alert_id = ai.alert_id
    ) AS alert_attrs
  FROM agg_pending_changes pc
  LEFT JOIN spider_document new_doc
    ON pc.new_doc_id = new_doc.spider_document_id
  LEFT JOIN spider_document old_doc
    ON pc.old_doc_id = old_doc.spider_document_id;
$$ LANGUAGE sql;

DROP FUNCTION IF EXISTS materialize_changes(JSON, INT, UUID, TIMESTAMP WITH TIME ZONE);
DROP FUNCTION IF EXISTS materialize_change(JSON, JSON, INT, UUID, TIMESTAMP WITH TIME ZONE);
DROP FUNCTION IF EXISTS materialize_change(JSON, JSON, JSON, INT, UUID, TIMESTAMP WITH TIME ZONE);
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
  notifications_created INT;
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
      SELECT DISTINCT url.url_id
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
      INSERT INTO change_x_url (change_id, url_id)
      SELECT ci.change_id, au.url_id
      FROM change_insert AS ci
      JOIN affected_urls AS au ON True
      RETURNING change_x_url.change_id, change_x_url.url_id
    ),
    general_info AS (
      SELECT spider_job_id, xfrm_id, check_frequency_id
      FROM affected_urls AS au
      INNER JOIN url ON au.url_id = url.url_id
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
        RETURNING alert_id, alert_keyword_id
      )
    SELECT x.c_notifications INTO notifications_created
    FROM (SELECT
      (SELECT COUNT(alert_id) FROM notification_insert) AS c_notifications,
      (SELECT COUNT(alert_id) FROM notification_keyword_insert) AS c_kws
    ) AS x;
  END IF;

  DELETE FROM pending_change
  USING json_array_elements(pendingChangeIds) x
  WHERE pending_change_id = x.value::TEXT::INT;

  RETURN new_change_id;
END
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION materialize_changes(
  changes JSON,
  in_user_id INT,
  in_spider_uuid UUID,
  -- a default timestamp to be used if a change itself doesn't provide
  -- one (like with the external sources), usually now(), the time of
  -- aggregation of the changes.
  in_ts TIMESTAMP WITH TIME ZONE
)
RETURNS SETOF INT
AS $$
  SELECT materialize_change(
    value->'pendingChangeIds',
    value->'alertMatches',
    value->'sections',
    in_user_id,
    in_spider_uuid,
    COALESCE((value->>'providedTs')::TIMESTAMP WITH TIME ZONE, in_ts)
  )
  FROM json_array_elements(changes);
$$ LANGUAGE sql;

DROP FUNCTION IF EXISTS url_add(TEXT, TEXT, TEXT, INT, INT, INT);
CREATE OR REPLACE FUNCTION url_add(new_url_title TEXT, new_url TEXT,
  new_lang TEXT, new_user_id INT, new_access_type_id INT,
  new_check_frequency_id INT, xfrm_commands TEXT, xfrm_args JSONB)
RETURNS TEXT
AS $$
DECLARE
  my_url_id INT;
BEGIN
  INSERT INTO url (url_title, url, url_lang, url_creator_user_id,
                   check_frequency_id, xfrm_id)
    VALUES (new_url_title, new_url, new_lang, new_user_id,
            new_check_frequency_id,
            get_xfrm_id(xfrm_commands, xfrm_args))
    RETURNING url_id INTO my_url_id;

  INSERT INTO access_control
    (user_id, url_id, access_type_id, access_control_valid_from)
    VALUES (new_user_id, my_url_id, new_access_type_id, now());

  RETURN my_url_id;
END
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION url_filter(url_in TEXT)
RETURNS TEXT
AS $$
   SELECT uri_encode(regexp_replace(url_in, '#[^?]*$', ''));
$$ LANGUAGE SQL;


DROP FUNCTION IF EXISTS spider_job_update_or_delete(INT, INT, BIGINT);
CREATE OR REPLACE FUNCTION spider_job_update_or_delete(my_job_id INT,
                                                       exclude_url_id INT,
                                                       new_check_freq BIGINT,
                                                       may_delete BOOL)
RETURNS void
AS $$
DECLARE
  my_change_id BIGINT;
  new_minimum BIGINT;
  interval_of_others BIGINT;
  is_active BOOLEAN;
BEGIN
  -- Check if min_check_freq needs an update - this excludes the url
  -- with url_id exclude_url_id, if given, as that row in table 'url'
  -- is about to be updated, so it should not be taken into
  -- account. Instead, take new_check_freq into account, which is
  -- either the new interval to be added (but not yet visible in "url"
  -- as we run in BEFORE triggers), or NULL, if the row in "url" is to
  -- be deleted.
  SELECT min(f.check_frequency_interval) INTO interval_of_others
  FROM url
  LEFT JOIN check_frequency f ON f.check_frequency_id = url.check_frequency_id
  WHERE url.url_id != coalesce(exclude_url_id, -1)
  AND url.spider_job_id = my_job_id AND url.url_active;

  -- Check if the existing job is active.
  SELECT job_active INTO is_active
  FROM spider_job
  WHERE job_id = my_job_id;

  IF interval_of_others IS NULL AND may_delete THEN
    -- deactivate the job
    UPDATE spider_job SET job_active = false
    WHERE job_id = my_job_id;

    -- notify the spiders
    INSERT INTO spider_change (job_id, change_type)
    VALUES (my_job_id, 'delete')
    RETURNING change_id INTO my_change_id;

    PERFORM pg_notify('spider_jobs_channel', my_change_id::TEXT);

  ELSIF NOT is_active THEN
    UPDATE spider_job
    SET min_check_interval = least(interval_of_others, new_check_freq),
        job_active = True
    WHERE job_id = my_job_id;

    INSERT INTO spider_change(job_id, change_type)
    VALUES (my_job_id, 'insert')
    RETURNING change_id INTO my_change_id;

    PERFORM pg_notify('spider_jobs_channel', my_change_id::TEXT);
  ELSE
    UPDATE spider_job
    SET min_check_interval = least(interval_of_others, new_check_freq)
    WHERE job_id = my_job_id
      AND min_check_interval != least(interval_of_others, new_check_freq);

    -- If we had to update the min_check_interval, notify the
    -- spiders. Otherwise, this update was not relevant.
    IF found THEN
      INSERT INTO spider_change (job_id, change_type)
      VALUES (my_job_id, 'update')
      RETURNING change_id INTO my_change_id;

      PERFORM pg_notify('spider_jobs_channel', my_change_id::TEXT);
    END IF;
  END IF;
END
$$ LANGUAGE plpgsql;



-- Creates a job for (url / url_lang) if it doesn't exist,
-- yet. Otherwise we check if min_check_interval needs an update.
DROP FUNCTION spider_job_maybe_add(TEXT, TEXT, BIGINT);
CREATE FUNCTION spider_job_maybe_add(in_url TEXT,
                                     in_url_lang TEXT,
                                     check_interval BIGINT)
RETURNS INT
AS $$
DECLARE
  my_job_id INT;
  my_change_id BIGINT;
BEGIN
  SELECT job_id INTO my_job_id FROM spider_job
    WHERE spider_job.url = url_filter(in_url)
      AND spider_job.url_lang = in_url_lang;

  IF my_job_id IS NULL THEN
    -- create the job if not existent
    INSERT INTO spider_job (url, url_lang, min_check_interval)
      VALUES (url_filter(in_url), in_url_lang, check_interval)
      RETURNING job_id INTO my_job_id;

    -- notify the spiders
    INSERT INTO spider_change (job_id, change_type)
    VALUES (my_job_id, 'insert')
    RETURNING change_id INTO my_change_id;

    PERFORM pg_notify('spider_jobs_channel', my_change_id::TEXT);
  ELSE
    PERFORM spider_job_update_or_delete(my_job_id, NULL,
                                        check_interval, false);
  END IF;

  RETURN my_job_id;
END
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION url_insert_trigger_func()
RETURNS trigger
AS $$
BEGIN
  IF split_part(NEW.url, ':', 1) = 'external' THEN
    -- no-op
  ELSIF NEW.url_active THEN
    SELECT spider_job_maybe_add(NEW.url, NEW.url_lang,
                                f.check_frequency_interval)
    FROM check_frequency f WHERE NEW.check_frequency_id = f.check_frequency_id
    INTO NEW.spider_job_id;
  END IF;

  RETURN NEW;
END
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION url_update_trigger_func()
RETURNS trigger
AS $$
DECLARE
  do_deactivate BOOL;
  old_is_external BOOL;
  new_is_external BOOL;
BEGIN
  old_is_external = split_part(OLD.url, ':', 1) = 'external';
  new_is_external = split_part(NEW.url, ':', 1) = 'external';
  do_deactivate =
    (OLD.url_active AND NOT NEW.url_active) OR
    (NOT old_is_external AND new_is_external);

  IF do_deactivate THEN
    -- url deactivated (same as DELETE)
    PERFORM spider_job_update_or_delete(OLD.spider_job_id, OLD.url_id,
                                        NULL, true);
  ELSIF NOT OLD.url_active AND NEW.url_active THEN
    -- url re-activated (same as INSERT)
    SELECT spider_job_maybe_add(NEW.url, NEW.url_lang,
                                f.check_frequency_interval)
    FROM check_frequency f WHERE NEW.check_frequency_id = f.check_frequency_id
    INTO NEW.spider_job_id;
  ELSIF NEW.url_active AND OLD.url_active AND
        (OLD.url != NEW.url OR OLD.url_lang != NEW.url_lang) THEN
    PERFORM spider_job_update_or_delete(OLD.spider_job_id, OLD.url_id,
                                        NULL, true);

    SELECT spider_job_maybe_add(NEW.url, NEW.url_lang,
                                f.check_frequency_interval)
    FROM check_frequency f WHERE NEW.check_frequency_id = f.check_frequency_id
    INTO NEW.spider_job_id;
  ELSIF OLD.url_id != NEW.url_id THEN
    RAISE EXCEPTION
      'url_update_trigger_func cannot handle primary key updates.';
  ELSIF OLD.check_frequency_id != NEW.check_frequency_id THEN
    -- columns url, url_lang and url_id unchanged
    PERFORM spider_job_update_or_delete(NEW.spider_job_id, NEW.url_id,
                                        f.check_frequency_interval, false)
    FROM check_frequency f WHERE NEW.check_frequency_id = f.check_frequency_id;
  END IF;

  RETURN new;
END
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION url_delete_trigger_func()
RETURNS trigger
AS $$
BEGIN
  IF split_part(OLD.url, ':', 1) = 'external' THEN
    -- no-op
  ELSIF OLD.url_active THEN
    PERFORM spider_job_update_or_delete(OLD.spider_job_id, OLD.url_id,
                                        NULL, true);
  END IF;
  RETURN old;
END
$$ LANGUAGE plpgsql;


DROP FUNCTION IF EXISTS get_xfrm_id(TEXT, JSONB);
CREATE OR REPLACE FUNCTION get_xfrm_id(
  in_commands TEXT,
  in_args JSONB
)
RETURNS INT
AS $$
DECLARE
  new_xfrm_id INT;
BEGIN
  SELECT xfrm_id INTO new_xfrm_id
  FROM xfrm
  WHERE xfrm_commands = in_commands
    AND xfrm_args = in_args;

  IF NOT found THEN
    INSERT INTO xfrm (xfrm_commands, xfrm_args)
    VALUES (in_commands, in_args)
    RETURNING xfrm_id INTO new_xfrm_id;
  END IF;

  RETURN new_xfrm_id;
END
$$ LANGUAGE plpgsql;


DROP FUNCTION IF EXISTS update_keywords_for_alert(INT, INT, TEXT[]);
CREATE OR REPLACE FUNCTION update_keywords_for_alert(
  in_alert_id INT,
  in_user_id INT,
  in_keywords TEXT[]
)
RETURNS void
AS $$
DECLARE
  keywords TEXT[];
BEGIN
  -- Without COALESCE, array_agg returns NULL for empty arrays, which
  -- leads to all comparisons for alert_keyword = ANY(keywords) to
  -- fail. See issue #2374.
  SELECT COALESCE(array_agg(x.lower), '{}'::TEXT[]) INTO keywords FROM (
    SELECT lower(unnest(in_keywords))
  ) AS x;

  -- deactivate keywords that are not in the given array, anymore.
  UPDATE alert_keyword
  SET alert_keyword_active = False
  FROM alert AS a
  WHERE a.alert_id = alert_keyword.alert_id
    AND a.alert_id = in_alert_id
    AND a.user_id = in_user_id
    AND NOT alert_keyword = ANY(keywords)
    AND alert_keyword_active;

  -- re-enable deactivated, but existing keywords
  UPDATE alert_keyword
  SET alert_keyword_active = True
  FROM alert AS a
  WHERE a.alert_id = alert_keyword.alert_id
    AND a.alert_id = in_alert_id
    AND a.user_id = in_user_id
    AND alert_keyword = ANY(keywords)
    AND NOT alert_keyword_active;

  -- add new keywords
  INSERT INTO alert_keyword (alert_id, alert_keyword)
  SELECT a.alert_id, new_keyword
  FROM unnest(keywords) AS new_keyword
  INNER JOIN alert AS a ON a.alert_id = in_alert_id AND a.user_id = in_user_id
  LEFT JOIN alert_keyword kw
    ON kw.alert_id = in_alert_id AND kw.alert_keyword = new_keyword
  WHERE kw.alert_keyword IS NULL;
END
$$ LANGUAGE plpgsql;


DROP FUNCTION IF EXISTS subscribe(INT, INT);
DROP FUNCTION IF EXISTS subscribe(INT, INT, INT);
CREATE OR REPLACE FUNCTION subscribe(groupid INT, userid INT, alert_cycle INT)
RETURNS void
AS $$
DECLARE
  alertid INT;

BEGIN

  INSERT INTO alert(user_id, alert_option_id) VALUES (userid, 1) returning alert_id into alertid;
  INSERT INTO alert_x_url_group(alert_id, url_group_id) VALUES (alertid, groupid);
  INSERT INTO alert_x_type_cycle (type_x_cycle_id, alert_id) VALUES (alert_cycle, alertid);

  INSERT INTO access_control (user_id, url_group_id, access_type_id, access_control_valid_from)
    VALUES (userid, groupid, 1, NOW());

  INSERT INTO access_control (user_id, url_id, access_type_id, access_control_valid_from)
    SELECT userid, url_id, 1, NOW() FROM url WHERE url_group_id = groupid;

  INSERT INTO user_subscription (user_id, url_group_id, user_action) VALUES (userid, groupid, 'subscribe');

END
$$ LANGUAGE plpgsql;


DROP FUNCTION IF EXISTS unsubscribe(INT, INT);
CREATE OR REPLACE FUNCTION unsubscribe(groupid INT, userid INT)
RETURNS void
AS $$
DECLARE
  alertid INT;
BEGIN
  FOR alertid IN SELECT a.alert_id
    FROM alert_x_url_group x JOIN alert a ON (a.alert_id=x.alert_id)
    WHERE url_group_id=groupid AND user_id=userid AND alert_active
     LOOP
     UPDATE alert SET alert_active = false WHERE alert_id = alertid;
     DELETE FROM alert_x_url_group WHERE alert_id = alertid AND url_group_id = groupid;
     DELETE FROM alert_x_type_cycle WHERE alert_id = alertid;
     UPDATE alert_keyword SET alert_keyword_active = false WHERE alert_id = alertid;
    END LOOP;

  DELETE FROM access_control
   WHERE user_id=userid AND url_id IN (SELECT url_id FROM url
    WHERE url_group_id=groupid) AND access_type_id = 1;

  DELETE FROM access_control
   WHERE user_id=userid AND url_group_id = groupid AND access_type_id = 1;

  INSERT INTO user_subscription (user_id, url_group_id, user_action) VALUES (userid, groupid, 'unsubscribe');

END
$$ LANGUAGE plpgsql;


DROP FUNCTION IF EXISTS add_external_change(
  TIMESTAMP WITH TIME ZONE, JSONB, INT, INT);
CREATE OR REPLACE FUNCTION add_external_change(
  in_ts TIMESTAMP WITH TIME ZONE,
  in_delta JSONB,
  in_user_id INT,
  in_url_id INT
) RETURNS INT -- the change_id inserted
AS $$
  INSERT INTO pending_change (check_frequency_id, url_ids, creation_ts, delta)
  VALUES (0, ARRAY[in_url_id], in_ts, in_delta)
  RETURNING pending_change_id;
$$ LANGUAGE sql;




CREATE OR REPLACE FUNCTION delete_changes_for(in_url_id INT)
RETURNS SETOF INT
AS $$
  -- delete notifications
  WITH dx AS (
    SELECT change_id FROM change_x_url WHERE url_id = in_url_id
  )
  DELETE FROM notification
  USING dx WHERE notification.change_id = dx.change_id;

  -- drop references from urls (should better be handled by the Foreign Key)
  WITH dx as (
    SELECT change_id FROM change_x_url WHERE url_id = in_url_id
  )
  UPDATE url
  SET url_latest_change_id = NULL
  FROM dx
  WHERE dx.change_id = url.url_latest_change_id;

  -- delete the change and corresponding change_x_url entries
  WITH dx AS (
    DELETE FROM change_x_url
    WHERE url_id = in_url_id
    RETURNING change_id
  )
  DELETE FROM change
  USING dx
  WHERE change.change_id = dx.change_id
  RETURNING change.change_id;

$$ LANGUAGE sql;
