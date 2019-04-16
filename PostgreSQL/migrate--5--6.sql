ALTER TABLE spider_job
  ADD COLUMN last_modification TEXT NULL,
  ADD COLUMN last_hash BYTEA NULL,
  ADD COLUMN last_entity_tag TEXT NULL;

ALTER TABLE spider_document
  DROP COLUMN last_mod,
  DROP COLUMN entity_tag;

DROP VIEW IF EXISTS v_alert_url_u_group;
CREATE OR REPLACE VIEW v_alert_url_u_group AS
  SELECT a.alert_id, a.alert_title, a.user_id, u.url_id, NULL AS url_group_id,
         u.url_title AS url_or_group_title, r.alert_release_date, a.alert_active
  FROM alert a
  JOIN alert_x_url x ON a.alert_id = x.alert_id
  JOIN url u ON x.url_id = u.url_id
  LEFT JOIN v_alert_release r ON a.alert_id=r.alert_id
UNION ALL
  SELECT a.alert_id, a.alert_title, a.user_id, NULL AS url_id, g.url_group_id,
         g.url_group_title AS url_or_group_title, r.alert_release_date, a.alert_active
  FROM alert a
  JOIN alert_x_url_group x ON a.alert_id = x.alert_id
  JOIN url_group g ON x.url_group_id = g.url_group_id
  LEFT JOIN v_alert_release r ON a.alert_id=r.alert_id;

DROP TABLE IF EXISTS cfg CASCADE;
CREATE TABLE IF NOT EXISTS cfg (
  cfg_id SERIAL  NOT NULL,
  cfg_name varchar(255) NOT NULL,
  cfg_value varchar(255) NOT NULL,
  PRIMARY KEY (cfg_id)
) ;


INSERT INTO cfg (cfg_name, cfg_value)
  VALUES
  ('email_from','s:21:"moonitor@datahouse.ch";'),
  ('reset_url','s:14:"passwordReset/";');
