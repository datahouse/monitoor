ALTER TABLE url
  ADD COLUMN url_group_id integer;

UPDATE url u
SET url_group_id = g.url_group_id
FROM url_x_group g
WHERE u.url_id = g.url_id;

ALTER TABLE url
  ALTER COLUMN url_group_id SET NOT NULL,
  ADD CONSTRAINT grp_fk FOREIGN KEY (url_group_id)
    REFERENCES url_group (url_group_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE url_x_group DROP CONSTRAINT urlid_fk;

ALTER TABLE url_x_group DROP CONSTRAINT url_group_fk;

CREATE OR REPLACE VIEW v_change AS
  SELECT n.alert_id, n.new_doc_id,
         n.old_doc_id, MIN(n.creation_ts) AS creation_ts, n.url_id, u.url,
         u.url_title, g.url_group_id, g.url_group_title, a.user_id,
         r.rating_value_id
    FROM notification n
    INNER JOIN alert a ON (a.alert_id = n.alert_id)
    INNER JOIN url u ON (u.url_id = n.url_id)
    INNER JOIN url_group g ON (u.url_group_id = g.url_group_id)
    LEFT JOIN access_control acc
      ON (a.user_id = acc.user_id AND acc.url_group_id = g.url_group_id)
    LEFT JOIN rating r
      ON a.user_id=r.user_id
        AND a.alert_id=r.alert_id
        AND n.new_doc_id = r.new_doc_id
    WHERE a.alert_active = TRUE
    GROUP BY n.alert_id, a.user_id,n.new_doc_id, n.url_id, u.url, u.url_title,
             n.old_doc_id, g.url_group_id, g.url_group_title,
             r.rating_value_id;

CREATE OR REPLACE VIEW spider_job_alert_type_cycle AS
  SELECT j.job_id, url.url_id, url.url_active,
         axu.alert_id, alert.alert_active, xtc.type_x_cycle_id,
         alert_group.url_group_id AS via_group_id
    FROM spider_job j
    INNER JOIN url ON url.spider_job_id = j.job_id
    INNER JOIN url_group ON url_group.url_group_id = url.url_group_id
    INNER JOIN url_group_children alert_group
            ON url_group.url_group_id = ANY(alert_group.child_ids)
    INNER JOIN alert_x_url_group axu
            ON axu.url_group_id = alert_group.url_group_id
    INNER JOIN alert_x_type_cycle xtc ON xtc.alert_id = axu.alert_id
    INNER JOIN alert ON axu.alert_id = alert.alert_id;

DROP TABLE url_x_group;
