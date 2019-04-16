SET search_path TO public, thirdparty;

DROP FUNCTION IF EXISTS subscribe(INT, INT);
DROP FUNCTION IF EXISTS subscribe(INT, INT, INT);
DROP FUNCTION IF EXISTS subscribe(INT, INT, INT, INT);
CREATE OR REPLACE FUNCTION subscribe(groupid INT, userid INT, alert_cycle INT, urlid INT=NULL)
RETURNS void
AS $$
DECLARE
  alertid INT;

BEGIN

  SELECT a.alert_id INTO alertid
	FROM alert a, alert_x_url_group b WHERE a.alert_id=b.alert_id AND user_id=userid AND url_group_id = groupid;
  IF alertid IS NULL THEN
	  INSERT INTO alert(user_id, alert_option_id) VALUES (userid, 1) returning alert_id into alertid;
	  INSERT INTO alert_x_url_group(alert_id, url_group_id) VALUES (alertid, groupid);
	  INSERT INTO alert_x_type_cycle (type_x_cycle_id, alert_id) VALUES (alert_cycle, alertid);
  END IF;

  INSERT INTO access_control (user_id, url_group_id, access_type_id, access_control_valid_from)
    SELECT userid, groupid, 1, NOW() WHERE NOT EXISTS (SELECT user_id FROM access_control WHERE user_id=userid AND url_group_id = groupid);

IF urlid IS NOT NULL THEN
  INSERT INTO access_control (user_id, url_id, access_type_id, access_control_valid_from)
    SELECT userid, urlid, 1, NOW() FROM url u WHERE url_id = urlid AND NOT EXISTS
    (SELECT user_id FROM access_control a WHERE user_id=userid AND u.url_id = a.url_id);
  INSERT INTO user_subscription (user_id, url_group_id, url_id, user_action)
  VALUES (userid, groupid, urlid, 'subscribe');
ELSE
  INSERT INTO access_control (user_id, url_id, access_type_id, access_control_valid_from)
    SELECT userid, url_id, 1, NOW() FROM url u WHERE url_group_id = groupid AND NOT EXISTS
    (SELECT user_id FROM access_control a WHERE user_id=userid AND u.url_id = a.url_id);
  INSERT INTO user_subscription (user_id, url_group_id, url_id, user_action)
  SELECT userid, groupid, url_id,'subscribe' FROM url WHERE url_group_id = groupid;
END IF;

END
$$ LANGUAGE plpgsql;

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION public.subscribe(groupid INT, userid INT, alert_cycle INT, urlid INT)
  OWNER TO project_mon;

