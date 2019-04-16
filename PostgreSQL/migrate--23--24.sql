INSERT INTO alert_type (alert_type_id, alert_type_name, alert_type_description)
VALUES
    (4, 'Push', 'Push alert');

INSERT INTO type_x_cycle (type_x_cycle_id, alert_type_id, alert_cycle_id, is_active)
VALUES
    (5, 4, 2, true);

