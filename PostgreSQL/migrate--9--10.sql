ALTER TABLE alert_x_keyword
  ADD COLUMN alert_keyword TEXT,
  ADD COLUMN alert_keyword_active BOOL;

UPDATE alert_x_keyword
  SET alert_keyword = x.alert_keyword,
      alert_keyword_active = True
  FROM alert_keyword AS x
  WHERE alert_x_keyword.alert_keyword_id = x.alert_keyword_id;

ALTER TABLE alert_x_keyword
  DROP COLUMN alert_keyword_id;

DROP TABLE alert_keyword;

ALTER TABLE alert_x_keyword
  RENAME TO alert_keyword;

ALTER TABLE alert_keyword
  RENAME COLUMN alert_x_keyword_id TO alert_keyword_id;

ALTER SEQUENCE alert_x_keyword_alert_x_keyword_id_seq
  RENAME TO alert_keyword_alert_keyword_id_seq;

ALTER INDEX alert_x_keyword_pkey
  RENAME TO alert_keyword_pkey;

WITH
  numbered AS (
    SELECT
      alert_keyword_id,
      row_number() OVER (PARTITION BY alert_id, alert_keyword
                         ORDER BY alert_id, alert_keyword)
      AS row_number
    FROM alert_keyword
  )
DELETE FROM alert_keyword
  USING numbered
  WHERE alert_keyword.alert_keyword_id = numbered.alert_keyword_id
    AND numbered.row_number > 1;

ALTER TABLE alert_keyword
  ALTER COLUMN alert_keyword SET NOT NULL,
  ALTER COLUMN alert_keyword_active SET DEFAULT True,
  ALTER COLUMN alert_keyword_active SET NOT NULL,
  ADD CONSTRAINT alert_keyword_alert_id_alert_keyword_key
    UNIQUE (alert_id, alert_keyword),
  ADD CONSTRAINT alert_keyword_lowercase
    CHECK (alert_keyword = lower(alert_keyword));

ALTER TABLE notification_x_keyword
  ADD CONSTRAINT alert_keyword_id_fk FOREIGN KEY (alert_keyword_id)
    REFERENCES alert_keyword(alert_keyword_id);
