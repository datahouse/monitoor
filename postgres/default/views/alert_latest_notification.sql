CREATE VIEW alert_latest_notification AS
  SELECT
    n.alert_id,
    cu.url_id,
    n.type_x_cycle_id,
    c.new_doc_id AS latest_doc_id,
    c.delta AS latest_delta,
    c.ts AS latest_notification_ts
  FROM (
      SELECT
        n1.alert_id,
        n1.type_x_cycle_id,
        ( SELECT c2.change_id
          FROM notification n2
          INNER JOIN change c1 ON n1.change_id = c1.change_id
          LEFT JOIN change_x_url cu1 ON cu1.change_id = c1.change_id
          INNER JOIN change c2 ON n2.change_id = c2.change_id
          LEFT JOIN change_x_url cu2 ON cu2.change_id = c2.change_id
          WHERE n2.alert_id = n1.alert_id
            AND cu1.url_id = cu2.url_id
            AND n1.type_x_cycle_id = n2.type_x_cycle_id
            -- Disregard retained notifications
            AND NOT is_retained
            -- This ORDER BY clause exactly matches the index
            -- notification_creation_ts_idx and allows quick (reverse)
            -- index scanning to retrieve the max(creation_ts)
          ORDER BY n2.alert_id DESC, cu2.url_id DESC,
                   n2.type_x_cycle_id DESC, c2.ts DESC
          LIMIT 1
        ) AS latest_change_id
        FROM notification n1
        WHERE NOT n1.is_retained
     ) AS a
  LEFT JOIN notification n
    ON n.alert_id = a.alert_id
      AND n.change_id = a.latest_change_id
      AND n.type_x_cycle_id = a.type_x_cycle_id
  LEFT JOIN change c
    ON c.change_id = n.change_id
  LEFT JOIN change_x_url cu
    ON c.change_id = cu.change_id;

-- FIXME: shouldn't belong to the app user
ALTER VIEW alert_latest_notification
  OWNER TO project_mon;
