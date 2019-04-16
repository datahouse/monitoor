SET search_path TO public, thirdparty;

DROP FUNCTION IF EXISTS enqueue_live_task(TEXT, JSONB);
CREATE OR REPLACE FUNCTION enqueue_live_task(in_kind TEXT, in_task_data JSONB)
RETURNS UUID
AS $$
DECLARE
  my_result uuid;
BEGIN
  INSERT INTO live_task_queue (task_kind, task_data)
  VALUES (in_kind, in_task_data)
  RETURNING task_uuid INTO my_result;

  NOTIFY spider_live_task_channel;

  RETURN my_result;
END
$$ LANGUAGE plpgsql;

-- FIXME: shouldn't belong to the app user
ALTER FUNCTION public.enqueue_live_task(in_kind text, in_task_data jsonb)
  OWNER TO project_mon;
