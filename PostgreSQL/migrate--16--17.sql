DROP TABLE IF EXISTS voucher CASCADE;
CREATE TABLE IF NOT EXISTS voucher (
  voucher_id SERIAL NOT NULL,
  voucher_code char(8) NOT NULL,
  voucher_used timestamp NULL,
  PRIMARY KEY (voucher_id)
) ;

ALTER TABLE account ADD COLUMN voucher_id integer NULL;

ALTER TABLE account
  ADD CONSTRAINT voucher_fk FOREIGN KEY (voucher_id)
    REFERENCES voucher (voucher_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE voucher
  ADD CONSTRAINT voucher_code
    UNIQUE (voucher_code);

ALTER TABLE url_group ADD COLUMN is_widget boolean NOT NULL DEFAULT false;

INSERT INTO pricing_plan (pricing_plan_id, pricing_plan_text, pricing_plan_sort_order)
VALUES (4, 'Widget', 4);

ALTER TABLE account ALTER COLUMN account_name_first DROP NOT NULL;
ALTER TABLE account ALTER COLUMN account_name_last DROP NOT NULL;

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

END
$$ LANGUAGE plpgsql;