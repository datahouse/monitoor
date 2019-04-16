CREATE FUNCTION test_alert_keyword_update()
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
  INSERT INTO alert (user_id, alert_option_id) VALUES (
    test_user_id,
    1 -- activity
  )
  RETURNING alert_id INTO test_alert_id;

  -- and another one
  INSERT INTO alert (user_id, alert_option_id) VALUES (
    test_user_id,
    1 -- activity
  )
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


CREATE FUNCTION test_notification_on_duplicate_url()
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
  INSERT INTO alert (user_id, alert_option_id)
    VALUES (
      test_user_id,
      1 -- activity
    )
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
  RETURN NEXT ok(meta_rec.latest_doc_id IS NULL);
  RETURN NEXT ok(meta_rec.latest_doc_contents_hash IS NULL);

  SELECT * FROM spider_store_document(
    my_job_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.latest_doc_id,                        -- in_old_doc_id
    test_spider_uuid,                              -- in_spider_uuid
    'foo'::bytea,                                  -- in_contents
    digest('foo', 'sha256'),                       -- in_contents_hash
    'text/markdown',                               -- in_mime_type
    now()                                          -- in_ts
  ) INTO doc_rec;

  RETURN NEXT ok(doc_rec.spider_document_id > 0);

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
  RETURN NEXT is(meta_rec.latest_doc_id, doc_rec.spider_document_id);
  RETURN NEXT ok(meta_rec.latest_doc_contents_hash IS NOT NULL);

  RETURN NEXT results_eq(
    'SELECT COUNT(url_id) FROM spider_job_alert_type_cycle ' ||
    'WHERE alert_id = ' || test_alert_id || ';',
    'SELECT 2::BIGINT;'
  );

  SELECT * FROM spider_store_document(
    my_job_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.latest_doc_id,                        -- in_old_doc_id
    test_spider_uuid,                              -- in_spider_uuid
    'bar'::bytea,                                  -- in_contents
    digest('bar', 'sha256'),                       -- in_contents_hash
    'text/markdown',                               -- in_mime_type
    now()                                          -- in_ts
  ) INTO doc_rec;

  RETURN NEXT ok(doc_rec.spider_document_id > 0);
END
$$ LANGUAGE plpgsql;


-- Simulate an initial and two subsequent document updates within less
-- than a day. Alice and Carol both have an hourly check frequency,
-- but different notification intervals. They still get two
-- notifications each, as the PHP job collects notifications into
-- daily email alerts.
CREATE FUNCTION test_notification_intervals()
RETURNS SETOF TEXT
AS $$
DECLARE
  setup RECORD;

  meta_rec RECORD;
  doc_rec1 RECORD;
  doc_rec2 RECORD;
  doc_rec3 RECORD;
BEGIN
  SELECT * FROM setup_common_test_base() INTO setup;

  -- Ensure the job is listed in the spider_job_alert_type_cycle view
  -- and has an entry for each URL.
  RETURN NEXT results_eq(
    'SELECT url_id FROM spider_job_alert_type_cycle ' ||
      'WHERE job_id = ' || setup.test_job1_id ||
      'ORDER BY url_id;',
    array[setup.alice_url1_id, setup.bob_url1_id,
          setup.carol_url1_id, setup.dave_url1_id]);

  -- simulate initial document retrieval
  SELECT * FROM spider_update_job_meta(
    setup.test_job1_id,
    'Mon, 17 Aug 2015 12:00:00 +0200',
    NULL,
    digest('foo', 'sha256')
  ) INTO meta_rec;

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT is(meta_rec.args, '{"xpath": "//body"}'::jsonb);
  RETURN NEXT ok(meta_rec.latest_doc_id IS NULL);
  RETURN NEXT ok(meta_rec.latest_doc_contents_hash IS NULL);

  -- handle the transformation for Alice and Carol
  SELECT * FROM spider_store_document(
    setup.test_job1_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.latest_doc_id,                        -- in_old_doc_id
    setup.test_spider_uuid,                        -- in_spider_uuid
    'foo'::bytea,                                  -- in_contents
    digest('foo', 'sha256'),                       -- in_contents_hash
    'text/markdown',                               -- in_mime_type
    '2015-08-17 12:00:00 +0200'                    -- in_ts
      ::TIMESTAMP WITH TIME ZONE
  ) INTO doc_rec1;

  RETURN NEXT ok(doc_rec1.spider_document_id > 0);

  -- three alerts configured for this job: for Alice, Bob, Carol and Dave.
  RETURN NEXT is((SELECT COUNT(1) FROM spider_job_alert_type_cycle
                  WHERE job_id = setup.test_job1_id), 4::BIGINT);

  -- simulate updated document retrieval
  SELECT * FROM spider_update_job_meta(
    setup.test_job1_id,
    'Mon, 17 Aug 2015 14:00:00 +0200',
    NULL,
    digest('bar', 'sha256')
  ) INTO meta_rec;

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT is(meta_rec.args, '{"xpath": "//body"}'::jsonb);
  RETURN NEXT is(meta_rec.latest_doc_id, doc_rec1.spider_document_id);
  RETURN NEXT ok(meta_rec.latest_doc_contents_hash IS NOT NULL);

  SELECT * FROM spider_store_document(
    setup.test_job1_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.latest_doc_id,                        -- in_old_doc_id
    setup.test_spider_uuid,                        -- in_spider_uuid
    'bar'::bytea,                                  -- in_contents
    digest('bar', 'sha256'),                       -- in_contents_hash
    'text/markdown',                               -- in_mime_type
    '2015-08-17 14:00:00 +0200'                    -- in_ts
      ::TIMESTAMP WITH TIME ZONE
  ) INTO doc_rec2;

  RETURN NEXT ok(doc_rec2.spider_document_id > 0);

  -- only one pending_change entriy should have been generated (we're
  -- not generating one for Bob, and the transformations for Alice and
  -- Carol match).
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 1::BIGINT);

  -- Check if the notifications have a proper creation_ts.
  RETURN NEXT results_eq(
    'SELECT creation_ts FROM pending_change WHERE ' ||
      setup.alice_url1_id || ' = ANY(url_ids);',
    array['2015-08-17 14:00:00+02'::TIMESTAMP WITH TIME ZONE]);
  RETURN NEXT results_eq(
    'SELECT creation_ts FROM pending_change WHERE ' ||
      setup.carol_url1_id || ' = ANY(url_ids);',
    array['2015-08-17 14:00:00+02'::TIMESTAMP WITH TIME ZONE]);


  -- Simulate yet another resource update, just two hours later. Now,
  -- only the user who selected an hourly check should receive a
  -- notification, not the other user who opted for daily checks.
  SELECT * FROM spider_update_job_meta(
    setup.test_job1_id,
    'Mon, 17 Aug 2015 16:00:00 +0200',
    NULL,
    digest('bar', 'sha256')
  ) INTO meta_rec;

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT is(meta_rec.args, '{"xpath": "//body"}'::jsonb);
  RETURN NEXT is(meta_rec.latest_doc_id, doc_rec2.spider_document_id);
  RETURN NEXT ok(meta_rec.latest_doc_contents_hash IS NOT NULL);

  SELECT * FROM spider_store_document(
    setup.test_job1_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.latest_doc_id,                        -- in_old_doc_id
    setup.test_spider_uuid,                        -- in_spider_uuid
    'baz'::bytea,                                  -- in_contents
    digest('baz', 'sha256'),                       -- in_contents_hash
    'text/markdown',                               -- in_mime_type
    '2015-08-17 16:00:00 +0200'                    -- in_ts
      ::TIMESTAMP WITH TIME ZONE
  ) INTO doc_rec3;

  RETURN NEXT ok(doc_rec3.spider_document_id > 0);

  -- There should now be 2 changes pending, now.
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 2::BIGINT);
END
$$ LANGUAGE plpgsql;


-- Simulate an initial and two subsequent document updates within less
-- than a day. Bob wants hourly checks, while Dave requested only a
-- single check per day.
CREATE FUNCTION test_varying_check_frequencies()
RETURNS SETOF TEXT
AS $$
DECLARE
  setup RECORD;

  meta_rec RECORD;
  doc_rec1 RECORD;
  doc_rec2 RECORD;
  doc_rec3 RECORD;
BEGIN
  SELECT * FROM setup_common_test_base() INTO setup;

  -- Ensure the job is listed in the spider_job_alert_type_cycle view
  -- and has an entry for each URL.
  RETURN NEXT results_eq(
    'SELECT url_id FROM spider_job_alert_type_cycle ' ||
      'WHERE job_id = ' || setup.test_job1_id ||
      'ORDER BY url_id;',
    array[setup.alice_url1_id, setup.bob_url1_id,
          setup.carol_url1_id, setup.dave_url1_id]);

  -- simulate initial document retrieval
  CREATE TEMPORARY TABLE meta_info1 AS
  SELECT * FROM spider_update_job_meta(
    setup.test_job1_id,
    'Mon, 17 Aug 2015 12:00:00 +0200',
    NULL,
    digest('foo', 'sha256')
  );

  RETURN NEXT is((SELECT COUNT(1) FROM meta_info1), 2::BIGINT);

  -- We're only interested in transformations for Bob and Dave in this test.
  SELECT * INTO meta_rec FROM meta_info1
    WHERE args->>'xpath' = '//@id=content';

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT is(meta_rec.args, '{"xpath": "//@id=content"}'::jsonb);
  RETURN NEXT ok(meta_rec.latest_doc_id IS NULL);
  RETURN NEXT ok(meta_rec.latest_doc_contents_hash IS NULL);

  -- handle the transformation for Bob and Dave
  SELECT * FROM spider_store_document(
    setup.test_job1_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.latest_doc_id,                        -- in_old_doc_id
    setup.test_spider_uuid,                        -- in_spider_uuid
    'foo'::bytea,                                  -- in_contents
    digest('foo', 'sha256'),                       -- in_contents_hash
    'text/markdown',                               -- in_mime_type
    '2015-08-17 12:00:00 +0200'                    -- in_ts
      ::TIMESTAMP WITH TIME ZONE
  ) INTO doc_rec1;

  RETURN NEXT ok(doc_rec1.spider_document_id > 0);

  -- three alerts configured for this job: for Alice, Bob, Carol, and Dave.
  RETURN NEXT is((SELECT COUNT(1) FROM spider_job_alert_type_cycle
                  WHERE job_id = setup.test_job1_id), 4::BIGINT);

  -- simulate updated document retrieval
  CREATE TEMPORARY TABLE meta_info2 AS
  SELECT * FROM spider_update_job_meta(
    setup.test_job1_id,
    'Mon, 17 Aug 2015 14:00:00 +0200',
    NULL,
    digest('bar', 'sha256')
  );

  RETURN NEXT is((SELECT COUNT(1) FROM meta_info2), 2::BIGINT);

  -- We're only interested in transformations for Bob and Dave in this test.
  SELECT * INTO meta_rec FROM meta_info2
    WHERE args->>'xpath' = '//@id=content';

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT is(meta_rec.args, '{"xpath": "//@id=content"}'::jsonb);
  RETURN NEXT is(meta_rec.latest_doc_id, doc_rec1.spider_document_id);
  RETURN NEXT ok(meta_rec.latest_doc_contents_hash IS NOT NULL);

  SELECT * FROM spider_store_document(
    setup.test_job1_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.latest_doc_id,                        -- in_old_doc_id
    setup.test_spider_uuid,                        -- in_spider_uuid
    'bar'::bytea,                                  -- in_contents
    digest('bar', 'sha256'),                       -- in_contents_hash
    'text/markdown',                               -- in_mime_type
    '2015-08-17 14:00:00 +0200'                    -- in_ts
      ::TIMESTAMP WITH TIME ZONE
  ) INTO doc_rec2;

  RETURN NEXT ok(doc_rec2.spider_document_id > 0);

  -- two pending_change entries should have been generated: one for
  -- Bob and one for Dave - they have different check_frequencies.
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 2::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.alice_url1_id = ANY(url_ids)), 0::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.bob_url1_id = ANY(url_ids)), 1::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.carol_url1_id = ANY(url_ids)), 0::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.dave_url1_id = ANY(url_ids)), 1::BIGINT);

  -- Check if the notifications have a proper creation_ts.
  RETURN NEXT is((SELECT creation_ts FROM pending_change
                  WHERE setup.bob_url1_id = ANY(url_ids)),
                 '2015-08-17 14:00:00+02'::TIMESTAMP WITH TIME ZONE);
  RETURN NEXT is((SELECT creation_ts FROM pending_change
                  WHERE setup.dave_url1_id = ANY(url_ids)),
                 '2015-08-17 14:00:00+02'::TIMESTAMP WITH TIME ZONE);


  -- Simulate yet another resource update, just two hours later. Now,
  -- only the user who selected an hourly check should receive a
  -- notification, not the other user who opted for daily checks.
  CREATE TEMPORARY TABLE meta_info3 AS
  SELECT * FROM spider_update_job_meta(
    setup.test_job1_id,
    'Mon, 17 Aug 2015 16:00:00 +0200',
    NULL,
    digest('bar', 'sha256')
  );

  RETURN NEXT is((SELECT COUNT(1) FROM meta_info3), 2::BIGINT);

  -- We're only interested in transformations for Bob and Dave in this test.
  SELECT * INTO meta_rec FROM meta_info3
    WHERE args->>'xpath' = '//@id=content';

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT is(meta_rec.args, '{"xpath": "//@id=content"}'::jsonb);
  RETURN NEXT is(meta_rec.latest_doc_id, doc_rec2.spider_document_id);
  RETURN NEXT ok(meta_rec.latest_doc_contents_hash IS NOT NULL);

  SELECT * FROM spider_store_document(
    setup.test_job1_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.latest_doc_id,                        -- in_old_doc_id
    setup.test_spider_uuid,                        -- in_spider_uuid
    'baz'::bytea,                                  -- in_contents
    digest('baz', 'sha256'),                       -- in_contents_hash
    'text/markdown',                               -- in_mime_type
    '2015-08-17 16:00:00 +0200'                    -- in_ts
      ::TIMESTAMP WITH TIME ZONE
  ) INTO doc_rec3;

  RETURN NEXT ok(doc_rec3.spider_document_id > 0);

  -- There should now be 4 changes pending.
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 4::BIGINT);
END
$$ LANGUAGE plpgsql;


CREATE FUNCTION test_varying_xpaths()
RETURNS SETOF TEXT
AS $$
DECLARE
  setup RECORD;
  test_alert_id INT;
  other_alert_id INT;

  meta_rec RECORD;
  doc_rec RECORD;
BEGIN
  SELECT * FROM setup_common_test_base() INTO setup;

  -- Ensure the job is listed in the spider_job_alert_type_cycle view
  -- and has an entry for each URL.
  RETURN NEXT results_eq(
    'SELECT url_id FROM spider_job_alert_type_cycle ' ||
      'WHERE job_id = ' || setup.test_job1_id ||
      'ORDER BY url_id;',
    array[setup.alice_url1_id, setup.bob_url1_id,
          setup.carol_url1_id, setup.dave_url1_id]);

  -- simulate initial document retrieval
  CREATE TEMPORARY TABLE meta_info AS
  SELECT * FROM spider_update_job_meta(
    setup.test_job1_id,
    'Mon, 17 Aug 2015 12:00:00 +0200',
    NULL,
    digest('<body></body>', 'sha256')
  );

  RETURN NEXT is((SELECT COUNT(1) FROM meta_info), 2::BIGINT);

  -- Handle alice's transformation
  SELECT * INTO meta_rec FROM meta_info
  WHERE args->>'xpath' = '//body';

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT ok(meta_rec.latest_doc_id IS NULL);
  RETURN NEXT ok(meta_rec.latest_doc_contents_hash IS NULL);

  SELECT * FROM spider_store_document(
    setup.test_job1_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.latest_doc_id,                        -- in_old_doc_id
    setup.test_spider_uuid,                        -- in_spider_uuid
    'foo'::bytea,                                  -- in_contents (transformed)
    digest('foo', 'sha256'),                       -- in_contents_hash
    'text/markdown',                               -- in_mime_type
    '2015-08-17 12:00:00 +0200'                    -- in_ts
      ::TIMESTAMP WITH TIME ZONE
  ) INTO doc_rec;

  RETURN NEXT ok(doc_rec.spider_document_id > 0);
  -- Just the reference document, no change should be generated, yet.
  RETURN NEXT ok((SELECT COUNT(1) = 0 FROM pending_change));

  -- Handle Bob's transformation
  SELECT * INTO meta_rec FROM meta_info
  WHERE args->>'xpath' = '//@id=content';

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT is(meta_rec.args, '{"xpath": "//@id=content"}'::jsonb);
  RETURN NEXT ok(meta_rec.latest_doc_id IS NULL);
  RETURN NEXT ok(meta_rec.latest_doc_contents_hash IS NULL);

  SELECT * FROM spider_store_document(
    setup.test_job1_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.latest_doc_id,                        -- in_old_doc_id
    setup.test_spider_uuid,                        -- in_spider_uuid
    'foo'::bytea,                                  -- in_contents (transformed)
    digest('foo', 'sha256'),                       -- in_contents_hash
    'text/markdown',                               -- in_mime_type
    '2015-08-17 12:00:00 +0200'                    -- in_ts
      ::TIMESTAMP WITH TIME ZONE
  ) INTO doc_rec;

  RETURN NEXT ok(doc_rec.spider_document_id > 0);
  -- Still no change expected.
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 0::BIGINT);

  -- simulate document update
  CREATE TEMPORARY TABLE meta_info_two AS
  SELECT * FROM spider_update_job_meta(
    setup.test_job1_id,
    'Wed, 19 Aug 2015 15:00:00 +0200',
    NULL,
    digest('<body><h1>some title</h1></body>', 'sha256')
  );

  RETURN NEXT is((SELECT COUNT(1) FROM meta_info_two), 2::BIGINT);

  -- Handle alice's second transformation
  SELECT * INTO meta_rec FROM meta_info_two
  WHERE args->>'xpath' = '//body';

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT ok(meta_rec.latest_doc_id IS NOT NULL);
  RETURN NEXT ok(meta_rec.latest_doc_contents_hash IS NOT NULL);

  SELECT * FROM spider_store_document(
    setup.test_job1_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.latest_doc_id,                        -- in_old_doc_id
    setup.test_spider_uuid,                        -- in_spider_uuid
    'foo'::bytea,                                  -- in_contents (transformed)
    digest('foo', 'sha256'),                       -- in_contents_hash
    'text/markdown',                               -- in_mime_type
    '2015-08-19 15:00:00 +0200'                    -- in_ts
      ::TIMESTAMP WITH TIME ZONE
  ) INTO doc_rec;

  RETURN NEXT ok(doc_rec.spider_document_id > 0);
  -- There should now be one pending change for Alice.
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 1::BIGINT);

  -- Handle Bob's second transformation
  SELECT * INTO meta_rec FROM meta_info_two
  WHERE args->>'xpath' = '//@id=content';

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT is(meta_rec.args, '{"xpath": "//@id=content"}'::jsonb);
  RETURN NEXT ok(meta_rec.latest_doc_id IS NOT NULL);
  RETURN NEXT ok(meta_rec.latest_doc_contents_hash IS NOT NULL);

  SELECT * FROM spider_store_document(
    setup.test_job1_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.latest_doc_id,                        -- in_old_doc_id
    setup.test_spider_uuid,                        -- in_spider_uuid
    'foo'::bytea,                                  -- in_contents (transformed)
    digest('foo', 'sha256'),                       -- in_contents_hash
    'text/markdown',                               -- in_mime_type
    '2015-08-19 15:00:00 +0200'                    -- in_ts
      ::TIMESTAMP WITH TIME ZONE
  ) INTO doc_rec;

  -- There should now be a pending change for Bob as well, totalling at
  -- 3 pending changes, one for Alice & Carol, one for Bob, and one for Dave.
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 3::BIGINT);

  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.alice_url1_id = ANY(url_ids)), 1::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.bob_url1_id = ANY(url_ids)), 1::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.carol_url1_id = ANY(url_ids)), 1::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.dave_url1_id = ANY(url_ids)), 1::BIGINT);

  RETURN NEXT ok(doc_rec.spider_document_id > 0);
END
$$ LANGUAGE plpgsql;

-- in contrast to the test above, this one also uses user Carol, who has
-- an xpath that matches the one of Alice.
CREATE FUNCTION test_varying_and_common_xpaths()
RETURNS SETOF TEXT
AS $$
DECLARE
  setup RECORD;
  test_alert_id INT;
  other_alert_id INT;

  meta_rec RECORD;
  doc_rec RECORD;
BEGIN
  SELECT * FROM setup_common_test_base() INTO setup;

  -- Ensure the job is listed in the spider_job_alert_type_cycle view
  -- and has an entry for each URL.
  RETURN NEXT results_eq(
    'SELECT url_id FROM spider_job_alert_type_cycle ' ||
      'WHERE job_id = ' || setup.test_job1_id ||
      'ORDER BY url_id;',
    array[setup.alice_url1_id, setup.bob_url1_id,
          setup.carol_url1_id, setup.dave_url1_id]);

  -- simulate initial document retrieval
  CREATE TEMPORARY TABLE meta_info AS
  SELECT * FROM spider_update_job_meta(
    setup.test_job1_id,
    'Mon, 17 Aug 2015 12:00:00 +0200',
    NULL,
    digest('<body></body>', 'sha256')
  );

  RETURN NEXT ok((SELECT COUNT(1) = 2 FROM meta_info));

  -- Handle the transformation for Alice and Carol
  SELECT * INTO meta_rec FROM meta_info
  WHERE args->>'xpath' = '//body';

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT ok(meta_rec.latest_doc_id IS NULL);
  RETURN NEXT ok(meta_rec.latest_doc_contents_hash IS NULL);

  SELECT * FROM spider_store_document(
    setup.test_job1_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.latest_doc_id,                        -- in_old_doc_id
    setup.test_spider_uuid,                        -- in_spider_uuid
    'foo'::bytea,                                  -- in_contents (transformed)
    digest('foo', 'sha256'),                       -- in_contents_hash
    'text/markdown',                               -- in_mime_type
    '2015-08-17 12:00:00 +0200'                    -- in_ts
      ::TIMESTAMP WITH TIME ZONE
  ) INTO doc_rec;

  RETURN NEXT ok(doc_rec.spider_document_id > 0);
  -- Just the reference document, no change should be generated, yet.
  RETURN NEXT ok((SELECT COUNT(1) = 0 FROM pending_change));

  -- Handle the transformation for Bob
  SELECT * INTO meta_rec FROM meta_info
  WHERE args->>'xpath' = '//@id=content';

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT is(meta_rec.args, '{"xpath": "//@id=content"}'::jsonb);
  RETURN NEXT ok(meta_rec.latest_doc_id IS NULL);
  RETURN NEXT ok(meta_rec.latest_doc_contents_hash IS NULL);

  SELECT * FROM spider_store_document(
    setup.test_job1_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.latest_doc_id,                        -- in_old_doc_id
    setup.test_spider_uuid,                        -- in_spider_uuid
    'foo'::bytea,                                  -- in_contents (transformed)
    digest('foo', 'sha256'),                       -- in_contents_hash
    'text/markdown',                               -- in_mime_type
    '2015-08-17 12:00:00 +0200'                    -- in_ts
      ::TIMESTAMP WITH TIME ZONE
  ) INTO doc_rec;

  RETURN NEXT ok(doc_rec.spider_document_id > 0);
  -- Still no change expected.
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 0::BIGINT);

  -- simulate document update, 3 hours after the reference document
  CREATE TEMPORARY TABLE meta_info_two AS
  SELECT * FROM spider_update_job_meta(
    setup.test_job1_id,
    'Wed, 19 Aug 2015 15:00:00 +0200',
    NULL,
    digest('<body><h1>some title</h1></body>', 'sha256')
  );

  RETURN NEXT is((SELECT COUNT(1) FROM meta_info_two), 2::BIGINT);

  -- Handle transformation of the changed resource for Alice and Carol
  SELECT * INTO meta_rec FROM meta_info_two
  WHERE args->>'xpath' = '//body';

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT ok(meta_rec.latest_doc_id IS NOT NULL);
  RETURN NEXT ok(meta_rec.latest_doc_contents_hash IS NOT NULL);

  SELECT * FROM spider_store_document(
    setup.test_job1_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.latest_doc_id,                        -- in_old_doc_id
    setup.test_spider_uuid,                        -- in_spider_uuid
    'foo'::bytea,                                  -- in_contents (transformed)
    digest('foo', 'sha256'),                       -- in_contents_hash
    'text/markdown',                               -- in_mime_type
    '2015-08-19 15:00:00 +0200'                    -- in_ts
      ::TIMESTAMP WITH TIME ZONE
  ) INTO doc_rec;

  RETURN NEXT ok(doc_rec.spider_document_id > 0);
  -- There should now be one pending change that's good for Alice and Carol.
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 1::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.alice_url1_id = ANY(url_ids)), 1::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.carol_url1_id = ANY(url_ids)), 1::BIGINT);
  -- Nothing for Bob or Dave, yet.
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.bob_url1_id = ANY(url_ids)), 0::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.dave_url1_id = ANY(url_ids)), 0::BIGINT);

  -- Handle transformation of the changed resource for Bob
  SELECT * INTO meta_rec FROM meta_info_two
  WHERE args->>'xpath' = '//@id=content';

  RETURN NEXT ok(meta_rec.xfrm_id > 0);
  RETURN NEXT is(meta_rec.commands, 'html2markdown');
  RETURN NEXT is(meta_rec.args, '{"xpath": "//@id=content"}'::jsonb);
  RETURN NEXT ok(meta_rec.latest_doc_id IS NOT NULL);
  RETURN NEXT ok(meta_rec.latest_doc_contents_hash IS NOT NULL);

  SELECT * FROM spider_store_document(
    setup.test_job1_id,
    get_xfrm_id(meta_rec.commands, meta_rec.args), -- xfrm_id
    meta_rec.latest_doc_id,                        -- in_old_doc_id
    setup.test_spider_uuid,                        -- in_spider_uuid
    'foo'::bytea,                                  -- in_contents (transformed)
    digest('foo', 'sha256'),                       -- in_contents_hash
    'text/markdown',                               -- in_mime_type
    '2015-08-19 15:00:00 +0200'                    -- in_ts
      ::TIMESTAMP WITH TIME ZONE
  ) INTO doc_rec;

  RETURN NEXT ok(doc_rec.spider_document_id > 0);

  -- Now, we should have three pending changes: one for Alice and Carol,
  -- one for Bob and one for Dave.
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 3::BIGINT);

  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.alice_url1_id = ANY(url_ids)), 1::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.bob_url1_id = ANY(url_ids)), 1::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.carol_url1_id = ANY(url_ids)), 1::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.dave_url1_id = ANY(url_ids)), 1::BIGINT);
END
$$ LANGUAGE plpgsql;
