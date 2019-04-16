SET search_path TO public, thirdparty;

CREATE OR REPLACE VIEW v_alert_url_u_group AS
  SELECT a.alert_id, a.user_id, g.url_group_id,
         g.url_group_title, a.alert_active
  FROM alert a
  JOIN alert_x_url_group x ON a.alert_id = x.alert_id
  JOIN url_group g ON x.url_group_id = g.url_group_id;

-- FIXME: shouldn't belong to the app user
ALTER VIEW v_alert_url_u_group
  OWNER TO project_mon;

