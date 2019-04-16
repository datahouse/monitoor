ALTER TABLE notification
  ADD COLUMN type_x_cycle_id INT REFERENCES type_x_cycle(type_x_cycle_id),
  ADD COLUMN spider_uuid UUID REFERENCES spider(spider_uuid),
  ADD COLUMN delivery_ts TIMESTAMP WITH TIME ZONE DEFAULT NULL,
  ADD COLUMN url_id INT REFERENCES url(url_id);

ALTER TABLE notification
  RENAME COLUMN ts TO creation_ts;

-- fake data, but good enough during development
INSERT INTO spider (spider_uuid, spider_last_seen)
  VALUES ('deadbeef-0b92-461f-aa5e-5872a6ce8057', '1970-01-01 00:00:00');

-- fill with a somewhat random default, and now() as delivery_ts
-- wherever delivered is true, already.
UPDATE notification n SET
  type_x_cycle_id = (SELECT type_x_cycle_id FROM type_x_cycle LIMIT 1),
  spider_uuid = (SELECT spider_uuid FROM spider LIMIT 1),
  url_id = (SELECT url_id FROM alert_x_url x WHERE x.alert_id = n.alert_id LIMIT 1);

UPDATE notification SET delivery_ts = now()
  WHERE delivered;

ALTER TABLE notification
  DROP COLUMN delivered,
  DROP CONSTRAINT notification_pkey,
  ALTER COLUMN type_x_cycle_id SET NOT NULL,
  ALTER COLUMN spider_uuid SET NOT NULL,
  ALTER COLUMN url_id SET NOT NULL,
  ADD PRIMARY KEY (alert_id, type_x_cycle_id, new_doc_id);

ALTER TABLE alert
  ADD COLUMN alert_active BOOL NOT NULL DEFAULT true;

ALTER TABLE alert_x_type_cycle
  DROP CONSTRAINT alert_x_type_cycle_pkey,
  DROP CONSTRAINT alert_x_type_cycle_alert_id_type_x_cycle_id_key,
  DROP COLUMN alert_x_type_cycle_id,
  ADD PRIMARY KEY (alert_id, type_x_cycle_id),
  DROP CONSTRAINT alertid_fk,  -- these last 2 for naming consistency
  ADD CONSTRAINT alert_id_fk FOREIGN KEY (alert_id)
    REFERENCES alert (alert_id) ON DELETE CASCADE ON UPDATE CASCADE;


DROP VIEW IF EXISTS v_alert_url_u_group;
DROP VIEW IF EXISTS v_alert_release;

CREATE VIEW v_alert_release AS
  SELECT alert_id, max(delivery_ts) as alert_release_date
  FROM notification
  GROUP BY alert_id;

-- v_alert_url_u_group only recreated, as it depends on v_alert_release
CREATE VIEW v_alert_url_u_group AS
SELECT a.alert_id, a.alert_title, a.user_id, u.url_id, u.url_title, r.alert_release_date, a.alert_active FROM alert a JOIN alert_x_url x ON (a.alert_id=x.alert_id) JOIN url u ON (x.url_id = u.url_id) LEFT JOIN v_alert_release r ON (a.alert_id=r.alert_id)
UNION ALL
SELECT a.alert_id, a.alert_title, a.user_id, g.url_group_id, g.url_group_title, r.alert_release_date, a.alert_active FROM alert a JOIN alert_x_url_group x ON (a.alert_id=x.alert_id) JOIN url_group g ON (x.url_group_id = g.url_group_id) LEFT JOIN v_alert_release r ON (a.alert_id=r.alert_id);


DROP TABLE alert_release;
