-- depends on the function trim_to_notification_period, therefore not in
-- the initial init.sql script.
ALTER TABLE notification_counter_internal
  ALTER COLUMN period_start SET DEFAULT trim_to_notification_period(now());
