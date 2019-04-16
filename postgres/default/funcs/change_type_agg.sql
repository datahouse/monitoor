SET search_path TO public, thirdparty;

DROP AGGREGATE IF EXISTS change_type_agg(change_type_enum);
DROP FUNCTION IF EXISTS change_type_agg_sfunc(change_type_enum, change_type_enum);

CREATE FUNCTION change_type_agg_sfunc(a change_type_enum, b change_type_enum) RETURNS change_type_enum
    LANGUAGE sql IMMUTABLE
    AS $$
  SELECT (CASE WHEN a = 'delete' OR b = 'delete'
    THEN 'delete'
    ELSE CASE WHEN a = 'insert' OR b = 'insert'
      THEN 'insert'
      ELSE 'update'
    END
  END)::change_type_enum;
$$;

CREATE AGGREGATE change_type_agg(change_type_enum) (
    SFUNC = change_type_agg_sfunc,
    STYPE = change_type_enum,
    INITCOND = 'update'
);

-- FIXME: shouldn't belong to the app user
ALTER AGGREGATE change_type_agg(change_type_enum)
  OWNER TO project_mon;
ALTER FUNCTION change_type_agg_sfunc(change_type_enum, change_type_enum)
  OWNER TO project_mon;
