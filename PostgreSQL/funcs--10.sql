DROP VIEW IF EXISTS spider_job_alert_type_cycle;
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

CREATE VIEW spider_job_alert_type_cycle AS
  SELECT j.job_id, url.url_id, url.url_active,
         axu.alert_id, alert.alert_active, xtc.type_x_cycle_id,
         alert_group.url_group_id AS via_group_id
    FROM spider_job j
    INNER JOIN url ON url.spider_job_id = j.job_id
    INNER JOIN url_x_group url_group ON url_group.url_id = url.url_id
    INNER JOIN url_group_children alert_group
            ON url_group.url_group_id = ANY(alert_group.child_ids)
    INNER JOIN alert_x_url_group axu ON axu.url_group_id = alert_group.url_group_id
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

DROP FUNCTION IF EXISTS spider_update_job_meta(INT, TEXT, TEXT, BYTEA);
CREATE FUNCTION spider_update_job_meta(in_job_id INT, in_last_modification TEXT,
                                       in_entity_tag TEXT, in_hash BYTEA)
RETURNS TABLE (
  xfrm_id INT,
  commands TEXT,
  args jsonb,
  last_spider_document_id BIGINT,
  last_contents_hash BYTEA,
  alert_keywords jsonb
)
AS $$
DECLARE
  old_hash BYTEA;
BEGIN
  SELECT last_hash INTO old_hash
  FROM spider_job WHERE job_id = in_job_id
  FOR UPDATE;

  UPDATE spider_job
  SET last_modification = in_last_modification,
      last_hash = in_hash,
      last_entity_tag = in_entity_tag
  WHERE job_id = in_job_id;

  RETURN QUERY
    WITH
      x AS (
        SELECT xfrm.xfrm_id, xfrm.xfrm_commands, xfrm.xfrm_args,
               doc.spider_document_id, doc.contents_hash,
               job_alert.alert_id,
               json_uniq(json_agg(kw.alert_keyword)) AS keywords
        FROM spider_job_alert_type_cycle job_alert
        LEFT JOIN url ON job_alert.job_id = url.spider_job_id
        LEFT JOIN xfrm ON url.xfrm_id = xfrm.xfrm_id
        LEFT JOIN spider_document_meta doc
          ON doc.job_id = job_alert.job_id AND doc.xfrm_id = xfrm.xfrm_id
        LEFT JOIN alert_keyword AS kw
          ON (kw.alert_id = job_alert.alert_id AND kw.alert_keyword_active)
        WHERE job_alert.job_id = in_job_id
          AND job_alert.url_active
          AND job_alert.alert_active
        GROUP BY xfrm.xfrm_id, xfrm_commands, xfrm_args,
                 doc.spider_document_id, doc.contents_hash,
                 job_alert.alert_id
      )
    SELECT
      x.xfrm_id, x.xfrm_commands, x.xfrm_args,
      x.spider_document_id, x.contents_hash,
      -- First fetch of a resource, we cannot possibly trigger any
      -- notification, as we have no document to compare
      -- against. However, we need to create one.
      CASE WHEN old_hash IS NULL
        THEN '{}'::jsonb
        ELSE json_object_agg(x.alert_id, x.keywords)::jsonb
      END AS alert_ids
    FROM x
    GROUP BY x.xfrm_id, x.xfrm_commands, x.xfrm_args,
             x.spider_document_id, x.contents_hash;
END
$$ LANGUAGE plpgsql;


DROP FUNCTION IF EXISTS spider_store_document(INT, TEXT, TEXT, BYTEA);
DROP FUNCTION IF EXISTS spider_store_document(INT, BYTEA);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, UUID, BYTEA, json);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, UUID, BYTEA, INT[]);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, UUID, BYTEA, TIMESTAMP WITH TIME ZONE, INT[]);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, INT, UUID, BYTEA, BYTEA, TIMESTAMP WITH TIME ZONE, INT[]);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, INT, UUID, BYTEA, BYTEA, TIMESTAMP WITH TIME ZONE, JSON);
CREATE FUNCTION spider_store_document(in_job_id INT, in_xfrm_id INT, in_old_doc_id INT, in_spider_uuid UUID,
                                      in_contents BYTEA, in_contents_hash BYTEA,
                                      in_ts TIMESTAMP WITH TIME ZONE,
									  alert_matches JSON)
RETURNS TABLE (
  spider_document_id BIGINT,
  notifications_created INT
)
AS $$
DECLARE
  inserted_doc_id BIGINT;
  notifications_created INT;
BEGIN
  INSERT INTO spider_document (job_id, xfrm_id, contents, contents_hash,
                               reception_ts)
    VALUES (in_job_id, in_xfrm_id, in_contents, in_contents_hash, in_ts)
    RETURNING spider_document.spider_document_id INTO inserted_doc_id;

  IF in_old_doc_id IS NOT NULL THEN
    WITH
      info_rec AS (
        SELECT key::INT AS alert_id,
		       (value->'trigger')::TEXT = 'true' AS trigger,
			   value->'matches' AS matches
        FROM json_each(alert_matches)
      ),
      -- join with the view
      toinsert AS (
        SELECT info_rec.alert_id, job_alert.url_id, job_alert.type_x_cycle_id
        FROM info_rec
        INNER JOIN spider_job_alert_type_cycle AS job_alert
                ON job_alert.job_id = in_job_id AND job_alert.alert_id = info_rec.alert_id
        WHERE info_rec.trigger
      ),
      notification_insert AS (
        INSERT INTO notification (alert_id, url_id, type_x_cycle_id,
                                  old_doc_id, new_doc_id, spider_uuid, creation_ts)
        SELECT i.alert_id, i.url_id, i.type_x_cycle_id,
               in_old_doc_id, inserted_doc_id, in_spider_uuid, in_ts
        FROM toinsert i
        RETURNING alert_id
      )
    SELECT COUNT(alert_id) INTO notifications_created FROM notification_insert;

    WITH
      info_rec AS (
        SELECT key::INT AS alert_id,
		       (value->'trigger')::TEXT = 'true' AS trigger,
			   json_array_elements_text(value->'matches') AS keyword
        FROM json_each(alert_matches)
      ),
      toinsert AS (
        SELECT info_rec.*, k.alert_keyword_id
        FROM info_rec
        INNER JOIN alert_keyword k
          ON k.alert_id = info_rec.alert_id
          AND k.alert_keyword = lower(info_rec.keyword)
        WHERE info_rec.trigger
      )
    INSERT INTO notification_x_keyword (alert_id, doc_id, alert_keyword_id)
      SELECT alert_id, inserted_doc_id, alert_keyword_id
      FROM toinsert;
  ELSE
    notifications_created := 0;
  END IF;

  RETURN QUERY SELECT inserted_doc_id, notifications_created;
END
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION url_add(new_url_title TEXT, new_url TEXT, new_lang TEXT,
  new_user_id INT, new_access_type_id INT, new_check_frequency_id INT)
RETURNS TEXT
AS $$
DECLARE
  my_url_id INT;
BEGIN
  INSERT INTO url (url_title, url, url_lang, url_creator_user_id, check_frequency_id)
    VALUES (new_url_title, new_url, new_lang, new_user_id, new_check_frequency_id)
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
   SELECT regexp_replace(url_in, '#[^?]*$', '');
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

  IF interval_of_others IS NULL AND may_delete THEN
    -- deactivate the job
    UPDATE spider_job SET job_active = false
    WHERE job_id = my_job_id;

    -- notify the spiders
    INSERT INTO spider_change (job_id, change_type)
    VALUES (my_job_id, 'delete')
    RETURNING change_id INTO my_change_id;

    PERFORM pg_notify('spider_jobs_channel', my_change_id::TEXT);
  ELSE
    UPDATE spider_job SET min_check_interval = least(interval_of_others, new_check_freq)
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



-- Creates a job for (filtered_url / url_lang) if it doesn't exist,
-- yet. Otherwise we check if min_check_interval needs an update.
CREATE OR REPLACE FUNCTION spider_job_maybe_add(filtered_url TEXT,
                                                my_url_lang TEXT,
                                                check_interval BIGINT)
RETURNS INT
AS $$
DECLARE
  my_job_id INT;
  my_change_id BIGINT;
BEGIN
  SELECT job_id INTO my_job_id FROM spider_job
    WHERE spider_job.url = filtered_url
      AND spider_job.url_lang = my_url_lang;

  IF my_job_id IS NULL THEN
    -- create the job if not existent
    INSERT INTO spider_job (url, url_lang, min_check_interval)
      VALUES (filtered_url, my_url_lang, check_interval)
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
  IF NEW.url_active THEN
    SELECT spider_job_maybe_add(url_filter(NEW.url), NEW.url_lang,
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
BEGIN
  IF OLD.url_active AND NOT NEW.url_active THEN
    -- url deactivated (same as DELETE)
    PERFORM spider_job_update_or_delete(OLD.spider_job_id, OLD.url_id,
                                        NULL, true);
  ELSIF NOT OLD.url_active AND NEW.url_active THEN
    -- url re-activated (same as INSERT)
    SELECT spider_job_maybe_add(url_filter(NEW.url), NEW.url_lang,
                                f.check_frequency_interval)
    FROM check_frequency f WHERE NEW.check_frequency_id = f.check_frequency_id
    INTO NEW.spider_job_id;
  ELSIF NEW.url_active AND OLD.url_active AND
        (OLD.url != NEW.url OR OLD.url_lang != NEW.url_lang) THEN
    PERFORM spider_job_update_or_delete(OLD.spider_job_id, OLD.url_id,
										NULL, true);

    SELECT spider_job_maybe_add(url_filter(NEW.url), NEW.url_lang,
                                f.check_frequency_interval)
    FROM check_frequency f WHERE NEW.check_frequency_id = f.check_frequency_id
    INTO NEW.spider_job_id;
  ELSIF OLD.url_id != NEW.url_id THEN
    RAISE EXCEPTION
      'url_update_trigger_func cannot handle updates to the primary key column.';
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
  IF OLD.url_active THEN
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
  SELECT array_agg(x.lower) INTO keywords FROM (
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
  LEFT JOIN alert_keyword kw ON kw.alert_keyword = new_keyword
  WHERE kw.alert_keyword IS NULL;
END
$$ LANGUAGE plpgsql;
