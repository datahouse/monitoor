SET search_path TO public, thirdparty;

DROP FUNCTION IF EXISTS spider_rebalance_jobs();
CREATE FUNCTION spider_rebalance_jobs()
RETURNS void
AS $$
  -- Assign unassigned jobs and reassign those of inactive spiders.
  WITH
    reassigned_job AS (
      UPDATE spider_job SET job_spider_uuid = (
        -- assign to some random not-suspected backend (or NULL, if
        -- none is available)
        SELECT uuid FROM spider_status
        WHERE NOT suspected
          AND job_id = job_id -- A dependency on the outer select, so
                              -- this subselect will be re-evaluated
                              -- for every row to update.
        LIMIT 1
      )
      FROM spider_status AS s
      WHERE (s.inactive AND job_spider_uuid = s.uuid)
         OR job_spider_uuid IS NULL
      RETURNING
        job_id AS job_id,
        job_spider_uuid IS NOT NULL AS is_assigned
    )
  INSERT INTO spider_change (job_id, change_type)
    SELECT job_id, 'update'::change_type_enum
      FROM reassigned_job
      WHERE is_assigned;
$$ LANGUAGE sql;

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION public.spider_rebalance_jobs()
  OWNER TO project_mon;

