SET search_path TO public, thirdparty;

-- Creates a job for (url / url_lang) if it doesn't exist,
-- yet. Otherwise we check if min_check_interval needs an update.
DROP FUNCTION IF EXISTS spider_job_maybe_add(TEXT, TEXT, BIGINT);
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

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION public.spider_job_maybe_add(in_url text, in_url_lang text, check_interval bigint)
  OWNER TO project_mon;
