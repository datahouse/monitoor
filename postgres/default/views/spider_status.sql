-- Definitions of active and inactive backends: new jobs can be
-- assigned to active backends, those in between don't lose their
-- jobs, yet, but don't get any new ones. And once a backend becomes
-- inactive, others take over their jobs. (Heartbeat interval of the
-- backend is 30 seconds.)
DROP VIEW IF EXISTS spider_status;
CREATE VIEW spider_status AS
  SELECT
    spider_uuid AS uuid,
    now() - spider_last_seen > '300 seconds'::interval AS inactive,
    now() - spider_last_seen > '60 seconds'::interval AS suspected
  FROM spider
  ORDER BY inactive, suspected, random();

-- FIXME: shouldn't belong to the app user
ALTER TABLE spider_status
  OWNER TO project_mon;
