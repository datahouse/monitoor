SET search_path TO public, thirdparty;

CREATE OR REPLACE FUNCTION delete_changes_for(in_url_id INT)
RETURNS SETOF INT
AS $$
  -- delete notifications
  WITH dx AS (
    SELECT change_id FROM change_x_url WHERE url_id = in_url_id
  )
  DELETE FROM notification
  USING dx WHERE notification.change_id = dx.change_id;

  -- drop references from urls (should better be handled by the Foreign Key)
  WITH dx as (
    SELECT change_id FROM change_x_url WHERE url_id = in_url_id
  )
  UPDATE url
  SET url_latest_change_id = NULL
  FROM dx
  WHERE dx.change_id = url.url_latest_change_id;

  -- delete the change and corresponding change_x_url entries
  WITH dx AS (
    DELETE FROM change_x_url
    WHERE url_id = in_url_id
    RETURNING change_id
  )
  DELETE FROM change
  USING dx
  WHERE change.change_id = dx.change_id
  RETURNING change.change_id;

$$ LANGUAGE sql;

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION delete_changes_for(in_url_id integer)
  OWNER TO project_mon;
