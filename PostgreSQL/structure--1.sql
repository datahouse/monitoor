DROP TABLE IF EXISTS mon_user CASCADE;
CREATE TABLE IF NOT EXISTS mon_user (
  user_id SERIAL NOT NULL,
  user_email varchar(100) NOT NULL,
  user_password char(64) NULL,
  user_password_salt char(16) NULL,
  user_valid_from timestamp NOT NULL,
  user_valid_till timestamp NULL,
  user_last_login timestamp NULL,
  PRIMARY KEY (user_id),
  UNIQUE (user_email)
) ;

DROP TABLE IF EXISTS user_pw_recovery CASCADE;
CREATE TABLE IF NOT EXISTS user_pw_recovery (
  user_pw_recovery_id SERIAL NOT NULL,
  user_id INTEGER NOT NULL,
  user_pw_recovery_created timestamp NOT NULL,
  user_pw_recovery_used timestamp NULL,
  user_pw_recovery_hash char(40) NOT NULL,
  PRIMARY KEY (user_pw_recovery_id),
  UNIQUE (user_pw_recovery_hash)
) ;

DROP TABLE IF EXISTS account CASCADE;
CREATE TABLE IF NOT EXISTS account (
  account_id SERIAL NOT NULL,
  user_id INTEGER NOT NULL,
  account_name_first varchar(255) NOT NULL,
  account_name_last varchar(255) NOT NULL,
  account_mobile varchar(20) NULL,
  PRIMARY KEY (account_id),
  UNIQUE (user_id)
) ;

ALTER TABLE account
ADD CONSTRAINT userid_fk FOREIGN KEY (user_id) REFERENCES mon_user (user_id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE user_pw_recovery
ADD CONSTRAINT usrid_fk FOREIGN KEY (user_id) REFERENCES mon_user (user_id) ON DELETE CASCADE ON UPDATE CASCADE;

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
  PRIMARY KEY (user_x_group_id),
   UNIQUE (user_id, user_group_id)
) ;

ALTER TABLE user_x_group
ADD CONSTRAINT user_fk FOREIGN KEY (user_id) REFERENCES mon_user (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT user_group_fk FOREIGN KEY (user_group_id) REFERENCES user_group (user_group_id) ON DELETE CASCADE ON UPDATE CASCADE;


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
  PRIMARY KEY (user_x_role_id),
  UNIQUE (user_id, role_id)
) ;

ALTER TABLE user_x_role
ADD CONSTRAINT userid_fk FOREIGN KEY (user_id) REFERENCES mon_user (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT role_fk FOREIGN KEY (role_id) REFERENCES role (role_id) ON DELETE CASCADE ON UPDATE CASCADE;


DROP TABLE IF EXISTS url CASCADE;
CREATE TABLE IF NOT EXISTS url (
  url_id SERIAL  NOT NULL,
  url_title varchar(100) NOT NULL,
  url varchar(200) NOT NULL,
  check_frequency_id integer NULL,
  url_creator_user_id integer NOT NULL,
  PRIMARY KEY (url_id)
) ;


DROP TABLE IF EXISTS check_frequency CASCADE;
CREATE TABLE IF NOT EXISTS check_frequency (
  check_frequency_id integer  NOT NULL,
  check_frequency_text varchar(10) NOT NULL,
  PRIMARY KEY (check_frequency_id)
) ;

ALTER TABLE url
ADD CONSTRAINT user_url_fk FOREIGN KEY (url_creator_user_id) REFERENCES mon_user (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT feq_fk FOREIGN KEY (check_frequency_id) REFERENCES check_frequency (check_frequency_id) ON DELETE CASCADE ON UPDATE CASCADE;

DROP TABLE IF EXISTS url_group CASCADE;
CREATE TABLE IF NOT EXISTS url_group (
  url_group_id SERIAL  NOT NULL,
  parent_url_group_id integer  NULL,
  url_group_title varchar(100) NOT NULL,
  url_group_description varchar(200) NULL,
  url_group_creator_user_id integer  NOT NULL,
  PRIMARY KEY (url_group_id)
) ;

ALTER TABLE url_group
ADD CONSTRAINT group_group_fk FOREIGN KEY (parent_url_group_id) REFERENCES url_group (url_group_id) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT usr_urlgrp_fk FOREIGN KEY (url_group_creator_user_id) REFERENCES mon_user (user_id) ON DELETE CASCADE ON UPDATE CASCADE;

DROP TABLE IF EXISTS url_x_group CASCADE;
CREATE TABLE IF NOT EXISTS url_x_group (
  url_x_group_id SERIAL NOT NULL,
  url_id integer  NOT NULL,
  url_group_id integer  NOT NULL,
  PRIMARY KEY (url_x_group_id),
  UNIQUE (url_id, url_group_id)
) ;

ALTER TABLE url_x_group
ADD CONSTRAINT urlid_fk FOREIGN KEY (url_id) REFERENCES url (url_id) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT urlgroup_fk FOREIGN KEY (url_group_id) REFERENCES url_group (url_group_id) ON DELETE CASCADE ON UPDATE CASCADE;

DROP TABLE IF EXISTS url_blacklist CASCADE;
CREATE TABLE IF NOT EXISTS url_blacklist (
  url_blacklist_id SERIAL  NOT NULL,
  url_blacklist varchar(100) NOT NULL,
  PRIMARY KEY (url_blacklist_id)
) ;

DROP TABLE IF EXISTS alert CASCADE;
CREATE TABLE IF NOT EXISTS alert (
  alert_id SERIAL  NOT NULL,
  alert_title varchar(50) NOT NULL,
  alert_description varchar(200) NULL,
  user_id INTEGER not null,
  PRIMARY KEY (alert_id)
) ;

ALTER TABLE alert
ADD CONSTRAINT alert_user_fk FOREIGN KEY (user_id) REFERENCES mon_user (user_id) ON DELETE CASCADE ON UPDATE CASCADE;

DROP TABLE IF EXISTS alert_x_url_group CASCADE;
CREATE TABLE IF NOT EXISTS alert_x_url_group (
  alert_x_url_group_id SERIAL NOT NULL,
  alert_id integer  NOT NULL,
  url_group_id integer  NOT NULL,
  PRIMARY KEY (alert_x_url_group_id),
  UNIQUE (alert_id, url_group_id)
) ;

ALTER TABLE alert_x_url_group
ADD CONSTRAINT alertid_fk FOREIGN KEY (alert_id) REFERENCES alert (alert_id) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT url_group_fk FOREIGN KEY (url_group_id) REFERENCES url_group (url_group_id) ON DELETE CASCADE ON UPDATE CASCADE;

DROP TABLE IF EXISTS alert_x_url CASCADE;
CREATE TABLE IF NOT EXISTS alert_x_url (
  alert_x_url_id SERIAL NOT NULL,
  alert_id integer  NOT NULL,
  url_id integer  NOT NULL,
  PRIMARY KEY (alert_x_url_id),
  UNIQUE (alert_id, url_id)
) ;

ALTER TABLE alert_x_url
ADD CONSTRAINT alert_id_fk FOREIGN KEY (alert_id) REFERENCES alert (alert_id) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT url_fk FOREIGN KEY (url_id) REFERENCES url (url_id) ON DELETE CASCADE ON UPDATE CASCADE;


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
  PRIMARY KEY (type_x_cycle_id),
  UNIQUE (alert_type_id, alert_cycle_id)
) ;

ALTER TABLE type_x_cycle
ADD CONSTRAINT alert_type_id_fk FOREIGN KEY (alert_type_id) REFERENCES alert_type (alert_type_id) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT alert_cycle_id_fk FOREIGN KEY (alert_cycle_id) REFERENCES alert_cycle (alert_cycle_id) ON DELETE CASCADE ON UPDATE CASCADE;

DROP TABLE IF EXISTS alert_x_type_cycle CASCADE;
CREATE TABLE IF NOT EXISTS alert_x_type_cycle (
  alert_x_type_cycle_id SERIAL NOT NULL,
  alert_id integer  NOT NULL,
  type_x_cycle_id integer  NOT NULL,
  PRIMARY KEY (alert_x_type_cycle_id),
  UNIQUE (alert_id, type_x_cycle_id)
) ;

ALTER TABLE alert_x_type_cycle
ADD CONSTRAINT alertid_fk FOREIGN KEY (alert_id) REFERENCES alert (alert_id) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT alert_type_fk FOREIGN KEY (type_x_cycle_id) REFERENCES type_x_cycle (type_x_cycle_id) ON DELETE CASCADE ON UPDATE CASCADE;

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
  PRIMARY KEY (alert_x_keyword_id),
   UNIQUE (alert_id, alert_keyword_id)
) ;

ALTER TABLE alert_x_keyword
ADD CONSTRAINT a_id_fk FOREIGN KEY (alert_id) REFERENCES alert (alert_id) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT keyword_fk FOREIGN KEY (alert_keyword_id) REFERENCES alert_keyword (alert_keyword_id) ON DELETE CASCADE ON UPDATE CASCADE;


DROP TABLE IF EXISTS alert_release CASCADE;
CREATE TABLE IF NOT EXISTS alert_release (
  alert_release_id SERIAL  NOT NULL,
  alert_id integer NOT NULL,
  type_x_cycle_id integer NOT NULL,
  alert_release_date timestamp NOT NULL,
  PRIMARY KEY (alert_release_id)
) ;

ALTER TABLE alert_release
ADD CONSTRAINT a__id_fk FOREIGN KEY (alert_id) REFERENCES alert (alert_id) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT alerttype_id_fk FOREIGN KEY (type_x_cycle_id) REFERENCES type_x_cycle (type_x_cycle_id) ON DELETE CASCADE ON UPDATE CASCADE;


DROP TABLE IF EXISTS access_control CASCADE;
CREATE TABLE IF NOT EXISTS access_control (
  access_control_id SERIAL  NOT NULL,
  user_id integer NULL,
  user_group_id integer NULL,
  url_id integer NULL,
  url_group_id integer NULL,
  access_type_id integer NOT NULL,
  access_control_valid_from timestamp NOT NULL,
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

ALTER TABLE access_control
ADD CONSTRAINT acc_typ_fk FOREIGN KEY (access_type_id) REFERENCES access_type (access_type_id) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT usr_fk FOREIGN KEY (user_id) REFERENCES mon_user (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT ursr_grp_fk FOREIGN KEY (user_group_id) REFERENCES user_group (user_group_id) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT url_fk FOREIGN KEY (url_id) REFERENCES url (url_id) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT url_grp_fk FOREIGN KEY (url_group_id) REFERENCES url_group (url_group_id) ON DELETE CASCADE ON UPDATE CASCADE;

DROP VIEW IF EXISTS v_alert_release;
CREATE VIEW v_alert_release AS SELECT alert_id, max(alert_release_date) as alert_release_date FROM alert_release GROUP BY alert_id;

DROP TABLE IF EXISTS spider CASCADE;
CREATE TABLE spider (
  spider_uuid UUID NOT NULL,
  spider_last_seen TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
  PRIMARY KEY (spider_uuid)
);

DROP VIEW IF EXISTS v_alert_url_u_group;
CREATE VIEW v_alert_url_u_group AS
SELECT a.alert_id, a.alert_title, a.user_id, u.url_id, u.url_title, r.alert_release_date FROM alert a JOIN alert_x_url x ON (a.alert_id=x.alert_id) JOIN url u ON (x.url_id = u.url_id) LEFT JOIN v_alert_release r ON (a.alert_id=r.alert_id)
UNION ALL
SELECT a.alert_id, a.alert_title, a.user_id, g.url_group_id, g.url_group_title, r.alert_release_date FROM alert a JOIN alert_x_url_group x ON (a.alert_id=x.alert_id) JOIN url_group g ON (x.url_group_id = g.url_group_id) LEFT JOIN v_alert_release r ON (a.alert_id=r.alert_id);
