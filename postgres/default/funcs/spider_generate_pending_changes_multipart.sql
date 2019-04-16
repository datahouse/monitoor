SET search_path TO public, thirdparty;

-- Generates an entry in pending_changes per part of a multipart
-- document. Used for RSS feeds.
CREATE OR REPLACE FUNCTION spider_generate_pending_changes_multipart(
  in_job_id INT, in_xfrm_id INT, in_spider_uuid UUID,
  in_old_doc_id BIGINT, in_new_doc_id BIGINT,
  in_ts TIMESTAMP WITH TIME ZONE
)
RETURNS void
AS $$
BEGIN
  WITH
    -- Retrieves the url_ids of entries affected by this spider job
    -- as well as the last time a change has been generated per each
    -- required check_frequency_interval.
    url_ids AS (
      SELECT
        array_agg(u.url_id) AS affected_url_ids
      FROM url AS u
      WHERE u.spider_job_id = in_job_id
        AND u.xfrm_id = in_xfrm_id
        AND u.url_active
    ),
    -- select only items that appear in the new document, but not in
    -- the old one.
    add_parts AS (
      SELECT *
      FROM diff_multipart_by_content_id(
        ARRAY[in_new_doc_id],
        -- compare against the last 10 documents for this job
        (
          SELECT array_agg(id)
            FROM get_prev_documents(in_old_doc_id, 10) AS id
        )
      )
    )

  -- Then insert a pending change per check_frequency and xfrm_id.
  INSERT INTO pending_change
    (check_frequency_id, url_ids, creation_ts, not_before_ts,
     delta, old_doc_id, new_doc_id)
  SELECT
    1::INT AS check_frequency_id, -- hard-coded to hourly
    url_ids.affected_url_ids AS url_ids,
    in_ts AS creation_ts,
    in_ts AS not_before_ts,  -- don't hold back anything, but don't publish prior
                             -- to creation, either.
    json_build_array(
        json_build_object(
          'add', add_parts.body,
          'del', ARRAY[]::TEXT[]
        )
    ) AS delta,              -- a single section without deletions, similar to
                             -- external changes
    in_old_doc_id,
    in_new_doc_id
  FROM url_ids
  JOIN add_parts ON True;
END
$$ LANGUAGE plpgsql;

GRANT EXECUTE
  ON FUNCTION spider_generate_pending_changes_multipart(
    INT, INT, UUID, BIGINT, BIGINT, TIMESTAMP WITH TIME ZONE
  )
  TO project_mon;
