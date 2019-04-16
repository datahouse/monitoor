SET search_path TO public, thirdparty;

DROP FUNCTION IF EXISTS update_keywords_for_alert(INT, INT, TEXT[]);
CREATE OR REPLACE FUNCTION update_keywords_for_alert(
  in_alert_id INT,
  in_user_id INT,
  in_keywords TEXT[]
)
RETURNS void
AS $$
DECLARE
  keywords TEXT[];
BEGIN
  -- Without COALESCE, array_agg returns NULL for empty arrays, which
  -- leads to all comparisons for alert_keyword = ANY(keywords) to
  -- fail. See issue #2374.
  SELECT COALESCE(array_agg(x.lower), '{}'::TEXT[]) INTO keywords FROM (
    SELECT lower(unnest(in_keywords))
  ) AS x;

  -- deactivate keywords that are not in the given array, anymore.
  UPDATE alert_keyword
  SET alert_keyword_active = False
  FROM alert AS a
  WHERE a.alert_id = alert_keyword.alert_id
    AND a.alert_id = in_alert_id
    AND a.user_id = in_user_id
    AND NOT alert_keyword = ANY(keywords)
    AND alert_keyword_active;

  -- re-enable deactivated, but existing keywords
  UPDATE alert_keyword
  SET alert_keyword_active = True
  FROM alert AS a
  WHERE a.alert_id = alert_keyword.alert_id
    AND a.alert_id = in_alert_id
    AND a.user_id = in_user_id
    AND alert_keyword = ANY(keywords)
    AND NOT alert_keyword_active;

  -- add new keywords
  INSERT INTO alert_keyword (alert_id, alert_keyword)
  SELECT a.alert_id, new_keyword
  FROM unnest(keywords) AS new_keyword
  INNER JOIN alert AS a ON a.alert_id = in_alert_id AND a.user_id = in_user_id
  LEFT JOIN alert_keyword kw
    ON kw.alert_id = in_alert_id AND kw.alert_keyword = new_keyword
  WHERE kw.alert_keyword IS NULL;
END
$$ LANGUAGE plpgsql;

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION public.update_keywords_for_alert(in_alert_id integer, in_user_id integer, in_keywords text[])
  OWNER TO project_mon;
