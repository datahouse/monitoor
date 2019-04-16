-- If there are multiple entries in spider_job for what matches a
-- single encoded URL, this migration script needs to fail, as it is
-- not capable of merging them. I currently think handling these
-- issues manually should be sufficient.
--
-- Also note that this migration scripts depends on a function defined
-- in funcs--14.sql... (OMG, way too dependencies to handle manually!)
--
-- ATTENTION: the following statement failing means this script is not
-- capable of migrating your database! Manual assistance is required.
CREATE UNIQUE INDEX tmp_test_idx
  ON spider_job (uri_encode(url));

-- Switch all URLs in spider_url to an URL-encoded variant.
UPDATE spider_job SET url = uri_encode(url);

DROP INDEX tmp_test_idx;


DROP VIEW IF EXISTS spider_load_agg;
CREATE OR REPLACE VIEW spider_load_agg AS
  SELECT
    spider_uuid,
    bucket_id,
    avg(load_one)
      AS load_one_avg,
    sum(bytes_sent_diff) + sum(bytes_recv_diff)
      AS network_bytes_sum,
    sum(cpu_iowait_time_diff)
      AS cpu_iowait_time_sum
  FROM (
    SELECT
      spider_uuid,
      (extract(epoch FROM now() - ts)
        -- A normal day has 86400 seconds (minus a leap second here
        -- and there, but we generously disregard these here). We
        -- multiply that by 100 buckets we want.
        * 100 / 86400)::INT
      AS bucket_id,
      load_one,
      bytes_sent - lag(bytes_sent) OVER (ORDER BY ts)
        AS bytes_sent_diff,
      bytes_recv - lag(bytes_recv) OVER (ORDER BY ts)
        AS bytes_recv_diff,
      cpu_iowait_time - lag(cpu_iowait_time) OVER (ORDER BY ts)
        AS cpu_iowait_time_diff
    FROM spider_load
    WHERE now() - spider_load.ts < '1 day'
  ) AS f
  GROUP BY spider_uuid, bucket_id
  ORDER BY bucket_id ASC;


-- Add a new view - from structure--14.sql
DROP VIEW IF EXISTS spider_load_agg;
CREATE OR REPLACE VIEW spider_load_agg AS
  SELECT
    spider_uuid,
    bucket_id,
    avg(load_one)
      AS load_one_avg,
    sum(bytes_sent_diff) + sum(bytes_recv_diff)
      AS network_bytes_sum,
    sum(cpu_iowait_time_diff)
      AS cpu_iowait_time_sum
  FROM (
    SELECT
      spider_uuid,
      (extract(epoch FROM now() - ts)
        -- A normal day has 86400 seconds (minus a leap second here
        -- and there, but we generously disregard these here). We
        -- multiply that by 100 buckets we want.
        * 100 / 86400)::INT
      AS bucket_id,
      load_one,
      bytes_sent - lag(bytes_sent) OVER (ORDER BY ts)
        AS bytes_sent_diff,
      bytes_recv - lag(bytes_recv) OVER (ORDER BY ts)
        AS bytes_recv_diff,
      cpu_iowait_time - lag(cpu_iowait_time) OVER (ORDER BY ts)
        AS cpu_iowait_time_diff
    FROM spider_load
    WHERE now() - spider_load.ts < '1 day'
  ) AS f
  GROUP BY spider_uuid, bucket_id
  ORDER BY bucket_id ASC;
