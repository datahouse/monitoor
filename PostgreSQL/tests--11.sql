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

  SELECT job_id INTO my_job_id FROM spider_job;

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

  SELECT job_id INTO my_job_id FROM spider_job;

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

  SELECT job_id INTO my_job_id FROM spider_job;

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
