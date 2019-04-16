INSERT INTO check_frequency (check_frequency_id, check_frequency_text)
VALUES
    (1, 'hourly'),
    (2, 'daily'),
    (3, 'weekly');

INSERT INTO alert_type (alert_type_id, alert_type_name, alert_type_description)
VALUES
    (1, 'SMS', 'SMS alert'),
    (2, 'Email', 'Email alert');

INSERT INTO alert_cycle (alert_cycle_id, alert_cycle_name,
                         alert_cycle_description)
VALUES
    (1, 'daily', 'daily digest'),
    (2, 'immediate', 'immediate release');

INSERT INTO type_x_cycle (alert_type_id, alert_cycle_id)
VALUES
    (1, 2),
    (2, 1),
    (2, 2);

INSERT INTO access_type (access_type_id, access_type_name,
                         access_type_description)
VALUES
    (1, 'r', 'read'),
    (2, 'rw', 'read and write');
