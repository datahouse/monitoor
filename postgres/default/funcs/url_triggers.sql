SET search_path TO public, thirdparty;

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

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION public.url_insert_trigger_func()
  OWNER TO project_mon;
ALTER FUNCTION public.url_update_trigger_func()
  OWNER TO project_mon;
ALTER FUNCTION public.url_delete_trigger_func()
  OWNER TO project_mon;

