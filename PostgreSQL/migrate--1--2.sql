ALTER TABLE mon_user
  ALTER COLUMN user_valid_from SET DEFAULT now();

CREATE TABLE spider_job (
  job_id SERIAL PRIMARY KEY,
  url TEXT NOT NULL,
  url_lang TEXT NOT NULL,
  min_check_interval BIGINT NOT NULL,
  job_spider_uuid uuid REFERENCES spider(spider_uuid),
  job_active BOOL DEFAULT true NOT NULL,
  UNIQUE(url, url_lang)
);

CREATE TYPE change_type_enum AS ENUM ('insert', 'update', 'delete');
CREATE TABLE spider_change (
  change_id BIGSERIAL PRIMARY KEY,
  job_id INTEGER REFERENCES spider_job(job_id),
  change_type change_type_enum NOT NULL
);

ALTER TABLE url
  ALTER COLUMN check_frequency_id SET NOT NULL,
  ADD COLUMN url_lang TEXT DEFAULT 'de' NOT NULL,
  ADD COLUMN spider_job_id INT REFERENCES spider_job(job_id) NOT NULL;

ALTER TABLE check_frequency
  ADD COLUMN check_frequency_interval BIGINT;

WITH
  nv (id, new_interval) AS (VALUES
    (1, 3600),
    (2, 3600 * 24),
    (3, 3600 * 24 * 7))
UPDATE check_frequency SET check_frequency_interval = nv.new_interval
FROM nv
WHERE check_frequency_id = nv.id;

ALTER TABLE check_frequency
  ALTER COLUMN check_frequency_interval SET NOT NULL;
