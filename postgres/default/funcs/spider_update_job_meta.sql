SET search_path TO public, thirdparty;

DROP FUNCTION IF EXISTS spider_update_job_meta(INT, TEXT, TEXT, BYTEA,
                                               TIMESTAMP WITH TIME ZONE);
DROP FUNCTION IF EXISTS spider_update_job_meta(INT, TEXT, TEXT, BYTEA);

-- For a given job and assuming the downloaded resource changed, this
-- functions returns the transformation commands that need to be
-- applied and some per-check-interval information about the latest
-- document.
CREATE FUNCTION spider_update_job_meta(in_job_id INT, in_last_modification TEXT,
                                       in_entity_tag TEXT, in_hash BYTEA)
RETURNS TABLE (
  xfrm_id INT,
  commands TEXT,
  args jsonb,
  latest_doc_id BIGINT,
  latest_doc_contents_hash BYTEA
)
AS $$
DECLARE
  old_hash BYTEA;
BEGIN
  SELECT last_hash INTO old_hash
  FROM spider_job WHERE job_id = in_job_id
  FOR UPDATE;

  UPDATE spider_job
  SET last_check_ts = now(),
      last_modification = in_last_modification,
      last_hash = in_hash,
      last_entity_tag = in_entity_tag
  WHERE job_id = in_job_id;

  RETURN QUERY
    SELECT
      xfrm.xfrm_id,
      xfrm.xfrm_commands,
      xfrm.xfrm_args,
      doc.spider_document_id AS latest_doc_id,
      doc.contents_hash AS latest_doc_contents_hash
    FROM spider_job j
    INNER JOIN url ON url.spider_job_id = j.job_id
    INNER JOIN xfrm ON xfrm.xfrm_id = url.xfrm_id
    -- join the latest document per job and xfrm_id
    LEFT JOIN spider_document_meta doc
      ON doc.job_id = j.job_id
      AND doc.xfrm_id = url.xfrm_id
    WHERE
      j.job_id = in_job_id
      AND url.url_active
      AND j.job_active
   GROUP BY
     xfrm.xfrm_id, xfrm_commands, xfrm_args,
     doc.spider_document_id,
     doc.contents_hash;
END
$$ LANGUAGE plpgsql;

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION public.spider_update_job_meta(in_job_id integer, in_last_modification text, in_entity_tag text, in_hash bytea)
  OWNER TO project_mon;
