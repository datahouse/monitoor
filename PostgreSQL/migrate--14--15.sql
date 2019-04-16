DROP VIEW IF EXISTS spider_job_alert_type_cycle;

ALTER TABLE alert ALTER COLUMN alert_threshold TYPE FLOAT;

-- Recreate the view (unchanged, except for the type change above).
CREATE VIEW spider_job_alert_type_cycle AS
  SELECT j.job_id, url.url_id, url.url_active,
         axu.alert_id, alert.alert_active,
         alert.alert_threshold,
         xtc.type_x_cycle_id,
         alert_group.url_group_id AS via_group_id,
         xfrm.xfrm_id, xfrm.xfrm_commands, xfrm.xfrm_args,
         cf.check_frequency_interval
    FROM spider_job j
    INNER JOIN url ON url.spider_job_id = j.job_id
    INNER JOIN xfrm ON xfrm.xfrm_id = url.xfrm_id
    INNER JOIN check_frequency cf USING(check_frequency_id)
    INNER JOIN url_group ON url_group.url_group_id = url.url_group_id
    INNER JOIN url_group_children alert_group
            ON url_group.url_group_id = ANY(alert_group.child_ids)
    INNER JOIN alert_x_url_group axu
            ON axu.url_group_id = alert_group.url_group_id
    INNER JOIN alert_x_type_cycle xtc ON xtc.alert_id = axu.alert_id
    INNER JOIN alert ON axu.alert_id = alert.alert_id;

-- Realign to represent a fraction between 0.0 and 1.0 rather than
-- percentage.
UPDATE alert
SET alert_threshold = alert_threshold / 100.0
WHERE alert_threshold IS NOT NULL;
