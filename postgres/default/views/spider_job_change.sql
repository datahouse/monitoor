SET search_path TO public, thirdparty;

CREATE OR REPLACE VIEW spider_job_change AS
  SELECT j.job_id, j.url, j.url_lang, j.min_check_interval,
         job_spider_uuid, job_active,
         extract('epoch' FROM now() - j.last_check_ts) AS age,
         c.change_id, c.change_type FROM spider_job j
  JOIN spider_change c ON c.job_id = j.job_id
  ORDER BY j.job_id, c.change_id;

-- FIXME: shouldn't belong to the app user
ALTER TABLE spider_job_change
  OWNER TO project_mon;

