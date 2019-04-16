SET search_path TO public, thirdparty;

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

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION public.spider_job_update_or_delete(my_job_id integer, exclude_url_id integer, new_check_freq bigint, may_delete boolean)
  OWNER TO project_mon;
