CREATE TABLE spider_errlog (
  spider_uuid UUID NOT NULL,
  ts TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
  msg TEXT NOT NULL,
  PRIMARY KEY (spider_uuid, ts)
);

DROP TABLE IF EXISTS xfrm CASCADE;
CREATE TABLE xfrm (
  xfrm_id SERIAL PRIMARY KEY,
  xfrm_commands TEXT NOT NULL,
  xfrm_args jsonb
);

ALTER TABLE xfrm
  ADD CONSTRAINT xfrm_commands_args_key
    UNIQUE (xfrm_commands, xfrm_args);

ALTER TABLE url
  ALTER COLUMN url TYPE VARCHAR(500),
  ADD COLUMN xfrm_id INT;

ALTER TABLE spider_document
  ADD COLUMN xfrm_id INT,
  ADD COLUMN contents_hash BYTEA;

WITH
  new_xfrm AS (
    INSERT INTO xfrm (xfrm_commands, xfrm_args)
    VALUES ('html2text', '{}')
    RETURNING xfrm_id
  ),
  url_update AS (
    UPDATE url
      SET xfrm_id = new_xfrm.xfrm_id
      FROM new_xfrm
      RETURNING new_xfrm.xfrm_id
  ),
  document_update AS (
    UPDATE spider_document
      SET xfrm_id = new_xfrm.xfrm_id
      FROM new_xfrm
      RETURNING new_xfrm.xfrm_id
  )
SELECT COUNT(1) FROM url_update
UNION ALL
SELECT COUNT(1) FROM document_update;

UPDATE spider_document
SET contents_hash = digest(contents, 'sha256');

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

ALTER TABLE url
  ALTER COLUMN xfrm_id SET NOT NULL,
  ADD CONSTRAINT xfrm_id_fk FOREIGN KEY (xfrm_id)
    REFERENCES xfrm (xfrm_id);

ALTER TABLE spider_document
  ALTER COLUMN xfrm_id SET NOT NULL,
  ALTER COLUMN contents_hash SET NOT NULL,
  ADD CONSTRAINT xfrm_id_fk FOREIGN KEY (xfrm_id)
    REFERENCES xfrm (xfrm_id),
  ADD CONSTRAINT spider_document_hash_matches
    CHECK(digest(contents, 'sha256') = contents_hash);

DROP INDEX spider_document_job_id;
CREATE INDEX spider_document_last_doc_idx
  ON spider_document(job_id, xfrm_id, spider_document_id);

INSERT INTO cfg (cfg_name, cfg_value) VALUES ('alert_url', 's:14:"alerts/detail/";');

DROP VIEW IF EXISTS v_change;
CREATE VIEW v_change AS
SELECT n.alert_id, a.alert_title, n.new_doc_id, n.old_doc_id, MIN(n.creation_ts) as creation_ts,
n.url_id, u.url, u.url_title, g.url_group_id, g.url_group_title, a.user_id
FROM notification n JOIN alert a ON (a.alert_id = n.alert_id)
JOIN url u ON (u.url_id = n.url_id)
LEFT JOIN url_x_group ug ON (ug.url_id = u.url_id)
LEFT JOIN url_group g ON (ug.url_group_id = g.url_group_id)
LEFT JOIN access_control acc ON (a.user_id = acc.user_id AND acc.url_group_id = ug.url_group_id)
WHERE a.alert_active = TRUE
GROUP BY n.alert_id, a.user_id,n.new_doc_id, n.url_id, u.url, u.url_title, n.old_doc_id,
a.alert_title, g.url_group_id, g.url_group_title;
