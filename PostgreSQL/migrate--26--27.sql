-- Revert this view, v26's variant was faster, but wrong.
CREATE OR REPLACE VIEW spider_document_meta AS
SELECT doc.spider_document_id, doc.job_id, doc.xfrm_id, doc.reception_ts,
  doc.contents, doc.contents_hash
FROM (
  SELECT * FROM (
    SELECT
      job_id,
      xfrm_id,
      max(spider_document_id)
        OVER (PARTITION BY job_id, xfrm_id)
        AS max_doc_id
    FROM spider_document
  ) AS x
  GROUP BY job_id, xfrm_id, max_doc_id
) AS x
LEFT JOIN spider_document doc
  ON doc.spider_document_id = x.max_doc_id
  AND doc.job_id = x.job_id
  AND doc.xfrm_id = x.xfrm_id;
