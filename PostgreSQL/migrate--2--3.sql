ALTER TABLE spider_job
  ADD COLUMN last_check_ts TIMESTAMP WITH TIME ZONE NULL;

DROP TABLE IF EXISTS spider_document;
CREATE TABLE spider_document (
  spider_document_id BIGSERIAL PRIMARY KEY,
  job_id INTEGER REFERENCES spider_job(job_id),
  reception_ts TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
  last_mod TEXT NULL,
  entity_tag TEXT NULL,
  contents BYTEA NOT NULL
);

DROP TABLE IF EXISTS notification;
CREATE TABLE notification (
  alert_id INTEGER REFERENCES alert(alert_id) NOT NULL,
  old_doc_id BIGINT REFERENCES spider_document(spider_document_id) NOT NULL,
  new_doc_id BIGINT REFERENCES spider_document(spider_document_id) NOT NULL,
  ts TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
  delivered BOOL NOT NULL,
  PRIMARY KEY (alert_id, new_doc_id)
);
