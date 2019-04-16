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

-- FIXME: shouldn't belong to the app user
ALTER TABLE spider_load_agg
  OWNER TO project_mon;

