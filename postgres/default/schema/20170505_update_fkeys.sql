ALTER TABLE url
  DROP CONSTRAINT url_url_group_id_fkey,
  ADD CONSTRAINT url_url_group_id_fkey
    FOREIGN KEY (url_group_id)
    REFERENCES url_group(url_group_id)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE notification_counter_internal
  DROP CONSTRAINT notification_counter_internal_url_group_id_fkey,
  ADD CONSTRAINT notification_counter_internal_url_group_id_fkey
    FOREIGN KEY (url_group_id)
    REFERENCES url_group(url_group_id)
    ON DELETE CASCADE;

ALTER TABLE change_x_url
  ADD CONSTRAINT change_x_url_url_group_id_fkey
    FOREIGN KEY (url_group_id)
    REFERENCES url_group(url_group_id)
    ON DELETE SET NULL;
