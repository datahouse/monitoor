SET search_path TO public, thirdparty;

-- Given two multipart spider_documents, this function returns the
-- parts that appear in the first set of documents, but not in the second
-- set.
DROP FUNCTION IF EXISTS diff_multipart_by_content_id(BIGINT, BIGINT);
CREATE OR REPLACE FUNCTION diff_multipart_by_content_id(
  first_doc_ids BIGINT[],
  second_doc_ids BIGINT[])
RETURNS TABLE (
  mime_type TEXT,
  content_id TEXT,
  ts TIMESTAMP WITH TIME ZONE,
  body TEXT[]
)
AS $$
  WITH
    -- split the first version of the multipart document into its parts
    first_parts AS (
      SELECT parts.*
      FROM spider_document
      JOIN split_multipart(
        convert_from(spider_document.contents, 'utf-8'),
        '====================548487216=='
      ) AS parts ON True
      WHERE spider_document_id = ANY(first_doc_ids)
    ),
    -- split the second version of the multipart document into its parts
    second_parts AS (
      SELECT parts.*
      FROM spider_document
      JOIN split_multipart(
        convert_from(spider_document.contents, 'utf-8'),
        '====================548487216=='
      ) AS parts ON True
      WHERE spider_document_id = ANY(second_doc_ids)
    )
  -- select only items that appear in the new document, but not in
  -- the old one.
  SELECT *
  FROM first_parts
  WHERE NOT EXISTS
   (
     SELECT content_id
     FROM second_parts
     WHERE second_parts.content_id = first_parts.content_id
   );
$$ LANGUAGE SQL;

GRANT EXECUTE
  ON FUNCTION diff_multipart_by_content_id(BIGINT[], BIGINT[])
  TO project_mon;
