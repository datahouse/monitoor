-- re-populate stats from existing notifications
INSERT INTO notification_counter_internal
  (user_id, url_group_id, period_start, count)
SELECT
  user_id,
  url_group_id,
  trim_to_notification_period(creation_ts) AS period_start,
  COUNT(change_id) AS count
FROM v_change
GROUP BY
  user_id,
  url_group_id,
  trim_to_notification_period(creation_ts);
