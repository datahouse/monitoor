SET search_path TO public, thirdparty;

-- Generates a single entry in pending_change for a newly inserted
-- document document. Used for the common case, where the
-- transformation yields a single document.
CREATE OR REPLACE FUNCTION spider_generate_pending_changes_simple(
  in_job_id INT, in_xfrm_id INT, in_spider_uuid UUID,
  in_old_doc_id BIGINT, in_new_doc_id BIGINT,
  in_ts TIMESTAMP WITH TIME ZONE
)
RETURNS void
AS $$
  WITH
    -- Retrieves the url_ids of entries affected by this spider job
    -- as well as the last time a change has been generated per each
    -- required check_frequency_interval.
    per_check_frequency AS (
      SELECT
        cf.check_frequency_id,
        cf.check_frequency_interval,
        u.xfrm_id,
        array_agg(u.url_id) AS affected_url_ids,
        -- there should only ever be one, so max() doesn't change
        -- much.
        max(c.ts) AS latest_change_ts
      FROM url u
      LEFT JOIN check_frequency cf
        ON cf.check_frequency_id = u.check_frequency_id
      LEFT JOIN change c
        ON u.url_latest_change_id = c.change_id
      WHERE u.spider_job_id = in_job_id
        AND u.xfrm_id = in_xfrm_id
        AND u.url_active
      GROUP BY
        cf.check_frequency_id, cf.check_frequency_interval, u.xfrm_id
    )
  -- Then insert a pending change per check_frequency and xfrm_id.
  INSERT INTO pending_change
    (check_frequency_id, url_ids, creation_ts, not_before_ts,
     old_doc_id, new_doc_id)
  SELECT
    check_frequency_id,
    affected_url_ids AS url_ids,
    in_ts AS creation_ts,
    COALESCE(
      latest_change_ts + check_frequency_interval * interval '1 second',
      '01-01-1980T00:00:00'
    ) AS not_before_ts,
    in_old_doc_id, in_new_doc_id
  FROM per_check_frequency;
$$ LANGUAGE sql;

GRANT EXECUTE
  ON FUNCTION public.spider_generate_pending_changes_simple(INT, INT, UUID,
    BIGINT, BIGINT, TIMESTAMP WITH TIME ZONE)
  TO project_mon;
