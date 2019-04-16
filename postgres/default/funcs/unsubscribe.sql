SET search_path TO public, thirdparty;

DROP FUNCTION IF EXISTS unsubscribe(INT, INT);
DROP FUNCTION IF EXISTS unsubscribe(INT, INT, INT);
CREATE OR REPLACE FUNCTION unsubscribe(groupid INT, userid INT, urlid INT=NULL)
RETURNS void
AS $$
DECLARE
  alertid INT;
  entry INT;
BEGIN

IF urlid IS NOT NULL THEN
  DELETE FROM access_control
   WHERE user_id=userid AND url_id = urlid AND access_type_id = 1;
  INSERT INTO user_subscription (user_id, url_group_id, url_id, user_action) VALUES (userid, groupid, urlid, 'unsubscribe');
ELSE
  DELETE FROM access_control
   WHERE user_id=userid AND url_id IN (SELECT url_id FROM url
    WHERE url_group_id=groupid) AND access_type_id = 1;
  DELETE FROM access_control
   WHERE user_id=userid AND url_group_id = groupid AND access_type_id = 1;
 INSERT INTO user_subscription (user_id, url_group_id, url_id, user_action)
  SELECT userid, groupid, url_id,'unsubscribe' FROM url WHERE url_group_id = groupid;
END IF;

SELECT url_id INTO entry FROM access_control WHERE url_id IN (SELECT url_id FROM url WHERE url_group_id = groupid) AND user_id = userid;

IF entry IS NULL THEN
DELETE FROM access_control
   WHERE user_id=userid AND url_group_id = groupid AND access_type_id = 1;
  FOR alertid IN SELECT a.alert_id
    FROM alert_x_url_group x JOIN alert a ON (a.alert_id=x.alert_id)
    WHERE url_group_id=groupid AND user_id=userid AND alert_active
     LOOP
     UPDATE alert SET alert_active = false WHERE alert_id = alertid;
     DELETE FROM alert_x_url_group WHERE alert_id = alertid AND url_group_id = groupid;
     DELETE FROM alert_x_type_cycle WHERE alert_id = alertid;
     UPDATE alert_keyword SET alert_keyword_active = false WHERE alert_id = alertid;
    END LOOP;
END IF;

END
$$ LANGUAGE plpgsql;

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION public.unsubscribe(groupid integer, userid integer, urlid integer)
  OWNER TO project_mon;

