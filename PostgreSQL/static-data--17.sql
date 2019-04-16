INSERT INTO check_frequency (check_frequency_id, check_frequency_text,
                             check_frequency_interval)
VALUES
    (1, 'hourly', 3600),
    (2, 'daily', 3600 * 24),
    (3, 'weekly', 3600 * 24 * 7);

INSERT INTO alert_type (alert_type_id, alert_type_name, alert_type_description)
VALUES
    (1, 'SMS', 'SMS alert'),
    (2, 'Email', 'Email alert'),
    (3, 'none', 'no notification');

INSERT INTO alert_cycle (alert_cycle_id, alert_cycle_name,
                         alert_cycle_description)
VALUES
    (1, 'daily', 'daily digest'),
    (2, 'immediate', 'immediate release'),
    (3, 'online', 'online only');

INSERT INTO type_x_cycle (alert_type_id, alert_cycle_id, is_active)
VALUES
    (1, 2, false),
    (2, 1, true),
    (2, 2, true),
    (3, 3, true);

INSERT INTO access_type (access_type_id, access_type_name,
                         access_type_description)
VALUES
    (1, 'r', 'read'),
    (2, 'rw', 'read and write');

INSERT INTO xfrm (xfrm_commands, xfrm_args)
  VALUES ('html2text', '{}'),
         ('html2markdown', '{}'),
          ('xpath|html2markdown', '{"xpath": "//body"}');

insert into rating_value (rating_value_id, rating_value_desc)
VALUES
    (1, 'very poor'),
    (2, 'poor'),
    (3, 'average'),
    (4, 'good'),
    (5, 'very good');

INSERT INTO alert_option (alert_option_id, alert_option_name, alert_option_description)
VALUES
    (1, 'Activity', 'Activity option - all changes'),
    (2, 'Keywords', 'Keyword option'),
    (3, 'Activity threshold', 'Activity option - with threshold');

INSERT INTO pricing_plan (pricing_plan_id, pricing_plan_text, pricing_plan_sort_order)
VALUES
    (1, 'Professional', 1),
    (2, 'Enterprise', 2),
    (3, 'Integrator', 3),
    (4, 'Widget', 4);