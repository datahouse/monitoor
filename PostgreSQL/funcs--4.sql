-- Called from the spider to register and periodically as a
-- heartbeat. If the passed UUID is NULL, a new UUID is assigned and
-- returned. Otherwise, an existing row is updated and NULL gets
-- returned.
CREATE OR REPLACE FUNCTION spider_heartbeat(UUID)
RETURNS uuid
AS $$
  -- Fast and fancy UPSERT variant, no lost updates.
  WITH
    new_values (spider_uuid, spider_last_seen) AS (
      VALUES (coalesce($1, thirdparty.gen_random_uuid()), now())
    ),
    upsert AS (
      UPDATE spider s SET
        spider_uuid = nv.spider_uuid,
        spider_last_seen = nv.spider_last_seen
      FROM new_values nv
      WHERE s.spider_uuid = nv.spider_uuid
      RETURNING s.*
    )
  INSERT INTO spider (spider_uuid, spider_last_seen)
  SELECT new_values.spider_uuid, spider_last_seen
  FROM new_values
  WHERE NOT EXISTS (SELECT 1 FROM upsert
                    WHERE new_values.spider_uuid = upsert.spider_uuid)
  RETURNING spider_uuid;
$$ LANGUAGE SQL;


DROP FUNCTION IF EXISTS spider_store_document(INT, TEXT, TEXT, BYTEA);
CREATE FUNCTION spider_store_document(new_job_id INT, new_last_mod TEXT,
	                                  new_entity_tag TEXT, new_contents BYTEA)
RETURNS BIGINT
AS $$
DECLARE
  doc_id BIGINT;
BEGIN
  INSERT INTO spider_document (job_id, last_mod, entity_tag, contents)
    VALUES (new_job_id, new_last_mod, new_entity_tag, new_contents)
    RETURNING spider_document_id INTO doc_id;
  RETURN doc_id;
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
  AND url.spider_job_id = my_job_id;

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
    WHERE job_id = my_job_id AND min_check_interval != least(interval_of_others, new_check_freq);

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
CREATE OR REPLACE FUNCTION spider_job_maybe_add(filtered_url TEXT, my_url_lang TEXT,
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
    PERFORM spider_job_update_or_delete(my_job_id, NULL, check_interval, false);
  END IF;

  RETURN my_job_id;
END
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION url_insert_trigger_func()
RETURNS trigger
AS $$
BEGIN
  SELECT spider_job_maybe_add(url_filter(NEW.url), NEW.url_lang,
                              f.check_frequency_interval)
  FROM check_frequency f WHERE NEW.check_frequency_id = f.check_frequency_id
  INTO NEW.spider_job_id;

  RETURN NEW;
END
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION url_update_trigger_func()
RETURNS trigger
AS $$
BEGIN
  IF OLD.url != NEW.url OR OLD.url_lang != NEW.url_lang THEN
    PERFORM spider_job_update_or_delete(OLD.spider_job_id, OLD.url_id, NULL, true);

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
  PERFORM spider_job_update_or_delete(OLD.spider_job_id, OLD.url_id, NULL, true);
  RETURN old;
END
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS url_insert_trigger ON url;
CREATE TRIGGER url_insert_trigger BEFORE INSERT ON url
  FOR EACH ROW EXECUTE PROCEDURE url_insert_trigger_func();

DROP TRIGGER IF EXISTS url_update_trigger ON url;
CREATE TRIGGER url_update_trigger BEFORE UPDATE ON url
  FOR EACH ROW EXECUTE PROCEDURE url_update_trigger_func();

DROP TRIGGER IF EXISTS url_delete_trigger ON url;
CREATE TRIGGER url_delete_trigger BEFORE DELETE ON url
  FOR EACH ROW EXECUTE PROCEDURE url_delete_trigger_func();
