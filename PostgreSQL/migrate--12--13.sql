drop table cfg;

ALTER TABLE alert ADD COLUMN alert_option_id integer NOT NULL DEFAULT 1;

ALTER TABLE alert ADD CONSTRAINT alopt_fk FOREIGN KEY (alert_option_id)
    REFERENCES alert_option (alert_option_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE alert SET alert_option_id = 2
    WHERE alert_id IN (SELECT alert_id FROM alert_keyword);

INSERT INTO alert_option (alert_option_id, alert_option_name, alert_option_description)
VALUES
    (3, 'Activity threshold', 'Activity option - with threshold');

ALTER TABLE alert ADD alert_threshold smallint;

ALTER TABLE url_group ADD COLUMN is_subscription boolean NOT NULL DEFAULT false;
ALTER TABLE url_group ADD COLUMN is_demo boolean NOT NULL DEFAULT false;

