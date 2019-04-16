SET search_path TO public, thirdparty;

-- Called from the spider to register and periodically as a
-- heartbeat. If the passed UUID is NULL, a new UUID is assigned and
-- returned. Otherwise, an existing row is updated and NULL gets
-- returned.
DROP FUNCTION IF EXISTS spider_heartbeat(UUID);
DROP FUNCTION IF EXISTS spider_heartbeat(UUID, TEXT, FLOAT, FLOAT, FLOAT,
     FLOAT, FLOAT, FLOAT, FLOAT, FLOAT, FLOAT,
     BIGINT, BIGINT, BIGINT, BIGINT);
CREATE OR REPLACE FUNCTION spider_heartbeat(
  in_uuid UUID,
  in_fqdn TEXT,
  in_load_one FLOAT,
  in_load_five FLOAT,
  in_load_fifteen FLOAT,
  in_cpu_user_time FLOAT,
  in_cpu_nice_time FLOAT,
  in_cpu_system_time FLOAT,
  in_cpu_idle_time FLOAT,
  in_cpu_iowait_time FLOAT,
  in_cpu_irq_time FLOAT,
  in_bytes_sent BIGINT,
  in_bytes_recv BIGINT,
  in_packets_sent BIGINT,
  in_packets_recv BIGINT
)
RETURNS uuid
AS $$
DECLARE
  out_uuid UUID;
BEGIN
  -- Fast and fancy UPSERT variant, no lost updates.
  WITH
    new_values (uuid, ts, fqdn) AS (
      VALUES (
        coalesce(in_uuid, thirdparty.gen_random_uuid()),
        now(),
        in_fqdn
      )
    ),
    upsert AS (
      UPDATE spider s SET
        spider_uuid = nv.uuid,
        spider_last_seen = nv.ts,
        spider_last_hostname = nv.fqdn
      FROM new_values nv
      WHERE s.spider_uuid = nv.uuid
      RETURNING s.*
    )
  INSERT INTO spider (spider_uuid, spider_last_seen, spider_last_hostname)
  SELECT new_values.*
  FROM new_values
  WHERE NOT EXISTS (SELECT 1 FROM upsert
                    WHERE new_values.uuid = upsert.spider_uuid)
  RETURNING spider_uuid INTO out_uuid;

  INSERT INTO spider_load
    (spider_uuid, load_one, load_five, load_fifteen,
     cpu_user_time, cpu_nice_time, cpu_system_time,
     cpu_idle_time, cpu_iowait_time, cpu_irq_time,
     bytes_sent, bytes_recv, packets_sent, packets_recv)
  VALUES (
    COALESCE(in_uuid, out_uuid),
    in_load_one, in_load_five, in_load_fifteen,
    in_cpu_user_time, in_cpu_nice_time, in_cpu_system_time,
    in_cpu_idle_time, in_cpu_iowait_time, in_cpu_irq_time,
    in_bytes_sent, in_bytes_recv, in_packets_sent, in_packets_recv
  );

  RETURN out_uuid;
END
$$ LANGUAGE plpgsql;

ALTER FUNCTION public.spider_heartbeat(in_uuid uuid, in_fqdn text, in_load_one double precision, in_load_five double precision, in_load_fifteen double precision, in_cpu_user_time double precision, in_cpu_nice_time double precision, in_cpu_system_time double precision, in_cpu_idle_time double precision, in_cpu_iowait_time double precision, in_cpu_irq_time double precision, in_bytes_sent bigint, in_bytes_recv bigint, in_packets_sent bigint, in_packets_recv bigint)
  OWNER TO project_mon;
