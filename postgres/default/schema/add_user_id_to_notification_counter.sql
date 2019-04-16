-- ATTENTION: must be re-created from the corresponding view script.
DROP VIEW notification_counter;
DROP TABLE notification_counter_internal;

-- internal counter table, possibly featuring multiple entries for
-- parallel updates.
CREATE TABLE notification_counter_internal (
  user_id INT NOT NULL REFERENCES mon_user(user_id),
  url_group_id INT NOT NULL REFERENCES url_group(url_group_id),
  -- some random uid to make conflicts reasonably improbable, but still
  -- allowing us to identify individual entries.
  entry_uid INT NOT NULL DEFAULT floor(random() * 2147483647),
  period_start TIMESTAMP WITHOUT TIME ZONE NOT NULL
    DEFAULT trim_to_notification_period(now()),
  count BIGINT NOT NULL DEFAULT 1,
  PRIMARY KEY (user_id, url_group_id, entry_uid)
);

ALTER TABLE notification_counter_internal
  OWNER TO project_mon;
