CREATE TABLE live_task_queue (
  task_uuid uuid NOT NULL DEFAULT thirdparty.gen_random_uuid(),
  task_kind TEXT NOT NULL,
  task_data JSONB,
  -- to assign a job to a specific spider, may be null for tasks any
  -- spider can process
  spider_uuid uuid NULL REFERENCES spider(spider_uuid)
);

CREATE TABLE live_task_result (
  task_uuid uuid NOT NULL,
  task_result JSONB,
  spider_uuid uuid NOT NULL REFERENCES spider(spider_uuid)
);
