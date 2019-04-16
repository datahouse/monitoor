ALTER TABLE url
  ADD COLUMN url_active BOOL NOT NULL DEFAULT true;

CREATE OR REPLACE FUNCTION change_type_agg_sfunc(a change_type_enum, b change_type_enum)
RETURNS change_type_enum
AS $$
  SELECT (CASE WHEN a = 'delete' OR b = 'delete'
    THEN 'delete'
    ELSE CASE WHEN a = 'insert' OR b = 'insert'
      THEN 'insert'
      ELSE 'update'
    END
  END)::change_type_enum;
$$ LANGUAGE SQL IMMUTABLE;

DROP AGGREGATE IF EXISTS change_type_agg(change_type_enum);
CREATE AGGREGATE change_type_agg(change_type_enum)
(
  sfunc = change_type_agg_sfunc,
  stype = change_type_enum,
  initcond = 'update'
);

DROP VIEW IF EXISTS spider_job_change;
CREATE VIEW spider_job_change AS
  SELECT j.job_id, j.url, j.url_lang, j.min_check_interval,
         job_spider_uuid, job_active,
         extract('epoch' FROM now() - j.last_check_ts) AS age,
         c.change_id, c.change_type FROM spider_job j
  JOIN spider_change c ON c.job_id = j.job_id
  ORDER BY j.job_id, c.change_id;


DROP FUNCTION IF EXISTS spider_rebalance_jobs();
CREATE FUNCTION spider_rebalance_jobs()
RETURNS void
AS $$
  -- SIMPLE, STUPID ASSIGNMENT
  UPDATE spider_job SET job_spider_uuid = (
    SELECT spider_uuid FROM spider ORDER BY spider_last_seen DESC LIMIT 1
  ) WHERE job_active AND job_spider_uuid IS NULL;
$$ LANGUAGE SQL;
