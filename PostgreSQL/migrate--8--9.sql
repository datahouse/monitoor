CREATE TABLE IF NOT EXISTS notification_x_keyword (
  alert_id INT NOT NULL,
  doc_id BIGINT NOT NULL,
  alert_keyword_id INT NOT NULL,
  PRIMARY KEY (alert_id, doc_id, alert_keyword_id)
);

DROP TABLE IF EXISTS spider_load CASCADE;
CREATE TABLE spider_load (
  spider_uuid UUID NOT NULL,
  ts TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
  load_one FLOAT NOT NULL,
  load_five FLOAT NOT NULL,
  load_fifteen FLOAT NOT NULL,
  cpu_user_time FLOAT NOT NULL,
  cpu_nice_time FLOAT NOT NULL,
  cpu_system_time FLOAT NOT NULL,
  cpu_idle_time FLOAT NOT NULL,
  cpu_iowait_time FLOAT NOT NULL,
  cpu_irq_time FLOAT NOT NULL,
  bytes_sent BIGINT NOT NULL,
  bytes_recv BIGINT NOT NULL,
  packets_sent BIGINT NOT NULL,
  packets_recv BIGINT NOT NULL,
  PRIMARY KEY (spider_uuid, ts)
);

ALTER TABLE spider
  ADD COLUMN spider_last_hostname TEXT;

UPDATE spider SET spider_last_hostname = '';

ALTER TABLE spider
  ALTER COLUMN spider_last_hostname SET NOT NULL;

INSERT INTO alert_type (alert_type_id, alert_type_name, alert_type_description)
VALUES
    (3, 'keine', 'no notification');

INSERT INTO alert_cycle (alert_cycle_id, alert_cycle_name,
                         alert_cycle_description)
VALUES
    (3, 'online', 'online only');

INSERT INTO type_x_cycle (alert_type_id, alert_cycle_id)
VALUES
    (3, 3);

-- This is assuming nobody else inserted an xfrm command. Maybe safe
-- enough, as we require manual insertion, up until now.
INSERT INTO xfrm (xfrm_id, xfrm_commands, xfrm_args)
  VALUES (2, 'html2markdown', '{}'),
         (3, 'xpath|html2markdown', '{"xpath": "//body"}');
SELECT setval('xfrm_xfrm_id_seq', 3, true);

update url set xfrm_id=3;

alter table type_x_cycle add column is_active BOOL DEFAULT true NOT NULL;

update type_x_cycle set is_active = false where type_x_cycle_id=1;

DROP VIEW IF EXISTS v_alert_url_u_group;
CREATE OR REPLACE VIEW v_alert_url_u_group AS
  SELECT a.alert_id, a.user_id, g.url_group_id,
         g.url_group_title, a.alert_active
  FROM alert a
  JOIN alert_x_url_group x ON a.alert_id = x.alert_id
  JOIN url_group g ON x.url_group_id = g.url_group_id;

DROP VIEW IF EXISTS v_change;

alter table alert drop column alert_title;
alter table alert drop column alert_description;

update cfg set cfg_value = 's:4:"url/";' where cfg_name='alert_url';
insert into cfg (cfg_name, cfg_value) VALUES ('url_groups', 's:10:"urlGroups/";');
insert into cfg (cfg_name, cfg_value) VALUES ('alert_setting_url', 's:12:"alerts/edit/";');

DROP VIEW IF EXISTS spider_job_alert_type_cycle;
drop table alert_x_url;

CREATE VIEW spider_job_alert_type_cycle AS
  SELECT j.job_id, url.url_id, url.url_active,
         axu.alert_id, alert.alert_active, xtc.type_x_cycle_id,
         alert_group.url_group_id AS via_group_id
    FROM spider_job j
    INNER JOIN url ON url.spider_job_id = j.job_id
    INNER JOIN url_x_group url_group ON url_group.url_id = url.url_id
    INNER JOIN url_group_children alert_group
            ON url_group.url_group_id = ANY(alert_group.child_ids)
    INNER JOIN alert_x_url_group axu ON axu.url_group_id = alert_group.url_group_id
    INNER JOIN alert_x_type_cycle xtc ON xtc.alert_id = axu.alert_id
	INNER JOIN alert ON axu.alert_id = alert.alert_id;

	DROP VIEW v_alert_release;



DROP TABLE IF EXISTS rating_value CASCADE;
CREATE TABLE IF NOT EXISTS rating_value (
  rating_value_id integer  NOT NULL,
  rating_value_desc varchar(50) NOT NULL,
  PRIMARY KEY (rating_value_id)
) ;

DROP TABLE IF EXISTS rating CASCADE;
CREATE TABLE IF NOT EXISTS rating (
  alert_id INT NOT NULL,
  new_doc_id BIGINT NOT NULL,
  user_id INT NOT NULL,
  rating_value_id INT NOT NULL,
  PRIMARY KEY (alert_id, new_doc_id, user_id)
);

ALTER TABLE rating
  ADD CONSTRAINT usr_fk FOREIGN KEY (user_id)
    REFERENCES mon_user (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT alert_fk FOREIGN KEY (alert_id)
    REFERENCES alert (alert_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT doc_fk FOREIGN KEY (new_doc_id)
    REFERENCES spider_document (spider_document_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT rating_value_fk FOREIGN KEY (rating_value_id)
    REFERENCES rating_value (rating_value_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

insert into rating_value (rating_value_id, rating_value_desc)
VALUES
    (1, 'very poor'),
    (2, 'poor'),
    (3, 'average'),
    (4, 'good'),
    (5, 'very good');

update cfg set cfg_value = 's:21:"monitoor@datahouse.ch";' where  cfg_name = 'email_from';

CREATE VIEW v_change AS
SELECT n.alert_id, n.new_doc_id, n.old_doc_id, MIN(n.creation_ts) as creation_ts,
n.url_id, u.url, u.url_title, g.url_group_id, g.url_group_title, a.user_id, r.rating_value_id
FROM notification n JOIN alert a ON (a.alert_id = n.alert_id)
JOIN url u ON (u.url_id = n.url_id)
LEFT JOIN url_x_group ug ON (ug.url_id = u.url_id)
LEFT JOIN url_group g ON (ug.url_group_id = g.url_group_id)
LEFT JOIN access_control acc ON (a.user_id = acc.user_id AND acc.url_group_id = ug.url_group_id)
LEFT JOIN rating r ON a.user_id=r.user_id AND a.alert_id=r.alert_id AND n.new_doc_id = r.new_doc_id
WHERE a.alert_active = TRUE
GROUP BY n.alert_id, a.user_id,n.new_doc_id, n.url_id, u.url, u.url_title, n.old_doc_id,
 g.url_group_id, g.url_group_title, r.rating_value_id;
