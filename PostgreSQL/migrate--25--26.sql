-- An optimized variant of this view, also using the url's
-- url_latest_change_id cache. Needs to scan pending_change in
-- addition, but that table should always be small.
CREATE OR REPLACE VIEW spider_document_meta AS
SELECT doc.spider_document_id, doc.job_id, doc.xfrm_id, doc.reception_ts,
  doc.contents, doc.contents_hash
FROM (
  SELECT
    j.job_id,
    url.xfrm_id,
    COALESCE(max(pc.new_doc_id), max(c.new_doc_id)) AS max_doc_id
    FROM url
    INNER JOIN change c
      ON url.url_latest_change_id = c.change_id
    INNER JOIN spider_job j
      ON j.job_id = url.spider_job_id
    LEFT JOIN pending_change pc
      ON url.url_id = ANY(pc.url_ids)
    GROUP BY j.job_id, url.xfrm_id
) AS x
LEFT JOIN spider_document doc
  ON doc.spider_document_id = x.max_doc_id
  AND doc.job_id = x.job_id
  AND doc.xfrm_id = x.xfrm_id;
