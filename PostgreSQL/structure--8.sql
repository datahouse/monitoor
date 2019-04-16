DROP TABLE IF EXISTS mon_user CASCADE;
CREATE TABLE IF NOT EXISTS mon_user (
  user_id SERIAL NOT NULL,
  user_email varchar(100) NOT NULL,
  user_password char(64) NULL,
  user_password_salt char(16) NULL,
  user_valid_from timestamp NOT NULL DEFAULT now(),
  user_valid_till timestamp NULL,
  user_last_login timestamp NULL,
  PRIMARY KEY (user_id)
) ;

DROP TABLE IF EXISTS user_pw_recovery CASCADE;
CREATE TABLE IF NOT EXISTS user_pw_recovery (
  user_pw_recovery_id SERIAL NOT NULL,
  user_id INTEGER NOT NULL,
  user_pw_recovery_created timestamp NOT NULL,
  user_pw_recovery_used timestamp NULL,
  user_pw_recovery_hash char(40) NOT NULL,
  PRIMARY KEY (user_pw_recovery_id)
) ;

DROP TABLE IF EXISTS account CASCADE;
CREATE TABLE IF NOT EXISTS account (
  account_id SERIAL NOT NULL,
  user_id INTEGER NOT NULL,
  account_name_first varchar(255) NOT NULL,
  account_name_last varchar(255) NOT NULL,
  account_mobile varchar(20) NULL,
  PRIMARY KEY (account_id)
) ;

DROP TABLE IF EXISTS user_group CASCADE;
CREATE TABLE IF NOT EXISTS user_group (
  user_group_id SERIAL NOT NULL,
  user_group_name varchar(100) NOT NULL,
  PRIMARY KEY (user_group_id)
) ;

DROP TABLE IF EXISTS user_x_group CASCADE;
CREATE TABLE IF NOT EXISTS user_x_group (
  user_x_group_id SERIAL NOT NULL,
  user_id integer NOT NULL,
  user_group_id integer NOT NULL,
  PRIMARY KEY (user_x_group_id)
) ;

DROP TABLE IF EXISTS role CASCADE;
CREATE TABLE IF NOT EXISTS role (
  role_id SERIAL  NOT NULL,
  role_short varchar(10) NOT NULL,
  role_description varchar(100) NOT NULL,
  PRIMARY KEY (role_id)
) ;

DROP TABLE IF EXISTS user_x_role CASCADE;
CREATE TABLE IF NOT EXISTS user_x_role (
  user_x_role_id SERIAL NOT NULL,
  user_id integer  NOT NULL,
  role_id integer  NOT NULL,
  PRIMARY KEY (user_x_role_id)
) ;

DROP TABLE IF EXISTS spider CASCADE;
CREATE TABLE spider (
  spider_uuid UUID NOT NULL,
  spider_last_seen TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
  PRIMARY KEY (spider_uuid)
);

DROP TABLE IF EXISTS spider_errlog CASCADE;
CREATE TABLE spider_errlog (
  spider_uuid UUID NOT NULL,
  ts TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
  msg TEXT NOT NULL,
  PRIMARY KEY (spider_uuid, ts)
);

DROP TABLE IF EXISTS spider_job CASCADE;
CREATE TABLE spider_job (
  job_id SERIAL PRIMARY KEY,
  url TEXT NOT NULL,              -- url and url_lang shouldn't ever change
  url_lang TEXT NOT NULL,         -- after the initial INSERT - create a
                                  -- new job, instead!
  min_check_interval BIGINT NOT NULL, -- minimal check interval (in seconds)
  job_spider_uuid uuid,
  job_active BOOL DEFAULT true NOT NULL,
  last_check_ts TIMESTAMP WITH TIME ZONE NULL,
  last_modification TEXT NULL,
  last_hash BYTEA NULL,
  last_entity_tag TEXT NULL
);

DROP TYPE IF EXISTS change_type_enum CASCADE;
CREATE TYPE change_type_enum AS ENUM ('insert', 'update', 'delete');

DROP TABLE IF EXISTS spider_change CASCADE;
CREATE TABLE spider_change (
  change_id BIGSERIAL PRIMARY KEY,
  job_id INTEGER NOT NULL,
  change_type change_type_enum NOT NULL
);

DROP TABLE IF EXISTS spider_document CASCADE;
CREATE TABLE spider_document (
  spider_document_id BIGSERIAL PRIMARY KEY,
  job_id INTEGER NOT NULL,
  xfrm_id INT NOT NULL,
  reception_ts TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
  contents BYTEA NOT NULL,
  contents_hash BYTEA NOT NULL
);

DROP TABLE IF EXISTS xfrm CASCADE;
CREATE TABLE xfrm (
  xfrm_id SERIAL PRIMARY KEY,
  xfrm_commands TEXT NOT NULL,
  xfrm_args jsonb
);

DROP TABLE IF EXISTS url CASCADE;
CREATE TABLE IF NOT EXISTS url (
  url_id SERIAL  NOT NULL,
  url_title varchar(100) NOT NULL,
  url varchar(500) NOT NULL,
  url_lang TEXT DEFAULT 'de' NOT NULL,  -- default 'de' for now
  xfrm_id INT NOT NULL,
  check_frequency_id integer NOT NULL,
  url_creator_user_id integer NOT NULL,
  url_active BOOL NOT NULL DEFAULT true,
  spider_job_id INT,
  PRIMARY KEY (url_id)
) ;

DROP TABLE IF EXISTS check_frequency CASCADE;
CREATE TABLE IF NOT EXISTS check_frequency (
  check_frequency_id integer  NOT NULL,
  check_frequency_text varchar(10) NOT NULL,
  check_frequency_interval BIGINT NOT NULL,
  PRIMARY KEY (check_frequency_id)
) ;

DROP TABLE IF EXISTS url_group CASCADE;
CREATE TABLE IF NOT EXISTS url_group (
  url_group_id SERIAL  NOT NULL,
  parent_url_group_id integer  NULL,
  url_group_title varchar(100) NOT NULL,
  url_group_description varchar(200) NULL,
  url_group_creator_user_id integer  NOT NULL,
  PRIMARY KEY (url_group_id)
) ;

DROP TABLE IF EXISTS url_x_group CASCADE;
CREATE TABLE IF NOT EXISTS url_x_group (
  url_x_group_id SERIAL NOT NULL,
  url_id integer  NOT NULL,
  url_group_id integer  NOT NULL,
  PRIMARY KEY (url_x_group_id)
) ;

DROP TABLE IF EXISTS url_blacklist CASCADE;
CREATE TABLE IF NOT EXISTS url_blacklist (
  url_blacklist_id SERIAL  NOT NULL,
  url_blacklist varchar(100) NOT NULL,
  PRIMARY KEY (url_blacklist_id)
) ;

DROP TABLE IF EXISTS alert CASCADE;
CREATE TABLE IF NOT EXISTS alert (
  alert_id SERIAL NOT NULL,
  alert_title varchar(50) NOT NULL,
  alert_description varchar(200) NULL,
  alert_active BOOL NOT NULL DEFAULT true,
  user_id INTEGER not null,
  PRIMARY KEY (alert_id)
);

DROP TABLE IF EXISTS alert_x_url_group CASCADE;
CREATE TABLE IF NOT EXISTS alert_x_url_group (
  alert_x_url_group_id SERIAL NOT NULL,
  alert_id integer  NOT NULL,
  url_group_id integer  NOT NULL,
  PRIMARY KEY (alert_x_url_group_id)
) ;

DROP TABLE IF EXISTS alert_x_url CASCADE;
CREATE TABLE IF NOT EXISTS alert_x_url (
  alert_x_url_id SERIAL NOT NULL,
  alert_id integer  NOT NULL,
  url_id integer  NOT NULL,
  PRIMARY KEY (alert_x_url_id)
) ;

DROP TABLE IF EXISTS alert_type CASCADE;
CREATE TABLE IF NOT EXISTS alert_type (
  alert_type_id integer  NOT NULL,
  alert_type_name varchar(50) NOT NULL,
  alert_type_description varchar(200) NOT NULL,
  PRIMARY KEY (alert_type_id)
) ;

DROP TABLE IF EXISTS alert_cycle CASCADE;
CREATE TABLE IF NOT EXISTS alert_cycle (
  alert_cycle_id integer  NOT NULL,
  alert_cycle_name varchar(50) NOT NULL,
  alert_cycle_description varchar(200) NOT NULL,
  PRIMARY KEY (alert_cycle_id)
) ;

DROP TABLE IF EXISTS type_x_cycle CASCADE;
CREATE TABLE IF NOT EXISTS type_x_cycle (
  type_x_cycle_id SERIAL NOT NULL,
  alert_type_id integer  NOT NULL,
  alert_cycle_id integer  NOT NULL,
  PRIMARY KEY (type_x_cycle_id)
) ;

DROP TABLE IF EXISTS alert_x_type_cycle CASCADE;
CREATE TABLE IF NOT EXISTS alert_x_type_cycle (
  alert_id INT NOT NULL,
  type_x_cycle_id INT NOT NULL,
  PRIMARY KEY (alert_id, type_x_cycle_id)
);

DROP TABLE IF EXISTS notification CASCADE;
CREATE TABLE notification (
  alert_id INTEGER NOT NULL,
  url_id INT NOT NULL,
  type_x_cycle_id INTEGER NOT NULL,
  old_doc_id BIGINT NOT NULL,
  new_doc_id BIGINT NOT NULL,
  spider_uuid UUID NOT NULL,
  creation_ts TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
  delivery_ts TIMESTAMP WITH TIME ZONE DEFAULT NULL,
  PRIMARY KEY (alert_id, type_x_cycle_id, new_doc_id)
);

DROP TABLE IF EXISTS alert_keyword CASCADE;
CREATE TABLE IF NOT EXISTS alert_keyword (
  alert_keyword_id SERIAL NOT NULL,
  alert_keyword varchar(50) NOT NULL,
  PRIMARY KEY (alert_keyword_id)
) ;

DROP TABLE IF EXISTS alert_x_keyword CASCADE;
CREATE TABLE IF NOT EXISTS alert_x_keyword (
  alert_x_keyword_id SERIAL NOT NULL,
  alert_id integer  NOT NULL,
  alert_keyword_id integer  NOT NULL,
  PRIMARY KEY (alert_x_keyword_id)
) ;

DROP TABLE IF EXISTS alert_release CASCADE;

DROP TABLE IF EXISTS access_control CASCADE;
CREATE TABLE IF NOT EXISTS access_control (
  access_control_id SERIAL  NOT NULL,
  user_id integer NULL,
  user_group_id integer NULL,
  url_id integer NULL,
  url_group_id integer NULL,
  access_type_id integer NOT NULL,
  access_control_valid_from timestamp NOT NULL DEFAULT now(),
  access_control_valid_till timestamp NULL,
  PRIMARY KEY (access_control_id)
) ;


DROP TABLE IF EXISTS access_type CASCADE;
CREATE TABLE IF NOT EXISTS access_type (
  access_type_id integer NOT NULL,
  access_type_name varchar(10) NOT NULL,
  access_type_description varchar(50) NOT NULL,
  PRIMARY KEY (access_type_id)
) ;

DROP VIEW IF EXISTS v_alert_release;
CREATE VIEW v_alert_release AS
  SELECT alert_id, max(delivery_ts) as alert_release_date
  FROM notification
  GROUP BY alert_id;

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


-- Helpful aggregate for grouping multiple changes. Merges insert and
-- update to insert. Assumes deletes are final and later inserts (to
-- the same job) aren't possible. That's sufficient for
-- spider_changes.
CREATE OR REPLACE FUNCTION change_type_agg_sfunc(a change_type_enum, b change_type_enum)
RETURNS change_type_enum
AS $$
  SELECT (CASE WHEN a = 'delete' OR b = 'delete'
    THEN 'delete'
    ELSE CASE WHEN a = 'insert' OR b = 'insert'
      THEN 'insert'
      ELSE 'update'
    END
  END)::change_type_enum;
$$ LANGUAGE SQL IMMUTABLE;

DROP AGGREGATE IF EXISTS change_type_agg(change_type_enum);
CREATE AGGREGATE change_type_agg(change_type_enum)
(
  sfunc = change_type_agg_sfunc,
  stype = change_type_enum,
  initcond = 'update'
);

DROP VIEW IF EXISTS spider_job_change;
CREATE VIEW spider_job_change AS
  SELECT j.job_id, j.url, j.url_lang, j.min_check_interval,
         job_spider_uuid, job_active,
         extract('epoch' FROM now() - j.last_check_ts) AS age,
         c.change_id, c.change_type FROM spider_job j
  JOIN spider_change c ON c.job_id = j.job_id
  ORDER BY j.job_id, c.change_id;

DROP TABLE IF EXISTS cfg CASCADE;
CREATE TABLE IF NOT EXISTS cfg (
  cfg_id SERIAL  NOT NULL,
  cfg_name varchar(255) NOT NULL,
  cfg_value varchar(255) NOT NULL,
  PRIMARY KEY (cfg_id)
) ;


-- Definitions of active and inactive backends: new jobs can be
-- assigned to active backends, those in between don't lose their
-- jobs, yet, but don't get any new ones. And once a backend becomes
-- inactive, others take over their jobs. (Heartbeat interval of the
-- backend is 30 seconds.)
DROP VIEW IF EXISTS spider_status;
CREATE VIEW spider_status AS
  SELECT
    spider_uuid AS uuid,
    now() - spider_last_seen > '300 seconds'::interval AS inactive,
    now() - spider_last_seen > '60 seconds'::interval AS suspected
  FROM spider
  ORDER BY inactive, suspected, random();

-- Shows only the last document per job_id, xfrm_id pair.
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
