DROP INDEX alert_x_url_group_url_group_id_idx;

ALTER TABLE alert_x_url_group
  DROP CONSTRAINT alert_x_url_group_alert_id_url_group_id_key,
  ADD CONSTRAINT alert_x_url_group_alert_id_url_group_id_key
    UNIQUE (url_group_id, alert_id);

CREATE INDEX url_url_group_id_idx
  ON url(url_group_id);

CREATE INDEX url_group_creator_user_id_idx
  ON url_group(url_group_creator_user_id );

CREATE INDEX access_control_url_id_idx
  ON access_control(url_id);
CREATE INDEX access_control_url_group_id_idx
  ON access_control(url_group_id);

ALTER TABLE alert_x_type_cycle
  DROP CONSTRAINT alert_x_type_cycle_key;

CREATE INDEX change_x_url_change_id_idx
  ON change_x_url(change_id);

CREATE INDEX alert_active_user_id_idx
  ON alert(user_id)
  WHERE alert_active;

CREATE INDEX alert_x_url_group_alert_id_idx
  ON alert_x_url_group(alert_id);
