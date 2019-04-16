SET search_path TO public, thirdparty;

DROP FUNCTION IF EXISTS add_external_change(
  TIMESTAMP WITH TIME ZONE, JSONB, INT, INT);
CREATE OR REPLACE FUNCTION add_external_change(
  in_ts TIMESTAMP WITH TIME ZONE,
  in_delta JSONB,
  in_user_id INT,
  in_url_id INT
) RETURNS INT -- the change_id inserted
AS $$
  INSERT INTO pending_change (check_frequency_id, url_ids, creation_ts, delta)
  VALUES (0, ARRAY[in_url_id], in_ts, in_delta)
  RETURNING pending_change_id;
$$ LANGUAGE sql;

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION public.add_external_change(in_ts timestamp with time zone, in_delta jsonb, in_user_id integer, in_url_id integer)
  OWNER TO project_mon;

