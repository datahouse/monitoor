SET search_path TO public, thirdparty;

CREATE OR REPLACE FUNCTION url_filter(url_in TEXT)
RETURNS TEXT
AS $$
   SELECT uri_encode(regexp_replace(url_in, '#[^?]*$', ''));
$$ LANGUAGE SQL;

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION public.url_filter(url_in text)
  OWNER TO project_mon;

