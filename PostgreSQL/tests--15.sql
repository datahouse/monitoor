SET search_path TO tests, public;

CREATE OR REPLACE FUNCTION clean_for_testing()
RETURNS void
AS $$
  -- delete all existing jobs and changes (will be rolled back, anyways)
  DELETE FROM url;
  DELETE FROM notification;
  DELETE FROM spider_document;
  DELETE FROM spider_change;
  DELETE FROM spider_job;
$$ LANGUAGE SQL;

CREATE OR REPLACE FUNCTION test_url_simple_insert()
RETURNS SETOF TEXT
AS $$
DECLARE
  test_user_id INT;
BEGIN
  PERFORM clean_for_testing();

   -- add a test user
  INSERT INTO mon_user (user_email) VALUES ('test@example.com')
    RETURNING user_id INTO test_user_id;

  -- add an example URL
  PERFORM url_add('some title', 'http://tests.example.com/about', 'de',
                  test_user_id,
		          2,  -- access_type_id for read/write
                  1, -- check_frequency_id for hourly
                  'html2markdown', '{}');

  -- expect exactly one single change
  RETURN NEXT results_eq(
    'SELECT change_type FROM spider_change;',
    array['insert'::change_type_enum]);
END
$$ LANGUAGE plpgsql;

DROP FUNCTION IF EXISTS test_url_insert_delete();
CREATE OR REPLACE FUNCTION test_url_insert_deactivate()
RETURNS SETOF TEXT
AS $$
DECLARE
  test_user_id INT;
  my_url_id INT;
  my_job_id INT;
BEGIN
  PERFORM clean_for_testing();

  -- add a test user
  INSERT INTO mon_user (user_email) VALUES ('test@example.com')
    RETURNING user_id INTO test_user_id;

  -- add an example URL
  SELECT url_add('some title', 'http://tests.example.com/about', 'de',
                  test_user_id,
		          2,  -- access_type_id for read/write
                  1,  -- check_frequency_id for hourly
                  'html2markdown', '{}')
  INTO my_url_id;

  -- then deactivate that url
  UPDATE url SET url_active = false WHERE url_id = my_url_id;

  -- expect exactly one deactivated job
  RETURN NEXT results_eq(
    'SELECT job_active FROM spider_job;',
    array[false]);

  SELECT max(job_id) INTO my_job_id FROM spider_job;

  -- expect two changes
  RETURN NEXT results_eq(
    'SELECT change_type FROM spider_change WHERE job_id = ' ||
      my_job_id::text || ' ORDER BY change_id ASC;',
    array['insert'::change_type_enum,
          'delete'::change_type_enum]);

  -- no other changes
  RETURN NEXT is_empty(
    'SELECT change_type FROM spider_change WHERE job_id != ' ||
      my_job_id::text);
END
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION test_url_delete_reactivate()
RETURNS SETOF TEXT
AS $$
DECLARE
  test_user_id INT;
  my_url_id INT;
  my_job_id INT;
BEGIN
  PERFORM clean_for_testing();

  -- add a test user
  INSERT INTO mon_user (user_email) VALUES ('test@example.com')
    RETURNING user_id INTO test_user_id;

  -- add an example URL
  SELECT url_add('some title', 'http://tests.example.com/about', 'de',
                  test_user_id,
		          2,  -- access_type_id for read/write
                  1,  -- check_frequency_id for hourly
                  'html2markdown', '{}')
  INTO my_url_id;

  -- then deactivate that url
  UPDATE url SET url_active = false WHERE url_id = my_url_id;

  -- expect exactly one deactivated job
  RETURN NEXT results_eq(
    'SELECT job_active FROM spider_job;',
    array[false]);

  SELECT max(job_id) INTO my_job_id FROM spider_job;

  -- expect two changes
  RETURN NEXT results_eq(
    'SELECT change_type FROM spider_change WHERE job_id = ' ||
      my_job_id::text || ' ORDER BY change_id ASC;',
    array['insert'::change_type_enum,
          'delete'::change_type_enum]);

  -- no other changes
  RETURN NEXT is_empty(
    'SELECT change_type FROM spider_change WHERE job_id != ' ||
      my_job_id::text);

  -- reactivate the URL
  SELECT url_add('another title', 'http://tests.example.com/about', 'de',
                  test_user_id,
		          2,  -- access_type_id for read/write
                  1,  -- check_frequency_id for hourly
                  'html2markdown', '{}')
  INTO my_url_id;

  -- expect exactly one (re)activated job
  RETURN NEXT results_eq(
    'SELECT job_active FROM spider_job;',
    array[true]);

  -- expect three changes, now
  RETURN NEXT results_eq(
    'SELECT change_type FROM spider_change WHERE job_id = ' ||
      my_job_id::text || ' ORDER BY change_id ASC;',
    array['insert'::change_type_enum,
          'delete'::change_type_enum,
          'insert'::change_type_enum]);

END
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION test_url_insert_twice_lower_freq()
RETURNS SETOF TEXT
AS $$
DECLARE
  test_user_id INT;
  other_user_id INT;
  my_url_id INT;
  my_job_id INT;
BEGIN
  PERFORM clean_for_testing();

  -- add a test user
  INSERT INTO mon_user (user_email) VALUES ('test@example.com')
    RETURNING user_id INTO test_user_id;

  -- add an example URL
  SELECT url_add('some title', 'http://tests.example.com/about', 'de',
                  test_user_id,
		          2,  -- access_type_id for read/write
                  1,  -- check_frequency_id for hourly
                  'html2markdown', '{}')
  INTO my_url_id;

  -- expect exactly one active job
  RETURN NEXT results_eq(
    'SELECT job_active FROM spider_job;',
    array[true]);

  -- with another user, add the same URL to monitor
  WITH
    new_user AS (
      INSERT INTO mon_user (user_email) VALUES ('other@example.com')
      RETURNING user_id)
  SELECT url_add('another title', 'http://tests.example.com/about', 'de',
                 user_id,
				 2,  -- access_type_id for read/write
                 3,  -- check_frequency_id for weekly
                 'html2markdown', '{}')
  INTO other_user_id
  FROM new_user;

  -- still exactly one active job
  RETURN NEXT results_eq(
    'SELECT job_active FROM spider_job;',
    array[true]);

  SELECT max(job_id) INTO my_job_id FROM spider_job;

  -- expect a sinlge change
  RETURN NEXT results_eq(
    'SELECT change_type FROM spider_change WHERE job_id = ' ||
      my_job_id::text || ' ORDER BY change_id ASC;',
    array['insert'::change_type_enum]);

  -- no other changes
  RETURN NEXT is_empty(
    'SELECT change_type FROM spider_change WHERE job_id != ' ||
      my_job_id::text);
END
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION test_url_insert_twice_then_update_freq()
RETURNS SETOF TEXT
AS $$
DECLARE
  test_user_id INT;
  other_url_id INT;
  my_url_id INT;
BEGIN
  PERFORM clean_for_testing();

  -- add a test user
  INSERT INTO mon_user (user_email) VALUES ('test@example.com')
    RETURNING user_id INTO test_user_id;

  -- add an example URL
  SELECT url_add('some title', 'http://tests.example.com/about', 'de',
                  test_user_id,
		          2,  -- access_type_id for read/write
                  2,  -- check_frequency_id for daily
                  'html2markdown', '{}')
  INTO my_url_id;

  -- expect exactly one active job
  RETURN NEXT results_eq(
    'SELECT job_active FROM spider_job;',
    array[true]);

  -- with another user, add the same URL to monitor
  WITH
    new_user AS (
      INSERT INTO mon_user (user_email) VALUES ('other@example.com')
      RETURNING user_id)
  SELECT url_add('another title', 'http://tests.example.com/about', 'de',
                 user_id,
				 2,  -- access_type_id for read/write
                 3,  -- check_frequency_id for weekly
                  'html2markdown', '{}')
  INTO other_url_id
  FROM new_user;

  -- still exactly one active job
  RETURN NEXT results_eq(
    'SELECT job_active FROM spider_job;',
    array[true]);

  -- update the second url for a higher frequency
  UPDATE url SET check_frequency_id = 1 WHERE url_id = other_url_id;

  -- expect an insert followed by an update
  RETURN NEXT results_eq(
    'SELECT change_type FROM spider_change ORDER BY change_id ASC;',
    array['insert'::change_type_enum,
          'update'::change_type_enum]);
END
$$ LANGUAGE plpgsql;



CREATE OR REPLACE FUNCTION test_url_insert_twice_higher_freq()
RETURNS SETOF TEXT
AS $$
DECLARE
  test_user_id INT;
  other_user_id INT;
  my_url_id INT;
  my_job_id INT;
BEGIN
  PERFORM clean_for_testing();

  -- add a test user
  INSERT INTO mon_user (user_email) VALUES ('test@example.com')
    RETURNING user_id INTO test_user_id;

  -- add an example URL
  SELECT url_add('some title', 'http://tests.example.com/about', 'de',
                  test_user_id,
		          2,  -- access_type_id for read/write
                  2,  -- check_frequency_id for daily
                  'html2markdown', '{}')
  INTO my_url_id;

  -- expect exactly one active job
  RETURN NEXT results_eq(
    'SELECT job_active FROM spider_job;',
    array[true]);

  -- with another user, add the same URL to monitor
  WITH
    new_user AS (
      INSERT INTO mon_user (user_email) VALUES ('other@example.com')
      RETURNING user_id)
  SELECT url_add('another title', 'http://tests.example.com/about', 'de',
                 user_id,
				 2,  -- access_type_id for read/write
                 1,  -- check_frequency_id for hourly
                 'html2markdown', '{}')
  INTO other_user_id
  FROM new_user;

  -- still exactly one active job
  RETURN NEXT results_eq(
    'SELECT job_active FROM spider_job;',
    array[true]);

  SELECT max(job_id) INTO my_job_id FROM spider_job;

  -- expect two changes
  RETURN NEXT results_eq(
    'SELECT change_type FROM spider_change WHERE job_id = ' ||
      my_job_id::text || ' ORDER BY change_id ASC;',
    array['insert'::change_type_enum,
          'update'::change_type_enum]);

  -- no other changes
  RETURN NEXT is_empty(
    'SELECT change_type FROM spider_change WHERE job_id != ' ||
      my_job_id::text);
END
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION test_url_insert_update_url()
RETURNS SETOF TEXT
AS $$
DECLARE
  test_user_id INT;
  my_url_id INT;
BEGIN
  PERFORM clean_for_testing();

  -- add a test user
  INSERT INTO mon_user (user_email) VALUES ('test@example.com')
    RETURNING user_id INTO test_user_id;

  -- add an example URL
  SELECT url_add('some title', 'http://tests.example.com/about', 'de',
                  test_user_id,
		          2,  -- access_type_id for read/write
                  1,  -- check_frequency_id for hourly
                  'html2markdown', '{}')
  INTO my_url_id;

  -- then update that url
  UPDATE url SET url = 'http://tests.example.com/different-page'
    WHERE url_id = my_url_id;

  -- expect an inactive and an active job
  RETURN NEXT results_eq(
    'SELECT job_active FROM spider_job ORDER BY job_id ASC;',
    array[false, true]);

  -- results in three changes
  RETURN NEXT results_eq(
    'SELECT change_type FROM spider_change ORDER BY change_id ASC;',
    array['insert'::change_type_enum,
          'delete'::change_type_enum,
          'insert'::change_type_enum]);
END
$$ LANGUAGE plpgsql;



CREATE OR REPLACE FUNCTION test_url_insert_update_freq()
RETURNS SETOF TEXT
AS $$
DECLARE
  test_user_id INT;
  my_url_id INT;
BEGIN
  PERFORM clean_for_testing();

  -- add a test user
  INSERT INTO mon_user (user_email) VALUES ('test@example.com')
    RETURNING user_id INTO test_user_id;

  -- add an example URL
  SELECT url_add('some title', 'http://tests.example.com/about', 'de',
                  test_user_id,
		          2,  -- access_type_id for read/write
                  1,  -- check_frequency_id for hourly
                  'html2markdown', '{}')
  INTO my_url_id;

  -- then update that url
  UPDATE url SET check_frequency_id = 3
    WHERE url_id = my_url_id;

  -- expect a single active job
  RETURN NEXT results_eq(
    'SELECT job_active FROM spider_job ORDER BY job_id ASC;',
    array[true]);

  -- results in three changes
  RETURN NEXT results_eq(
    'SELECT change_type FROM spider_change ORDER BY change_id ASC;',
    array['insert'::change_type_enum,
          'update'::change_type_enum]);
END
$$ LANGUAGE plpgsql;


DROP FUNCTION IF EXISTS test_url_insert_twice_then_delete_other();
CREATE OR REPLACE FUNCTION test_url_insert_twice_then_deactivate_other()
RETURNS SETOF TEXT
AS $$
DECLARE
  test_user_id INT;
  other_url_id INT;
  my_url_id INT;
BEGIN
  PERFORM clean_for_testing();

  -- add a test user
  INSERT INTO mon_user (user_email) VALUES ('test@example.com')
    RETURNING user_id INTO test_user_id;

  -- add an example URL
  SELECT url_add('some title', 'http://tests.example.com/about', 'de',
                  test_user_id,
		          2,  -- access_type_id for read/write
                  2,  -- check_frequency_id for daily
                  'html2markdown', '{}')
  INTO my_url_id;

  -- expect exactly one active job
  RETURN NEXT results_eq(
    'SELECT job_active FROM spider_job;',
    array[true]);

  -- with another user, add the same URL to monitor
  WITH
    new_user AS (
      INSERT INTO mon_user (user_email) VALUES ('other@example.com')
      RETURNING user_id)
  SELECT url_add('another title', 'http://tests.example.com/about', 'de',
                 user_id,
				 2,  -- access_type_id for read/write
                 3,  -- check_frequency_id for weekly
                  'html2markdown', '{}')
  INTO other_url_id
  FROM new_user;

  -- still exactly one active job
  RETURN NEXT results_eq(
    'SELECT job_active FROM spider_job;',
    array[true]);

  -- delete the url with the lower check_frequency, i.e. this
  -- shouldn't matter to the spider_job
  UPDATE url SET url_active = false WHERE url_id = other_url_id;

  -- expect an insert followed by an update
  RETURN NEXT results_eq(
    'SELECT change_type FROM spider_change ORDER BY change_id ASC;',
    array['insert'::change_type_enum]);
END
$$ LANGUAGE plpgsql;


DROP FUNCTION IF EXISTS test_url_insert_twice_then_delete_twice();
CREATE OR REPLACE FUNCTION test_url_insert_twice_then_deactivate_both()
RETURNS SETOF TEXT
AS $$
DECLARE
  test_user_id INT;
  other_url_id INT;
  my_url_id INT;
BEGIN
  PERFORM clean_for_testing();

  -- add a test user
  INSERT INTO mon_user (user_email) VALUES ('test@example.com')
    RETURNING user_id INTO test_user_id;

  -- add an example URL
  SELECT url_add('some title', 'http://tests.example.com/about', 'de',
                  test_user_id,
		          2,  -- access_type_id for read/write
                  2,  -- check_frequency_id for daily
                  'html2markdown', '{}')
  INTO my_url_id;

  -- expect exactly one active job
  RETURN NEXT results_eq(
    'SELECT job_active FROM spider_job;',
    array[true]);

  -- with another user, add the same URL to monitor
  WITH
    new_user AS (
      INSERT INTO mon_user (user_email) VALUES ('other@example.com')
      RETURNING user_id)
  SELECT url_add('another title', 'http://tests.example.com/about', 'de',
                 user_id,
				 2,  -- access_type_id for read/write
                 3,  -- check_frequency_id for weekly
                  'html2markdown', '{}')
  INTO other_url_id
  FROM new_user;

  -- still exactly one active job
  RETURN NEXT results_eq(
    'SELECT job_active FROM spider_job;',
    array[true]);

  -- delete the url with the lowest check_frequency
  UPDATE url SET url_active = false WHERE url_id = my_url_id;

  -- then delete the other url as well
  UPDATE url SET url_active = false WHERE url_id = other_url_id;

  -- exactyl one inactive job
  RETURN NEXT results_eq(
    'SELECT job_active FROM spider_job;',
    array[false]);

  -- expect an insert followed by an update
  RETURN NEXT results_eq(
    'SELECT change_type FROM spider_change ORDER BY change_id ASC;',
    array['insert'::change_type_enum,
          'update'::change_type_enum,
		  'delete'::change_type_enum]);
END
$$ LANGUAGE plpgsql;

DROP FUNCTION IF EXISTS test_alert_keyword_update();
CREATE OR REPLACE FUNCTION test_alert_keyword_update()
RETURNS SETOF TEXT
AS $$
DECLARE
  test_user_id INT;
  test_alert_id INT;
  other_alert_id INT;
  my_url_id INT;
BEGIN
  PERFORM clean_for_testing();

  -- add a test user
  INSERT INTO mon_user (user_email) VALUES ('test@example.com')
    RETURNING user_id INTO test_user_id;

  -- add an example URL
  SELECT url_add('some title', 'http://tests.example.com/about', 'de',
                  test_user_id,
		          2,  -- access_type_id for read/write
                  2,  -- check_frequency_id for daily
                  'html2markdown', '{}')
  INTO my_url_id;

  -- expect exactly one active job
  RETURN NEXT results_eq(
    'SELECT job_active FROM spider_job;',
    array[true]);

  -- an example alert
  INSERT INTO alert (user_id) VALUES (test_user_id)
  RETURNING alert_id INTO test_alert_id;

  -- and another one
  INSERT INTO alert (user_id) VALUES (test_user_id)
  RETURNING alert_id INTO other_alert_id;

  -- manually add an alert keyword
  INSERT INTO alert_keyword
    (alert_id, alert_keyword, alert_keyword_active)
  VALUES
    (test_alert_id, 'orange', true),
    (test_alert_id, 'apple', true),
    (test_alert_id, 'pear', false);

  -- then try to update the keywords list for the other alert
  PERFORM update_keywords_for_alert(other_alert_id, test_user_id,
                                    ARRAY['orange', 'pear', 'banana']::TEXT[]);

  RETURN NEXT results_eq(
    'SELECT alert_keyword FROM alert_keyword WHERE alert_id = ' ||
      test_alert_id || ' AND alert_keyword_active ORDER BY alert_keyword;',
    array['apple', 'orange']);

  RETURN NEXT results_eq(
    'SELECT alert_keyword FROM alert_keyword WHERE alert_id = ' ||
      other_alert_id || ' AND alert_keyword_active ORDER BY alert_keyword;',
    array['banana', 'orange', 'pear']);

  -- then update those of the first alert
  PERFORM update_keywords_for_alert(test_alert_id, test_user_id,
                                    ARRAY['orange', 'pear', 'banana', 'kiwi']);

  RETURN NEXT results_eq(
    'SELECT alert_keyword FROM alert_keyword WHERE alert_id = ' ||
      test_alert_id || ' AND alert_keyword_active ORDER BY alert_keyword;',
    array['banana', 'kiwi', 'orange', 'pear']);

  RETURN NEXT results_eq(
    'SELECT alert_keyword FROM alert_keyword WHERE alert_id = ' ||
      other_alert_id || ' AND alert_keyword_active ORDER BY alert_keyword;',
    array['banana', 'orange', 'pear']);

END
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION test_notification_on_duplicate_url()
RETURNS SETOF TEXT
AS $$
DECLARE
  test_spider_uuid UUID;
  test_user_id INT;
  test_url_group INT;
  test_url_id INT;
  my_job_id INT;
  other_url_id INT;
  test_alert_id INT;

  meta_rec RECORD;
  doc_rec RECORD;
BEGIN
  PERFORM clean_for_testing();

  -- add a dummy spider
  INSERT INTO spider (spider_uuid, spider_last_hostname)
    VALUES (gen_random_uuid(), 'nohostname')
    RETURNING spider_uuid INTO test_spider_uuid;

  -- add a test user
  INSERT INTO mon_user (user_email) VALUES ('test@example.com')
    RETURNING user_id INTO test_user_id;

  -- add an example URL
  SELECT url_add('some title', 'http://tests.example.com/about', 'de',
                  test_user_id,
		          2,  -- access_type_id for read/write
                  2,  -- check_frequency_id for daily
                  'html2markdown', '{}')
  INTO test_url_id;

  SELECT max(job_id) INTO my_job_id FROM spider_job;

  -- add the same URL under another id
  SELECT url_add('other title', 'http://tests.example.com/about', 'de',
                  test_user_id,
		          2,  -- access_type_id for read/write
                  2,  -- check_frequency_id for daily
                  'html2markdown', '{}')
  INTO other_url_id;

  -- add a group and assign both URL entries to that same group
  INSERT INTO url_group
      (url_group_title, url_group_creator_user_id)
    VALUES ('a group', test_user_id)
    RETURNING url_group_id INTO test_url_group;

  UPDATE url SET url_group_id = test_url_group
    WHERE url_id IN (test_url_id, other_url_id);

  -- an example alert
  INSERT INTO alert (user_id) VALUES (test_user_id)
    RETURNING alert_id INTO test_alert_id;

  -- specify a type_cycle for the alert (otherwise it isn't listed in
  -- the spider_job_alert_type_cycle view).
  INSERT INTO alert_x_type_cycle (alert_id, type_x_cycle_id)
    VALUES (test_alert_id, (
      SELECT type_x_cycle_id FROM type_x_cycle
      WHERE alert_type_id = 2 -- email
        AND alert_cycle_id = 2)
  );

  -- add the group to the alert
  INSERT INTO alert_x_url_group (alert_id, url_group_id)
    VALUES (test_alert_id, test_url_group);

  -- check the relevant views ('child' is a bit of a misnomer, as each
  -- each group's own id is in the child_ids. However, that makes it
  -- easier to select a group and all its children).
  --
  -- So in this case, for test_url_group, we expect the set of
  -- descendants to be just the test_url_group (id) itself.
  RETURN NEXT results_eq(
    'SELECT unnest(child_ids) FROM url_group_children ' ||
      'WHERE url_group_id = ' || test_url_group || ';',
    array[test_url_group]::int[]);

  -- Ensure the job is listed in the spider_job_alert_type_cycle view
  -- and has an entry for each URL.
  RETURN NEXT results_eq(
    'SELECT url_id FROM spider_job_alert_type_cycle ' ||
      'WHERE job_id = ' || my_job_id ||
      'ORDER BY url_id;',
    array[test_url_id, other_url_id]);

  -- simulate initial document retrieval
  SELECT * FROM spider_update_job_meta(
    my_job_id,
    'Mon, 17 Aug 2015 12:00:00 +0200',
    NULL,
    digest('foo', 'sha256')
  ) INTO meta_rec;

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT is(meta_rec.args, '{}'::jsonb);
  RETURN NEXT ok(meta_rec.last_spider_document_id IS NULL);
  RETURN NEXT ok(meta_rec.last_contents_hash IS NULL);
  RETURN NEXT is(meta_rec.alert_keywords, '{}'::jsonb);

  SELECT * FROM spider_store_document(
    my_job_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.last_spider_document_id,              -- in_old_doc_id
    test_spider_uuid,                              -- in_spider_uuid
    'foo'::bytea,                                  -- in_contents
    digest('foo', 'sha256'),                       -- in_contents_hash
    now(),                                         -- in_ts
    '{}'::json                                     -- alert_matches
  ) INTO doc_rec;

  RETURN NEXT ok(doc_rec.spider_document_id > 0);
  RETURN NEXT is(doc_rec.notifications_created, 0);

  -- simulate updated document retrieval
  SELECT * FROM spider_update_job_meta(
    my_job_id,
    'Mon, 17 Aug 2015 18:00:00 +0200',
    NULL,
    digest('bar', 'sha256')
  ) INTO meta_rec;

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT is(meta_rec.args, '{}'::jsonb);
  RETURN NEXT is(meta_rec.last_spider_document_id,
                 doc_rec.spider_document_id);
  RETURN NEXT ok(meta_rec.last_contents_hash IS NOT NULL);

  -- now, the alert_id should appear, but no keywords are specified
  -- for this alert.
  RETURN NEXT is(meta_rec.alert_keywords,
                 ('{"' || test_alert_id || '": []}')::jsonb);

  RETURN NEXT results_eq(
    'SELECT COUNT(url_id) FROM spider_job_alert_type_cycle ' ||
    'WHERE alert_id = ' || test_alert_id || ';',
    'SELECT 2::BIGINT;'
  );

  SELECT * FROM spider_store_document(
    my_job_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.last_spider_document_id,              -- in_old_doc_id
    test_spider_uuid,                              -- in_spider_uuid
    'bar'::bytea,                                  -- in_contents
    digest('bar', 'sha256'),                       -- in_contents_hash
    now(),                                         -- in_ts
    ('{"' || test_alert_id || '": {"trigger": true}}')::json
                                                  -- alert_matches
  ) INTO doc_rec;

  RETURN NEXT ok(doc_rec.spider_document_id > 0);

  -- If the URL is listed twice, we also generate two notifications,
  -- even for the same alert_id. (Keep in mind that the URLs may well
  -- have different check intervals and may appear in different
  -- sub-groups.)
  RETURN NEXT is(doc_rec.notifications_created, 2);

END
$$ LANGUAGE plpgsql;





CREATE OR REPLACE FUNCTION test_notification_intervals()
RETURNS SETOF TEXT
AS $$
DECLARE
  test_spider_uuid UUID;
  test_user_id INT;
  other_user_id INT;
  test_url_group INT;
  other_url_group INT;
  test_url_id INT;
  other_url_id INT;
  my_job_id INT;
  test_alert_id INT;
  other_alert_id INT;

  meta_rec RECORD;
  doc_rec RECORD;
BEGIN
  PERFORM clean_for_testing();

  -- add a dummy spider
  INSERT INTO spider (spider_uuid, spider_last_hostname)
    VALUES (gen_random_uuid(), 'nohostname')
    RETURNING spider_uuid INTO test_spider_uuid;

  -- add a test user
  INSERT INTO mon_user (user_email) VALUES ('alice@example.com')
    RETURNING user_id INTO test_user_id;

  -- add another user
  INSERT INTO mon_user (user_email) VALUES ('bob@example.com')
    RETURNING user_id INTO other_user_id;

  -- add an example URL
  SELECT url_add('some title', 'http://tests.example.com/about', 'de',
                  test_user_id,
		          2,  -- access_type_id for read/write
                  1,  -- check_frequency_id for hourly notifications
                  'html2markdown', '{}')
  INTO test_url_id;

  SELECT max(job_id) INTO my_job_id FROM spider_job;

  -- add the same URL under another id
  SELECT url_add('other title', 'http://tests.example.com/about', 'de',
                  other_user_id,
		          2,  -- access_type_id for read/write
                  2,  -- check_frequency_id for daily notifications
                  'html2markdown', '{}')
  INTO other_url_id;

  -- add a url_groups and assign the URLs to each user's group
  INSERT INTO url_group
      (url_group_title, url_group_creator_user_id)
    VALUES ('group for alice', test_user_id)
    RETURNING url_group_id INTO test_url_group;

  INSERT INTO url_group
      (url_group_title, url_group_creator_user_id)
    VALUES ('a group for bob', other_user_id)
    RETURNING url_group_id INTO other_url_group;

  UPDATE url SET url_group_id = test_url_group
    WHERE url_id = test_url_id;

  UPDATE url SET url_group_id = other_url_group
    WHERE url_id = other_url_id;

  -- add example alerts
  INSERT INTO alert (user_id) VALUES (test_user_id)
    RETURNING alert_id INTO test_alert_id;
  INSERT INTO alert (user_id) VALUES (other_user_id)
    RETURNING alert_id INTO other_alert_id;

  -- specify a type_cycle for the alerts (otherwise they aren't listed
  -- in the spider_job_alert_type_cycle view).
  INSERT INTO alert_x_type_cycle (alert_id, type_x_cycle_id)
    VALUES (test_alert_id, (
      SELECT type_x_cycle_id FROM type_x_cycle
      WHERE alert_type_id = 2 -- email
        AND alert_cycle_id = 2)
    ),
    (other_alert_id, (
      SELECT type_x_cycle_id FROM type_x_cycle
      WHERE alert_type_id = 2 -- email
        AND alert_cycle_id = 2)
    );

  -- add the groups to their corresponding alerts
  INSERT INTO alert_x_url_group (alert_id, url_group_id)
    VALUES (test_alert_id, test_url_group);
  INSERT INTO alert_x_url_group (alert_id, url_group_id)
    VALUES (other_alert_id, other_url_group);

  -- Ensure the job is listed in the spider_job_alert_type_cycle view
  -- and has an entry for each URL.
  RETURN NEXT results_eq(
    'SELECT url_id FROM spider_job_alert_type_cycle ' ||
      'WHERE job_id = ' || my_job_id ||
      'ORDER BY url_id;',
    array[test_url_id, other_url_id]);

  -- simulate initial document retrieval
  SELECT * FROM spider_update_job_meta(
    my_job_id,
    'Mon, 17 Aug 2015 12:00:00 +0200',
    NULL,
    digest('foo', 'sha256')
  ) INTO meta_rec;

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT is(meta_rec.args, '{}'::jsonb);
  RETURN NEXT ok(meta_rec.last_spider_document_id IS NULL);
  RETURN NEXT ok(meta_rec.last_contents_hash IS NULL);
  RETURN NEXT is(meta_rec.alert_keywords, '{}'::jsonb);

  SELECT * FROM spider_store_document(
    my_job_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.last_spider_document_id,              -- in_old_doc_id
    test_spider_uuid,                              -- in_spider_uuid
    'foo'::bytea,                                  -- in_contents
    digest('foo', 'sha256'),                       -- in_contents_hash
    '2015-08-17 12:00:00 +0200'::TIMESTAMP WITH TIME ZONE,     -- in_ts
    '{}'::json                                     -- alert_matches
  ) INTO doc_rec;

  RETURN NEXT ok(doc_rec.spider_document_id > 0);
  RETURN NEXT is(doc_rec.notifications_created, 0);


  RETURN NEXT results_eq(
    'SELECT COUNT(1) FROM spider_job_alert_type_cycle x ' ||
    'WHERE job_id = ' || my_job_id || ';',
    array[2::BIGINT]
  );

  -- simulate updated document retrieval
  SELECT * FROM spider_update_job_meta(
    my_job_id,
    'Mon, 17 Aug 2015 14:00:00 +0200',
    NULL,
    digest('bar', 'sha256')
  ) INTO meta_rec;

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT is(meta_rec.args, '{}'::jsonb);
  RETURN NEXT is(meta_rec.last_spider_document_id,
                 doc_rec.spider_document_id);
  RETURN NEXT ok(meta_rec.last_contents_hash IS NOT NULL);

  -- The alert_ids should appear, but no keywords are specified for
  -- either alert.
  RETURN NEXT is(meta_rec.alert_keywords,
                 ('{"' || test_alert_id || '": [], ' ||
                  '"' || other_alert_id || '": []}')::jsonb);

  SELECT * FROM spider_store_document(
    my_job_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.last_spider_document_id,              -- in_old_doc_id
    test_spider_uuid,                              -- in_spider_uuid
    'bar'::bytea,                                  -- in_contents
    digest('bar', 'sha256'),                       -- in_contents_hash
    '2015-08-17 14:00:00 +0200'::TIMESTAMP WITH TIME ZONE,     -- in_ts
    ('{"' || test_alert_id || '": {"trigger": true},' ||
     '"' || other_alert_id || '": {"trigger": true}}')::json
                                                  -- alert_matches
  ) INTO doc_rec;

  RETURN NEXT ok(doc_rec.spider_document_id > 0);

  -- Even if the URL is listed twice, we should only generate a single
  -- notification for this change. (As it's just one alert.)
  RETURN NEXT is(doc_rec.notifications_created, 2);

  -- Check if the notifications have a proper creation_ts.
  RETURN NEXT results_eq(
    'SELECT creation_ts FROM notification WHERE url_id = ' ||
      test_url_id || ';',
    array['2015-08-17 14:00:00+02'::TIMESTAMP WITH TIME ZONE]);
  RETURN NEXT results_eq(
    'SELECT creation_ts FROM notification WHERE url_id = ' ||
      other_url_id || ';',
    array['2015-08-17 14:00:00+02'::TIMESTAMP WITH TIME ZONE]);



  RETURN NEXT results_eq(
    'SELECT latest_notification_ts FROM alert_latest_notification ' ||
    'WHERE alert_id = ' || test_alert_id || ' AND url_id = ' || test_url_id ||
    ';',
    array['2015-08-17 14:00:00+02'::TIMESTAMP WITH TIME ZONE]);
  RETURN NEXT results_eq(
    'SELECT latest_notification_ts FROM alert_latest_notification ' ||
    'WHERE alert_id = ' || other_alert_id || ' AND url_id = ' || other_url_id ||
    ';',
    array['2015-08-17 14:00:00+02'::TIMESTAMP WITH TIME ZONE]);



  -- Simulate yet another resource update, just two hours later. Now,
  -- only the user who selected an hourly check should receive a
  -- notification, not the other user who opted for daily checks.
  SELECT * FROM spider_update_job_meta(
    my_job_id,
    'Mon, 17 Aug 2015 16:00:00 +0200',
    NULL,
    digest('bar', 'sha256')
  ) INTO meta_rec;

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT is(meta_rec.args, '{}'::jsonb);
  RETURN NEXT is(meta_rec.last_spider_document_id,
                 doc_rec.spider_document_id);
  RETURN NEXT ok(meta_rec.last_contents_hash IS NOT NULL);

  -- Both alerts should still be listed, as before.
  RETURN NEXT is(meta_rec.alert_keywords,
                 ('{"' || test_alert_id || '": [], ' ||
                  '"' || other_alert_id || '": []}')::jsonb);

  SELECT * FROM spider_store_document(
    my_job_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.last_spider_document_id,              -- in_old_doc_id
    test_spider_uuid,                              -- in_spider_uuid
    'baz'::bytea,                                  -- in_contents
    digest('baz', 'sha256'),                       -- in_contents_hash
    '2015-08-17 16:00:00 +0200'::TIMESTAMP WITH TIME ZONE,     -- in_ts
    ('{"' || test_alert_id || '": {"trigger": true},' ||
     '"' || other_alert_id || '": {"trigger": true}}')::json
                                                  -- alert_matches
  ) INTO doc_rec;

  RETURN NEXT ok(doc_rec.spider_document_id > 0);

  -- Now, for the test_alert_id, we should have two notifications, no
  -- retained ones. But for the other_alert_id, there should be one
  -- retained and one ready for delivery.
  RETURN NEXT results_eq(
    'SELECT COUNT(1)
     FROM notification
     WHERE alert_id = ' || test_alert_id || ' AND is_retained;',
    array[0::bigint]);
  RETURN NEXT results_eq(
    'SELECT COUNT(1)
     FROM notification
     WHERE alert_id = ' || test_alert_id || ' AND NOT is_retained;',
    array[2::bigint]);
  RETURN NEXT results_eq(
    'SELECT COUNT(1)
     FROM notification
     WHERE alert_id = ' || other_alert_id || ' AND is_retained;',
    array[1::bigint]);
  RETURN NEXT results_eq(
    'SELECT COUNT(1)
     FROM notification
     WHERE alert_id = ' || other_alert_id || ' AND NOT is_retained;',
    array[1::bigint]);

END
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION test_varying_xpaths()
RETURNS SETOF TEXT
AS $$
DECLARE
  test_spider_uuid UUID;
  test_user_id INT;
  other_user_id INT;
  test_url_group INT;
  other_url_group INT;
  test_url_id INT;
  other_url_id INT;
  my_job_id INT;
  test_alert_id INT;
  other_alert_id INT;

  meta_rec RECORD;
  doc_rec RECORD;
BEGIN
  PERFORM clean_for_testing();

  -- add a dummy spider
  INSERT INTO spider (spider_uuid, spider_last_hostname)
    VALUES (gen_random_uuid(), 'nohostname')
    RETURNING spider_uuid INTO test_spider_uuid;

  -- add a test user
  INSERT INTO mon_user (user_email) VALUES ('alice@example.com')
    RETURNING user_id INTO test_user_id;

  -- add another user
  INSERT INTO mon_user (user_email) VALUES ('bob@example.com')
    RETURNING user_id INTO other_user_id;

  -- add an example URL
  SELECT url_add('some title', 'http://tests.example.com/about', 'de',
                  test_user_id,
		          2,  -- access_type_id for read/write
                  1,  -- check_frequency_id for hourly notifications
                  'html2markdown', '{"xpath": "//body"}')
  INTO test_url_id;

  SELECT max(job_id) INTO my_job_id FROM spider_job;

  -- add the same URL under another id
  SELECT url_add('other title', 'http://tests.example.com/about', 'de',
                  other_user_id,
		          2,  -- access_type_id for read/write
                  2,  -- check_frequency_id for daily notifications
                  'html2markdown', '{"xpath": "//@id=content"}')
  INTO other_url_id;

  -- add a url_groups and assign the URLs to each user's group
  INSERT INTO url_group
      (url_group_title, url_group_creator_user_id)
    VALUES ('group for alice', test_user_id)
    RETURNING url_group_id INTO test_url_group;

  INSERT INTO url_group
      (url_group_title, url_group_creator_user_id)
    VALUES ('a group for bob', other_user_id)
    RETURNING url_group_id INTO other_url_group;

  UPDATE url SET url_group_id = test_url_group
    WHERE url_id = test_url_id;

  UPDATE url SET url_group_id = other_url_group
    WHERE url_id = other_url_id;

  -- add example alerts
  INSERT INTO alert (user_id) VALUES (test_user_id)
    RETURNING alert_id INTO test_alert_id;
  INSERT INTO alert (user_id) VALUES (other_user_id)
    RETURNING alert_id INTO other_alert_id;

  -- specify a type_cycle for the alerts (otherwise they aren't listed
  -- in the spider_job_alert_type_cycle view).
  INSERT INTO alert_x_type_cycle (alert_id, type_x_cycle_id)
    VALUES (test_alert_id, (
      SELECT type_x_cycle_id FROM type_x_cycle
      WHERE alert_type_id = 2 -- email
        AND alert_cycle_id = 2)
    ),
    (other_alert_id, (
      SELECT type_x_cycle_id FROM type_x_cycle
      WHERE alert_type_id = 2 -- email
        AND alert_cycle_id = 2)
    );

  -- add the groups to their corresponding alerts
  INSERT INTO alert_x_url_group (alert_id, url_group_id)
    VALUES (test_alert_id, test_url_group);
  INSERT INTO alert_x_url_group (alert_id, url_group_id)
    VALUES (other_alert_id, other_url_group);

  -- Ensure the job is listed in the spider_job_alert_type_cycle view
  -- and has an entry for each URL.
  RETURN NEXT results_eq(
    'SELECT url_id FROM spider_job_alert_type_cycle ' ||
      'WHERE job_id = ' || my_job_id ||
      'ORDER BY url_id;',
    array[test_url_id, other_url_id]);

  -- simulate initial document retrieval
  CREATE TEMPORARY TABLE meta_info AS
  SELECT * FROM spider_update_job_meta(
    my_job_id,
    'Mon, 17 Aug 2015 12:00:00 +0200',
    NULL,
    digest('<body></body>', 'sha256')
  );

  RETURN NEXT results_eq(
    'SELECT COUNT(1) FROM meta_info;',
    array[2::BIGINT]
  );

  -- Handle alice's transformation
  SELECT * INTO meta_rec FROM meta_info
  WHERE args->>'xpath' = '//body';

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT ok(meta_rec.last_spider_document_id IS NULL);
  RETURN NEXT ok(meta_rec.last_contents_hash IS NULL);
  RETURN NEXT is(meta_rec.alert_keywords, '{}'::jsonb);

  SELECT * FROM spider_store_document(
    my_job_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.last_spider_document_id,              -- in_old_doc_id
    test_spider_uuid,                              -- in_spider_uuid
    'foo'::bytea,                                  -- in_contents (transformed)
    digest('foo', 'sha256'),                       -- in_contents_hash
    '2015-08-17 12:00:00 +0200'::TIMESTAMP WITH TIME ZONE,     -- in_ts
    '{}'::json                                     -- alert_matches
  ) INTO doc_rec;

  RETURN NEXT ok(doc_rec.spider_document_id > 0);
  RETURN NEXT is(doc_rec.notifications_created, 0);

  -- Handle Bob's transformation
  SELECT * INTO meta_rec FROM meta_info
  WHERE args->>'xpath' = '//@id=content';

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT is(meta_rec.args, '{"xpath": "//@id=content"}'::jsonb);
  RETURN NEXT ok(meta_rec.last_spider_document_id IS NULL);
  RETURN NEXT ok(meta_rec.last_contents_hash IS NULL);
  RETURN NEXT is(meta_rec.alert_keywords, '{}'::jsonb);

  SELECT * FROM spider_store_document(
    my_job_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.last_spider_document_id,              -- in_old_doc_id
    test_spider_uuid,                              -- in_spider_uuid
    'foo'::bytea,                                  -- in_contents (transformed)
    digest('foo', 'sha256'),                       -- in_contents_hash
    '2015-08-17 12:00:00 +0200'::TIMESTAMP WITH TIME ZONE,     -- in_ts
    '{}'::json                                     -- alert_matches
  ) INTO doc_rec;

  RETURN NEXT ok(doc_rec.spider_document_id > 0);
  RETURN NEXT is(doc_rec.notifications_created, 0);


  -- simulate document update
  CREATE TEMPORARY TABLE meta_info_two AS
  SELECT * FROM spider_update_job_meta(
    my_job_id,
    'Wed, 19 Aug 2015 15:00:00 +0200',
    NULL,
    digest('<body><h1>some title</h1></body>', 'sha256')
  );

  RETURN NEXT results_eq(
    'SELECT COUNT(1) FROM meta_info_two;',
    array[2::BIGINT]
  );

  -- Handle alice's second transformation
  SELECT * INTO meta_rec FROM meta_info_two
  WHERE args->>'xpath' = '//body';

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT ok(meta_rec.last_spider_document_id IS NOT NULL);
  RETURN NEXT ok(meta_rec.last_contents_hash IS NOT NULL);
  RETURN NEXT is(meta_rec.alert_keywords, ('{"' || test_alert_id || '": []}')::jsonb);

  SELECT * FROM spider_store_document(
    my_job_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.last_spider_document_id,              -- in_old_doc_id
    test_spider_uuid,                              -- in_spider_uuid
    'foo'::bytea,                                  -- in_contents (transformed)
    digest('foo', 'sha256'),                       -- in_contents_hash
    '2015-08-19 15:00:00 +0200'::TIMESTAMP WITH TIME ZONE,     -- in_ts
    ('{"' || test_alert_id || '": {"trigger": true}}')::json
                                                   -- alert_matches
  ) INTO doc_rec;

  RETURN NEXT ok(doc_rec.spider_document_id > 0);
  RETURN NEXT is(doc_rec.notifications_created, 1);

  -- Handle Bob's second transformation
  SELECT * INTO meta_rec FROM meta_info_two
  WHERE args->>'xpath' = '//@id=content';

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT is(meta_rec.args, '{"xpath": "//@id=content"}'::jsonb);
  RETURN NEXT ok(meta_rec.last_spider_document_id IS NOT NULL);
  RETURN NEXT ok(meta_rec.last_contents_hash IS NOT NULL);
  RETURN NEXT is(meta_rec.alert_keywords, ('{"' || other_alert_id || '": []}')::jsonb);

  SELECT * FROM spider_store_document(
    my_job_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.last_spider_document_id,              -- in_old_doc_id
    test_spider_uuid,                              -- in_spider_uuid
    'foo'::bytea,                                  -- in_contents (transformed)
    digest('foo', 'sha256'),                       -- in_contents_hash
    '2015-08-19 15:00:00 +0200'::TIMESTAMP WITH TIME ZONE,     -- in_ts
    ('{"' || test_alert_id || '": {"trigger": true}}')::json
                                                   -- alert_matches
  ) INTO doc_rec;

  RETURN NEXT ok(doc_rec.spider_document_id > 0);
  RETURN NEXT is(doc_rec.notifications_created, 1);

END
$$ LANGUAGE plpgsql;
