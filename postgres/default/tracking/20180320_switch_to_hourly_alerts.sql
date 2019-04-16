INSERT INTO alert_cycle
  VALUES (5, 'hourly', 'hourly digest')
  ON CONFLICT DO NOTHING;

INSERT INTO type_x_cycle
  VALUES (8, 2, 5, true)
  ON CONFLICT DO NOTHING;

UPDATE alert_x_type_cycle
  SET type_x_cycle_id = 8
  WHERE type_x_cycle_id IN (2, 6);
