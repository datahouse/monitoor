-- rename a deprecated index, so it doesn't get in the way
ALTER INDEX notification_creation_ts_idx
  RENAME TO old_notification_creation_ts_idx;

-- add a creation_ts column to notification so we can build
-- a very helpful index on it
ALTER TABLE notification
  ADD COLUMN creation_ts TIMESTAMP WITH TIME ZONE;

-- populate it with data from the change table and arrange for
-- proper constraints
UPDATE notification
  SET creation_ts = (
    SELECT c.ts
    FROM change c
    WHERE c.change_id = notification.change_id
  );

ALTER TABLE notification
  ALTER COLUMN creation_ts SET NOT NULL,
  ALTER COLUMN creation_ts SET DEFAULT now();

-- the index fixing #2730
CREATE INDEX notification_creation_ts_idx
  ON notification(creation_ts);

-- recreate this view to use the above index
CREATE OR REPLACE VIEW v_change AS
  SELECT
    n.alert_id,
    n.type_x_cycle_id,
    c.change_id,
    c.new_doc_id,
    c.old_doc_id,
    c.delta,
    n.creation_ts,  -- using indexable ts
    cu.url_id,
    u.url,
    u.url_title,
    g.url_group_id,
    g.url_group_title,
    a.user_id
  FROM notification n
  JOIN change c ON n.change_id = c.change_id
  JOIN change_x_url cu ON c.change_id = cu.change_id
  JOIN alert a ON a.alert_id = n.alert_id
  JOIN url u ON u.url_id = cu.url_id
  JOIN url_group g ON u.url_group_id = g.url_group_id
  JOIN alert_x_url_group aug
    ON a.alert_id = aug.alert_id AND g.url_group_id = aug.url_group_id
  WHERE a.alert_active = true AND n.is_retained = false
  -- dropping the unnecessary grouping clause here.
  ;
