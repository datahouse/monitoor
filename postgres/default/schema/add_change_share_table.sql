CREATE TABLE IF NOT EXISTS change_share (
  change_share_id SERIAL NOT NULL,
  user_id INT NOT NULL,
  change_id INT NOT NULL,
  share_hash char(10) NOT NULL,
  last_used timestamp NULL,
  PRIMARY KEY (change_share_id),
  UNIQUE (share_hash)
);

ALTER TABLE change_share
  ADD CONSTRAINT user_fk FOREIGN KEY (user_id)
    REFERENCES mon_user (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT change_fk FOREIGN KEY (change_id)
    REFERENCES change (change_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

-- FIXME: shouldn't belong to the app user
ALTER TABLE change_share
  OWNER TO project_mon;
