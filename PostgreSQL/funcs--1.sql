-- Called from the spider to register and periodically as a
-- heartbeat. If the passed UUID is NULL, a new UUID is assigned and
-- returned. Otherwise, an existing row is updated and NULL gets
-- returned.
CREATE OR REPLACE FUNCTION spider_heartbeat(UUID)
RETURNS uuid
AS $$
  -- Fast and fancy UPSERT variant, no lost updates.
  WITH
    new_values (spider_uuid, spider_last_seen) AS (
      VALUES (coalesce($1, gen_random_uuid()), now())
    ),
    upsert AS (
      UPDATE spider s SET
        spider_uuid = nv.spider_uuid,
        spider_last_seen = nv.spider_last_seen
      FROM new_values nv
      WHERE s.spider_uuid = nv.spider_uuid
      RETURNING s.*
    )
  INSERT INTO spider (spider_uuid, spider_last_seen)
  SELECT new_values.spider_uuid, spider_last_seen
  FROM new_values
  WHERE NOT EXISTS (SELECT 1 FROM upsert
                    WHERE new_values.spider_uuid = upsert.spider_uuid)
  RETURNING spider_uuid;
$$ LANGUAGE SQL;
