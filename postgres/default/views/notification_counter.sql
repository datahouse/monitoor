-- view that shoud be used to query statistics
CREATE OR REPLACE VIEW notification_counter AS
  SELECT
    user_id,
    url_group_id,
    period_start,
    SUM(count)::BIGINT AS count
  FROM notification_counter_internal
  GROUP BY user_id, url_group_id, period_start;

-- FIXME: shouldn't belong to the app user
ALTER VIEW notification_counter
  OWNER TO project_mon;
