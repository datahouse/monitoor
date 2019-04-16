CREATE OR REPLACE VIEW v_change AS
  SELECT
    n.alert_id,
    n.type_x_cycle_id,
    c.change_id,
    c.new_doc_id,
    c.old_doc_id,
    c.delta,
    c.ts AS creation_ts,
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
  GROUP BY n.alert_id, n.type_x_cycle_id, a.user_id, c.change_id,
    c.new_doc_id, cu.url_id, u.url, u.url_title, c.old_doc_id, c.delta,
    g.url_group_id, g.url_group_title;
