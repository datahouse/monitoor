DROP TABLE IF EXISTS user_activation CASCADE;
CREATE TABLE IF NOT EXISTS user_activation (
  user_activation_id SERIAL NOT NULL,
  user_id INTEGER NOT NULL,
  user_activation_created timestamp NOT NULL,
  user_activation_used timestamp NULL,
  user_activation_hash char(40) NOT NULL,
  PRIMARY KEY (user_activation_id)
) ;

ALTER TABLE mon_user ADD COLUMN user_activated BOOL NOT NULL DEFAULT false;

UPDATE mon_user set user_activated = true;

ALTER TABLE user_activation
  ADD CONSTRAINT user_activation_hash_key
    UNIQUE (user_activation_hash),
  ADD CONSTRAINT user_activation_id_key
    UNIQUE (user_activation_id);

ALTER TABLE user_activation
  ADD CONSTRAINT usrid_fk FOREIGN KEY (user_id)
    REFERENCES mon_user (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE;


INSERT INTO cfg (cfg_name, cfg_value) VALUES ('activation_url','s:9:"activate/";');

DROP TABLE IF EXISTS alert_option CASCADE;
CREATE TABLE IF NOT EXISTS alert_option (
  alert_option_id integer  NOT NULL,
  alert_option_name varchar(50) NOT NULL,
  alert_option_description varchar(200) NOT NULL,
  PRIMARY KEY (alert_option_id)
) ;

INSERT INTO alert_option (alert_option_id, alert_option_name, alert_option_description)
VALUES
    (1, 'Activity', 'Activity option - all changes'),
    (2, 'Keywords', 'Keyword option');

ALTER TABLE alert_x_type_cycle
  ADD CONSTRAINT alert_x_type_cycle_key
    UNIQUE (alert_id, type_x_cycle_id);


-- add the url_id to the primary key of notification (as per #2207).
ALTER INDEX notification_pkey RENAME TO notification_old_pkey;
CREATE UNIQUE INDEX notification_pkey
  ON notification (alert_id, url_id, type_x_cycle_id, new_doc_id);
ALTER TABLE notification
  DROP CONSTRAINT notification_old_pkey,
  ADD PRIMARY KEY USING INDEX notification_pkey,
  ADD COLUMN is_retained BOOLEAN NOT NULL DEFAULT False;

-- new index on notifications for a quicker scan for the latest
-- notification on a specific alert / url.
CREATE INDEX notification_creation_ts_idx
    ON notification (alert_id, url_id, type_x_cycle_id, creation_ts);

DROP VIEW IF EXISTS v_change;
CREATE OR REPLACE VIEW v_change AS
  SELECT n.alert_id, n.new_doc_id,
         n.old_doc_id, MIN(n.creation_ts) AS creation_ts, n.url_id, u.url,
         u.url_title, g.url_group_id, g.url_group_title, a.user_id,
         r.rating_value_id
    FROM notification n
    INNER JOIN alert a ON (a.alert_id = n.alert_id)
    INNER JOIN url u ON (u.url_id = n.url_id)
    INNER JOIN url_group g ON (u.url_group_id = g.url_group_id)
    LEFT JOIN access_control acc
      ON (a.user_id = acc.user_id AND acc.url_group_id = g.url_group_id)
    LEFT JOIN rating r
      ON a.user_id=r.user_id
        AND a.alert_id=r.alert_id
        AND n.new_doc_id = r.new_doc_id
    WHERE a.alert_active = TRUE
        AND n.is_retained = FALSE
    GROUP BY n.alert_id, a.user_id,n.new_doc_id, n.url_id, u.url, u.url_title,
             n.old_doc_id, g.url_group_id, g.url_group_title,
             r.rating_value_id;


-- Extend notification_x_keyword to match the primary key of the
-- notification table, again.
ALTER TABLE notification_x_keyword
  RENAME COLUMN doc_id TO new_doc_id;

ALTER TABLE notification_x_keyword
  ADD COLUMN url_id INT,
  ADD COLUMN type_x_cycle_id INT;

UPDATE notification_x_keyword
  SET url_id = n.url_id,
      type_x_cycle_id = n.type_x_cycle_id
FROM notification n
WHERE n.alert_id = notification_x_keyword.alert_id
  AND n.new_doc_id = notification_x_keyword.new_doc_id;

ALTER INDEX notification_x_keyword_pkey
  RENAME TO notification_x_keyword_old_pkey;
CREATE UNIQUE INDEX notification_x_keyword_pkey
  ON notification_x_keyword(alert_id, url_id, type_x_cycle_id,
                            new_doc_id, alert_keyword_id);

ALTER TABLE notification_x_keyword
  ALTER COLUMN url_id SET NOT NULL,
  ALTER COLUMN type_x_cycle_id SET NOT NULL,
  DROP CONSTRAINT notification_x_keyword_old_pkey,
  ADD PRIMARY KEY USING INDEX notification_x_keyword_pkey,
  ADD CONSTRAINT notification_x_keyword_notification_fk
    FOREIGN KEY (alert_id, url_id, type_x_cycle_id, new_doc_id)
    REFERENCES notification(alert_id, url_id, type_x_cycle_id, new_doc_id);
