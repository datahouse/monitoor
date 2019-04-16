SET search_path TO public, thirdparty;

DROP FUNCTION IF EXISTS spider_store_document(INT, TEXT, TEXT, BYTEA);
DROP FUNCTION IF EXISTS spider_store_document(INT, BYTEA);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, UUID, BYTEA, json);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, UUID, BYTEA, INT[]);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, UUID, BYTEA,
                                              TIMESTAMP WITH TIME ZONE, INT[]);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, INT, UUID, BYTEA, BYTEA,
                                              TIMESTAMP WITH TIME ZONE, INT[]);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, INT, UUID,
                                              BYTEA, BYTEA,
                                              TIMESTAMP WITH TIME ZONE,
                                              JSON);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, BIGINT, UUID,
                                              BYTEA, BYTEA,
                                              TIMESTAMP WITH TIME ZONE,
                                              JSON);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, BIGINT, UUID,
                                              BYTEA, BYTEA,
                                              TIMESTAMP WITH TIME ZONE);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, UUID,
                                              BYTEA, BYTEA,
                                              TIMESTAMP WITH TIME ZONE);
DROP FUNCTION IF EXISTS spider_store_document(INT, INT, BIGINT, UUID,
                                              BYTEA, BYTEA,
                                              TIMESTAMP WITH TIME ZONE);
CREATE OR REPLACE FUNCTION spider_store_document(
  in_job_id INT, in_xfrm_id INT, in_old_doc_id BIGINT, in_spider_uuid UUID,
  in_contents BYTEA, in_contents_hash BYTEA, in_contents_mime_type TEXT,
  in_ts TIMESTAMP WITH TIME ZONE)
RETURNS TABLE (
  spider_document_id BIGINT
)
AS $$
DECLARE
  inserted_doc_id BIGINT;
  inserted_change_id BIGINT;
BEGIN
  INSERT INTO spider_document (job_id, xfrm_id, contents, contents_hash,
                               reception_ts)
    VALUES (in_job_id, in_xfrm_id, in_contents, in_contents_hash, in_ts)
    RETURNING spider_document.spider_document_id INTO inserted_doc_id;

  IF in_old_doc_id IS NOT NULL THEN
    IF in_contents_mime_type = 'multipart/mixed' THEN
      PERFORM spider_generate_pending_changes_multipart(in_job_id, in_xfrm_id,
        in_spider_uuid, in_old_doc_id, inserted_doc_id, in_ts);
    ELSE
      PERFORM spider_generate_pending_changes_simple(in_job_id, in_xfrm_id,
        in_spider_uuid, in_old_doc_id, inserted_doc_id, in_ts);
    END IF;
  END IF;

  RETURN QUERY SELECT inserted_doc_id;
END
$$ LANGUAGE plpgsql;

GRANT EXECUTE
  ON FUNCTION public.spider_store_document(INT, INT, BIGINT, UUID,
    BYTEA, BYTEA, TEXT, TIMESTAMP WITH TIME ZONE)
  TO project_mon;
