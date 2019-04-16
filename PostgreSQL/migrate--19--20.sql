ALTER TABLE url
  DROP CONSTRAINT external_check,
  ALTER COLUMN check_frequency_id SET NOT NULL;
