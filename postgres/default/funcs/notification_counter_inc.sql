SET search_path TO public, thirdparty;

CREATE OR REPLACE FUNCTION trim_to_notification_period(
  in_ts TIMESTAMP WITH TIME ZONE
) RETURNS TIMESTAMP WITHOUT TIME ZONE AS
$$
  SELECT date_trunc('hour', in_ts AT TIME ZONE 'UTC');
$$ LANGUAGE SQL IMMUTABLE;

DROP FUNCTION IF EXISTS notification_counter_inc(INT, TIMESTAMP WITH TIME ZONE);
CREATE OR REPLACE FUNCTION notification_counter_inc(
  in_user_id INT,
  in_url_group_id INT,
  in_ts TIMESTAMP WITH TIME ZONE
)
RETURNS void AS $$
DECLARE
  start_ts CONSTANT TIMESTAMP WITHOUT TIME ZONE
    := trim_to_notification_period(in_ts);
  locked_uid INT;
BEGIN
  SELECT entry_uid INTO locked_uid
  FROM notification_counter_internal
  WHERE user_id = in_user_id
    AND url_group_id = in_url_group_id
    AND period_start = start_ts
  FOR UPDATE SKIP LOCKED
  LIMIT 1;

  IF found THEN
    UPDATE notification_counter_internal
    SET count = notification_counter_internal.count + 1
    WHERE
      user_id = in_user_id
      AND url_group_id = in_url_group_id
      AND entry_uid = locked_uid
      AND period_start = start_ts;
  ELSE
    INSERT INTO notification_counter_internal
      (user_id, url_group_id, period_start)
    VALUES
      (in_user_id, in_url_group_id, start_ts);
  END IF;
END
$$ LANGUAGE plpgsql RETURNS NULL ON NULL INPUT;

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION public.trim_to_notification_period(in_ts timestamp with time zone)
  OWNER TO project_mon;
ALTER FUNCTION public.notification_counter_inc(integer, integer, timestamp with time zone)
  OWNER TO project_mon;
