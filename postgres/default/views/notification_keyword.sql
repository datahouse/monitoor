CREATE OR REPLACE VIEW notification_keyword AS
  SELECT n.alert_id, n.change_id, n.type_x_cycle_id,
    n.delivery_ts, n.push_ts, n.is_retained,
    kw.alert_keyword_id, kw.alert_keyword, kw.alert_keyword_active
  FROM notification n
  LEFT JOIN notification_x_keyword nk
    ON n.alert_id = nk.alert_id
    AND n.change_id = nk.change_id
    AND n.type_x_cycle_id = nk.type_x_cycle_id
  LEFT JOIN alert_keyword kw
    ON kw.alert_keyword_id = nk.alert_keyword_id;

-- FIXME: shouldn't belong to the app user
ALTER VIEW notification_keyword
  OWNER TO project_mon;

