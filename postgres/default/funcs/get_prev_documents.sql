DROP FUNCTION IF EXISTS get_prev_documents(start_doc_id BIGINT);
CREATE OR REPLACE FUNCTION get_prev_documents(start_doc_id BIGINT, in_limit INT)
RETURNS SETOF BIGINT
RETURNS NULL ON NULL INPUT
AS $$
  WITH RECURSIVE prev_docs(id) AS
    (
      SELECT start_doc_id AS id
      UNION ALL
      SELECT old_doc_id AS id
        FROM change c, prev_docs
        WHERE c.new_doc_id = prev_docs.id
    )
  SELECT * FROM prev_docs
    LIMIT in_limit;
$$ LANGUAGE SQL STABLE;
