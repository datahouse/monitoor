-- structure
DROP TABLE user_x_group;
ALTER TABLE mon_user ADD COLUMN user_group_id INT NULL REFERENCES user_group(user_group_id);
ALTER TABLE mon_user ADD COLUMN is_group_admin BOOLEAN NOT NULL DEFAULT false;

ALTER TABLE url_group ADD COLUMN subscription_price INT NULL;

ALTER TABLE url_group ADD COLUMN billable_only boolean NOT NULL DEFAULT false;

-- user grouping data see #4034 or ask pem

-- new channels updaten UPDATE url_group SET is_subscription WHERE url_group_id =;

-- DEMO
-- UPDATE url_group SET billable_only = true WHERE url_group_id = 126;
-- UPDATE url_group SET subscription_price = 35 WHERE url_group_id = 126;

-- LIVE
-- UPDATE url_group SET billable_only = true WHERE url_group_id = 268;
-- UPDATE url_group SET subscription_price = 35 WHERE url_group_id = 268;

DROP TABLE IF EXISTS user_subscription CASCADE;
DROP TYPE IF EXISTS action;
CREATE TYPE action AS ENUM ('subscribe', 'unsubscribe');
CREATE TABLE IF NOT EXISTS user_subscription (
  user_subscription_id SERIAL NOT NULL,
  user_id INTEGER NOT NULL,
  url_group_id INTEGER NOT NULL,
  subscription_ts timestamp NOT NULL DEFAULT NOW(),
  user_action action NOT NULL,
  PRIMARY KEY (user_subscription_id)
) ;

ALTER TABLE user_subscription
  ADD CONSTRAINT user_fk FOREIGN KEY (user_id)
    REFERENCES mon_user (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT url_group_fk FOREIGN KEY (url_group_id)
    REFERENCES url_group (url_group_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

DROP FUNCTION IF EXISTS subscribe(INT, INT);
CREATE OR REPLACE FUNCTION subscribe(groupid INT, userid INT, alert_cycle INT)
RETURNS void
AS $$
DECLARE
  alertid INT;

BEGIN

  INSERT INTO alert(user_id, alert_option_id) VALUES (userid, 1) returning alert_id into alertid;
  INSERT INTO alert_x_url_group(alert_id, url_group_id) VALUES (alertid, groupid);
  INSERT INTO alert_x_type_cycle (type_x_cycle_id, alert_id) VALUES (alert_cycle, alertid);

  INSERT INTO access_control (user_id, url_group_id, access_type_id, access_control_valid_from)
    VALUES (userid, groupid, 1, NOW());

  INSERT INTO access_control (user_id, url_id, access_type_id, access_control_valid_from)
    SELECT userid, url_id, 1, NOW() FROM url WHERE url_group_id = groupid;

  INSERT INTO user_subscription (user_id, url_group_id, user_action) VALUES (userid, groupid, 'subscribe');

END
$$ LANGUAGE plpgsql;

DROP FUNCTION IF EXISTS unsubscribe(INT, INT);
CREATE OR REPLACE FUNCTION unsubscribe(groupid INT, userid INT)
RETURNS void
AS $$
DECLARE
  alertid INT;
BEGIN
  FOR alertid IN SELECT a.alert_id
    FROM alert_x_url_group x JOIN alert a ON (a.alert_id=x.alert_id)
    WHERE url_group_id=groupid AND user_id=userid AND alert_active
     LOOP
     UPDATE alert SET alert_active = false WHERE alert_id = alertid;
     DELETE FROM alert_x_url_group WHERE alert_id = alertid AND url_group_id = groupid;
     DELETE FROM alert_x_type_cycle WHERE alert_id = alertid;
     UPDATE alert_keyword SET alert_keyword_active = false WHERE alert_id = alertid;
    END LOOP;

  DELETE FROM access_control
   WHERE user_id=userid AND url_id IN (SELECT url_id FROM url
    WHERE url_group_id=groupid) AND access_type_id = 1;

  DELETE FROM access_control
   WHERE user_id=userid AND url_group_id = groupid AND access_type_id = 1;

  INSERT INTO user_subscription (user_id, url_group_id, user_action) VALUES (userid, groupid, 'unsubscribe');

END
$$ LANGUAGE plpgsql;

DROP view v_change;

ALTER table url alter column url type text;

CREATE OR REPLACE VIEW v_change AS
  SELECT
    n.alert_id,
    n.type_x_cycle_id,
    c.change_id,
    c.new_doc_id,
    c.old_doc_id,
    c.delta,
    c.ts AS creation_ts,
    cu.url_id,
    u.url,
    u.url_title,
    g.url_group_id,
    g.url_group_title,
    a.user_id
  FROM notification n
  JOIN change c ON n.change_id = c.change_id
  JOIN change_x_url cu ON c.change_id = cu.change_id
  JOIN alert a ON a.alert_id = n.alert_id
  JOIN url u ON u.url_id = cu.url_id
  JOIN url_group g ON u.url_group_id = g.url_group_id
  JOIN alert_x_url_group aug
    ON a.alert_id = aug.alert_id AND g.url_group_id = aug.url_group_id
  WHERE a.alert_active = true AND n.is_retained = false
  GROUP BY n.alert_id, n.type_x_cycle_id, a.user_id, c.change_id,
    c.new_doc_id, cu.url_id, u.url, u.url_title, c.old_doc_id, c.delta,
    g.url_group_id, g.url_group_title;
