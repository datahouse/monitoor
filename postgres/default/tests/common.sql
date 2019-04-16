CREATE FUNCTION clean_for_testing()
RETURNS void
AS $$
  -- delete all existing jobs and changes (will be rolled back, anyways)
  DELETE FROM alert;
  DELETE FROM alert_x_url_group;
  DELETE FROM alert_keyword;
  DELETE FROM pending_change;
  DELETE FROM mon_user;
  DELETE FROM url;
  DELETE FROM url_group;
  DELETE FROM notification;
  DELETE FROM notification_x_keyword;
  DELETE FROM spider_document;
  DELETE FROM spider_change;
  DELETE FROM spider_job;
$$ LANGUAGE SQL;

-- This functions sets up four test users: Alice, Bob, Carol and Dave.
CREATE FUNCTION setup_common_test_base()
RETURNS TABLE (
  test_spider_uuid UUID,
  test_job1_id INT,
  alice_user_id INT,
  alice_url1_id INT,
  alice_url_group_id INT,
  alice_alert_id INT,
  bob_user_id INT,
  bob_url1_id INT,
  bob_url_group_id INT,
  bob_alert_id INT,
  carol_user_id INT,
  carol_url1_id INT,
  carol_url_group_id INT,
  carol_alert_id INT,
  dave_user_id INT,
  dave_url1_id INT,
  dave_url_group_id INT,
  dave_alert_id INT
) AS $$
DECLARE
  test_spider_uuid UUID;
  test_job1_id INT;
  alice_user_id INT;
  alice_url1_id INT;
  alice_url_group_id INT;
  alice_alert_id INT;
  bob_user_id INT;
  bob_url1_id INT;
  bob_url_group_id INT;
  bob_alert_id INT;
  carol_user_id INT;
  carol_url1_id INT;
  carol_url_group_id INT;
  carol_alert_id INT;
  dave_user_id INT;
  dave_url1_id INT;
  dave_url_group_id INT;
  dave_alert_id INT;
BEGIN
  PERFORM clean_for_testing();

  -- add a dummy spider
  INSERT INTO spider (spider_uuid, spider_last_hostname)
    VALUES (gen_random_uuid(), 'nohostname')
    RETURNING spider_uuid INTO test_spider_uuid;

  -- add a test user (alice)
  INSERT INTO mon_user (user_email) VALUES ('alice@example.com')
    RETURNING user_id INTO alice_user_id;

  -- add an example URL
  SELECT url_add('some title', 'http://tests.example.com/about', 'de',
                  alice_user_id,
                  2,  -- access_type_id for read/write
                  1,  -- check_frequency_id for hourly notifications
                  'html2markdown', '{"xpath": "//body"}')
  INTO alice_url1_id;

  SELECT max(job_id) INTO test_job1_id FROM spider_job;

  -- add a url_group and assign the URL to user Alice
  INSERT INTO url_group
      (url_group_title, url_group_creator_user_id)
    VALUES ('group for alice', alice_user_id)
    RETURNING url_group_id INTO alice_url_group_id;

  UPDATE url SET url_group_id = alice_url_group_id
    WHERE url_id = alice_url1_id;

  -- add example alerts for Alice
  INSERT INTO alert (user_id, alert_option_id)
    VALUES (
      alice_user_id,
      1 -- activity
    )
    RETURNING alert_id INTO alice_alert_id;

  -- specify a type_cycle for the alerts (otherwise they aren't listed
  -- in the spider_job_alert_type_cycle view).
  INSERT INTO alert_x_type_cycle (alert_id, type_x_cycle_id)
    VALUES (alice_alert_id, (
      SELECT type_x_cycle_id FROM type_x_cycle
      WHERE alert_type_id = 2      -- email
        AND alert_cycle_id = 2)    -- immediate
    );

  -- add the groups to their corresponding alerts
  INSERT INTO alert_x_url_group (alert_id, url_group_id)
    VALUES (alice_alert_id, alice_url_group_id);


  -- add test user Bob
  INSERT INTO mon_user (user_email) VALUES ('bob@example.com')
    RETURNING user_id INTO bob_user_id;

  -- add the same URL under another id and with a different xpath.
  SELECT url_add('other title', 'http://tests.example.com/about', 'de',
                  bob_user_id,
                  2,  -- access_type_id for read/write
                  1,  -- check_frequency_id for hourly checks
                  'html2markdown', '{"xpath": "//@id=content"}')
  INTO bob_url1_id;

  -- add a url_group and assign the URL to user Alice
  INSERT INTO url_group
      (url_group_title, url_group_creator_user_id)
    VALUES ('a group for bob', bob_user_id)
    RETURNING url_group_id INTO bob_url_group_id;

  UPDATE url SET url_group_id = bob_url_group_id
    WHERE url_id = bob_url1_id;

  -- add example alerts for Bob
  INSERT INTO alert (user_id, alert_option_id)
    VALUES (
      bob_user_id,
      1 -- activity
    )
    RETURNING alert_id INTO bob_alert_id;

  -- specify a type_cycle for the alerts (otherwise they aren't listed
  -- in the spider_job_alert_type_cycle view).
  INSERT INTO alert_x_type_cycle (alert_id, type_x_cycle_id)
    VALUES (bob_alert_id, (
      SELECT type_x_cycle_id FROM type_x_cycle
      WHERE alert_type_id = 2      -- email
        AND alert_cycle_id = 2)    -- immediate
    );

  -- add the groups to their corresponding alerts
  INSERT INTO alert_x_url_group (alert_id, url_group_id)
    VALUES (bob_alert_id, bob_url_group_id);


  -- add test user Carol
  INSERT INTO mon_user (user_email) VALUES ('carol@example.com')
    RETURNING user_id INTO carol_user_id;

  -- add an example URL - with the same xpath as Alice, this time.
  SELECT url_add('some title', 'http://tests.example.com/about', 'de',
                  carol_user_id,
                  2,  -- access_type_id for read/write
                  1,  -- check_frequency_id for hourly notifications
                  'html2markdown', '{"xpath": "//body"}')
  INTO carol_url1_id;

  SELECT max(job_id) INTO test_job1_id FROM spider_job;

  -- add a url_group and assign the URL to user Carol
  INSERT INTO url_group
      (url_group_title, url_group_creator_user_id)
    VALUES ('group for carol', carol_user_id)
    RETURNING url_group_id INTO carol_url_group_id;

  UPDATE url SET url_group_id = carol_url_group_id
    WHERE url_id = carol_url1_id;

  -- add example alerts for Carol
  INSERT INTO alert (user_id, alert_option_id)
    VALUES (
      carol_user_id,
      1 -- activity
    )
    RETURNING alert_id INTO carol_alert_id;

  -- specify a type_cycle for the alerts (otherwise they aren't listed
  -- in the spider_job_alert_type_cycle view).
  INSERT INTO alert_x_type_cycle (alert_id, type_x_cycle_id)
    VALUES (carol_alert_id, (
      SELECT type_x_cycle_id FROM type_x_cycle
      WHERE alert_type_id = 2      -- email
        AND alert_cycle_id = 1)    -- only daily emails
    );

  -- add the groups to their corresponding alerts
  INSERT INTO alert_x_url_group (alert_id, url_group_id)
    VALUES (carol_alert_id, carol_url_group_id);


  -- add test user Dave
  INSERT INTO mon_user (user_email) VALUES ('dave@example.com')
    RETURNING user_id INTO dave_user_id;

  -- add the same URL under another id and with a different xpath.
  SELECT url_add('other title', 'http://tests.example.com/about', 'de',
                  dave_user_id,
                  2,  -- access_type_id for read/write
                  2,  -- check_frequency_id for daily checks
                  'html2markdown', '{"xpath": "//@id=content"}')
  INTO dave_url1_id;

  -- add a url_group and assign the URL to user Alice
  INSERT INTO url_group
      (url_group_title, url_group_creator_user_id)
    VALUES ('a group for dave', dave_user_id)
    RETURNING url_group_id INTO dave_url_group_id;

  UPDATE url SET url_group_id = dave_url_group_id
    WHERE url_id = dave_url1_id;

  -- add example alerts for Dave
  INSERT INTO alert (user_id, alert_option_id)
    VALUES (
      dave_user_id,
      1 -- activity
    )
    RETURNING alert_id INTO dave_alert_id;

  -- specify a type_cycle for the alerts (otherwise they aren't listed
  -- in the spider_job_alert_type_cycle view).
  INSERT INTO alert_x_type_cycle (alert_id, type_x_cycle_id)
    VALUES (dave_alert_id, (
      SELECT type_x_cycle_id FROM type_x_cycle
      WHERE alert_type_id = 2      -- email
        AND alert_cycle_id = 2)    -- immediate
    );

  -- add the groups to their corresponding alerts
  INSERT INTO alert_x_url_group (alert_id, url_group_id)
    VALUES (dave_alert_id, dave_url_group_id);

  RETURN QUERY SELECT
    test_spider_uuid,
    test_job1_id,
    alice_user_id,
    alice_url1_id,
    alice_url_group_id,
    alice_alert_id,
    bob_user_id,
    bob_url1_id,
    bob_url_group_id,
    bob_alert_id,
    carol_user_id,
    carol_url1_id,
    carol_url_group_id,
    carol_alert_id,
    dave_user_id,
    dave_url1_id,
    dave_url_group_id,
    dave_alert_id;
END
$$ LANGUAGE plpgsql;
