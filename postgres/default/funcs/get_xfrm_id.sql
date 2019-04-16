SET search_path TO public, thirdparty;

DROP FUNCTION IF EXISTS get_xfrm_id(TEXT, JSONB);
CREATE OR REPLACE FUNCTION get_xfrm_id(
  in_commands TEXT,
  in_args JSONB
)
RETURNS INT
AS $$
DECLARE
  new_xfrm_id INT;
BEGIN
  SELECT xfrm_id INTO new_xfrm_id
  FROM xfrm
  WHERE xfrm_commands = in_commands
    AND xfrm_args = in_args;

  IF NOT found THEN
    INSERT INTO xfrm (xfrm_commands, xfrm_args)
    VALUES (in_commands, in_args)
    RETURNING xfrm_id INTO new_xfrm_id;
  END IF;

  RETURN new_xfrm_id;
END
$$ LANGUAGE plpgsql;

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION get_xfrm_id(in_commands text, in_args jsonb)
  OWNER TO project_mon;

