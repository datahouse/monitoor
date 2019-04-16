SET search_path TO public, thirdparty;

DROP FUNCTION IF EXISTS url_add(TEXT, TEXT, TEXT, INT, INT, INT);
CREATE OR REPLACE FUNCTION url_add(new_url_title TEXT, new_url TEXT,
  new_lang TEXT, new_user_id INT, new_access_type_id INT,
  new_check_frequency_id INT, xfrm_commands TEXT, xfrm_args JSONB)
RETURNS TEXT
AS $$
DECLARE
  my_url_id INT;
BEGIN
  INSERT INTO url (url_title, url, url_lang, url_creator_user_id,
                   check_frequency_id, xfrm_id)
    VALUES (new_url_title, new_url, new_lang, new_user_id,
            new_check_frequency_id,
            get_xfrm_id(xfrm_commands, xfrm_args))
    RETURNING url_id INTO my_url_id;

  INSERT INTO access_control
    (user_id, url_id, access_type_id, access_control_valid_from)
    VALUES (new_user_id, my_url_id, new_access_type_id, now());

  RETURN my_url_id;
END
$$ LANGUAGE plpgsql;

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION public.url_add(new_url_title text, new_url text, new_lang text, new_user_id integer, new_access_type_id integer, new_check_frequency_id integer, xfrm_commands text, xfrm_args jsonb)
  OWNER TO project_mon;
