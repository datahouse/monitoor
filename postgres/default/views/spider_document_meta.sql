CREATE VIEW spider_document_meta AS
WITH
  grouped AS (
    SELECT job_id, xfrm_id, max(spider_document_id) AS max_doc_id
    FROM spider_document
    GROUP BY job_id, xfrm_id
  )
SELECT doc.spider_document_id, doc.job_id, doc.xfrm_id, doc.reception_ts,
  doc.contents, doc.contents_hash
FROM spider_document doc
INNER JOIN grouped ON grouped.max_doc_id = doc.spider_document_id;

-- FIXME: shouldn't belong to the app user
ALTER VIEW spider_document_meta
  OWNER TO project_mon;
