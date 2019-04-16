SET search_path TO public, thirdparty;

-- a little helper method to clear (json) nulls and eliminate
-- duplicates from json arrays
CREATE OR REPLACE FUNCTION json_uniq(in_data JSON)
  RETURNS json
LANGUAGE SQL IMMUTABLE
AS $$
SELECT COALESCE(json_agg(x.value), '[]'::json)
FROM (
  SELECT DISTINCT value::jsonb
  FROM json_array_elements(in_data)
  WHERE value::TEXT <> 'null'
) AS x;
$$;

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION public.json_uniq(in_data json)
  OWNER TO project_mon;
