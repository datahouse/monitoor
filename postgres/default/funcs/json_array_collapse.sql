SET search_path TO public, thirdparty;

-- Generates a simple array from an array of arrays, i.e. strips one
-- level of nesting.
CREATE OR REPLACE FUNCTION json_array_collapse(in_data json)
  RETURNS json
  RETURNS NULL ON NULL INPUT
  LANGUAGE SQL IMMUTABLE
AS $$
SELECT json_agg(unnested2.value)
FROM (
  SELECT json_array_elements(
      CASE json_typeof(unnested.value) WHEN 'null' THEN '[]'::JSON
                                       ELSE unnested.value END
    ) AS value
    FROM (
      SELECT json_array_elements(
        CASE json_typeof(in_data) WHEN 'null' THEN '[]'::JSON
                                  ELSE in_data END
      ) AS value
    ) AS unnested
  ) AS unnested2;
$$;

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION public.json_array_collapse(in_data json)
  OWNER TO project_mon;

