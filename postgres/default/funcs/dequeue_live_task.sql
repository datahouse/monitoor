SET search_path TO public, thirdparty;

DROP FUNCTION IF EXISTS dequeue_live_task(uuid);
CREATE FUNCTION dequeue_live_task(in_spider_uuid uuid)
RETURNS JSON
AS $$
DECLARE
  my_task_uuid uuid;
  my_result JSON;
BEGIN
  SELECT json_build_object(
    'uuid', task_uuid,
    'kind', task_kind,
    'data', task_data
  ) INTO my_result
  FROM live_task_queue
  WHERE spider_uuid IS NULL OR spider_uuid = in_spider_uuid
  FOR UPDATE
  LIMIT 1;

  IF found THEN
    DELETE FROM live_task_queue
    WHERE task_uuid = (my_result->>'uuid')::UUID;
  END IF;

  RETURN my_result;
END
$$ LANGUAGE plpgsql;

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION dequeue_live_task(in_spider_uuid uuid)
  OWNER TO project_mon;
