ALTER TABLE access_control
  ALTER COLUMN access_control_valid_from SET DEFAULT now();

CREATE INDEX url_spider_job_id ON url(spider_job_id);
CREATE INDEX alert_user_id_idx ON alert(user_id);
CREATE INDEX alert_x_url_url_id_idx ON alert_x_url(url_id);
CREATE INDEX alert_x_url_group_url_group_id_idx ON alert_x_url_group(url_group_id);
CREATE INDEX spider_document_job_id ON spider_document(job_id);

DROP VIEW IF EXISTS spider_status;
CREATE VIEW spider_status AS
  SELECT
    spider_uuid AS uuid,
    now() - spider_last_seen > '300 seconds'::interval AS inactive,
    now() - spider_last_seen > '60 seconds'::interval AS suspected
  FROM spider
  ORDER BY inactive, suspected, random();
