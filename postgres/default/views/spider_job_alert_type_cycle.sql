SET search_path TO public, thirdparty;

DROP VIEW IF EXISTS spider_job_alert_type_cycle;
DROP VIEW IF EXISTS url_group_children;

CREATE VIEW url_group_children AS
  SELECT c.url_group_id, (
    WITH RECURSIVE t(id) AS (
        SELECT c.url_group_id
      UNION ALL
        SELECT g.url_group_id AS id FROM t
          LEFT JOIN url_group g ON t.id = g.parent_url_group_id
          WHERE g.parent_url_group_id IS NOT NULL
    )
    SELECT array_agg(t.id) FROM t
  )::int[] AS child_ids
  FROM url_group c;


CREATE VIEW spider_job_alert_type_cycle AS
  SELECT j.job_id, url.url_id, url.url_active,
         axu.alert_id, alert.alert_active,
         alert.alert_threshold,
         xtc.type_x_cycle_id,
         alert_group.url_group_id AS via_group_id,
         xfrm.xfrm_id, xfrm.xfrm_commands, xfrm.xfrm_args,
         cf.check_frequency_interval
    FROM url
    LEFT JOIN spider_job j ON j.job_id = url.spider_job_id
    INNER JOIN xfrm ON xfrm.xfrm_id = url.xfrm_id
    INNER JOIN check_frequency cf USING(check_frequency_id)
    INNER JOIN url_group ON url_group.url_group_id = url.url_group_id
    INNER JOIN url_group_children alert_group
            ON url_group.url_group_id = ANY(alert_group.child_ids)
    INNER JOIN alert_x_url_group axu
            ON axu.url_group_id = alert_group.url_group_id
    INNER JOIN alert_x_type_cycle xtc ON xtc.alert_id = axu.alert_id
    INNER JOIN alert ON axu.alert_id = alert.alert_id;


-- FIXME: shouldn't belong to the app user
ALTER VIEW url_group_children
  OWNER TO project_mon;
ALTER VIEW spider_job_alert_type_cycle
  OWNER TO project_mon;

