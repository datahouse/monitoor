SET search_path TO public, thirdparty;

-- Interestingly, there's no binary xor operator by default.
CREATE OR REPLACE FUNCTION xor(a BOOL, b BOOL)
    RETURNS BOOL
    RETURNS NULL ON NULL INPUT
    IMMUTABLE
    AS $xor$
SELECT (a AND NOT b) OR (NOT a AND b);
$xor$ LANGUAGE sql;

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION xor(a boolean, b boolean)
  OWNER TO project_mon;

