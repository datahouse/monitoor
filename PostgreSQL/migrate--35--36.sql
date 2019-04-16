-- Correction of v_change due to #5951.
CREATE OR REPLACE VIEW v_change AS
  SELECT
    n.alert_id,
    n.type_x_cycle_id,
    c.change_id,
    c.new_doc_id,
    c.old_doc_id,
    c.delta,
    cu.ts AS creation_ts,  -- using indexable ts
    u.url_id,
    u.url,
    u.url_title,
    g.url_group_id,
    g.url_group_title,
    a.user_id
  FROM notification n,
    change c,
    change_x_url cu,
    alert a,
    alert_x_url_group aug,
    url u,
    url_group g
  WHERE
    -- join conditions
    c.change_id = cu.change_id
    AND n.change_id = c.change_id
    AND a.alert_id = n.alert_id
    AND aug.alert_id = n.alert_id
    AND aug.url_group_id = cu.url_group_id
    AND u.url_id = cu.url_id
    AND cu.url_group_id = g.url_group_id

    -- original where clause
    AND a.alert_active = true
    AND n.is_retained = false
  ;
