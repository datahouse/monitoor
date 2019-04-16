CREATE FUNCTION test_materialize_varying_xpaths_changes()
RETURNS SETOF TEXT
AS $$
DECLARE
  setup RECORD;
  doc_id1 INT;
  doc_id2 INT;
  pchange_alice_url1_id INT;
  pchange_bob_url1_id INT;
  result_change_id INT;
BEGIN
  SELECT * FROM setup_common_test_base() INTO setup;

  -- simulate a first change, with path '//body' for Alice
  INSERT INTO spider_document (
    job_id, reception_ts, xfrm_id, contents, contents_hash
  ) VALUES (
    setup.test_job1_id,
    '2017-04-01 12:00:00'::TIMESTAMP WITH TIME ZONE,
    get_xfrm_id('html2markdown', '{"xpath": "//body"}'::JSONB),
    'content 1 all',
    digest('content 1 all', 'sha256')
  ) RETURNING spider_document_id INTO doc_id1;

  INSERT INTO spider_document (
    job_id, reception_ts, xfrm_id, contents, contents_hash
  ) VALUES (
    setup.test_job1_id,
    '2017-04-01 12:00:00'::TIMESTAMP WITH TIME ZONE,
    get_xfrm_id('html2markdown', '{"xpath": "//body"}'::JSONB),
    'content 2 all',
    digest('content 2 all', 'sha256')
  ) RETURNING spider_document_id INTO doc_id2;

  -- insert a mock pending change, covering Alice's url_id.
  INSERT INTO pending_change (
      check_frequency_id,
      url_ids,
      creation_ts,
      not_before_ts,
      old_doc_id,
      new_doc_id,
      delta
    ) VALUES (
      1,  -- hourly
      array[setup.alice_url1_id],
      '2017-04-01 12:00:00'::TIMESTAMP WITH TIME ZONE,
      '2017-04-01 13:00:00'::TIMESTAMP WITH TIME ZONE,
      doc_id1,           -- old_doc_id
      doc_id2,           -- new_doc_id
      '{"add": "content 2"}'::JSONB        -- delta
    )
    RETURNING pending_change_id INTO pchange_alice_url1_id;

  -- same document with xpath '//@id=content' for Bob
  INSERT INTO spider_document (
    job_id, reception_ts, xfrm_id, contents, contents_hash
  ) VALUES (
    setup.test_job1_id,
    '2017-04-01 12:00:00'::TIMESTAMP WITH TIME ZONE,
    get_xfrm_id('html2markdown', '{"xpath": "//@id=content"}'::JSONB),
    'content 1 limited',
    digest('content 1 limited', 'sha256')
  ) RETURNING spider_document_id INTO doc_id1;

  INSERT INTO spider_document (
    job_id, reception_ts, xfrm_id, contents, contents_hash
  ) VALUES (
    setup.test_job1_id,
    '2017-04-01 12:00:00'::TIMESTAMP WITH TIME ZONE,
    get_xfrm_id('html2markdown', '{"xpath": "//@id=content"}'::JSONB),
    'content 2 limited',
    digest('content 2 limited', 'sha256')
  ) RETURNING spider_document_id INTO doc_id2;

  -- insert a mock pending change, covering Alice's and Bob's url_id.
  INSERT INTO pending_change (
      check_frequency_id,
      url_ids,
      creation_ts,
      not_before_ts,
      old_doc_id,
      new_doc_id,
      delta
    ) VALUES (
      1,  -- hourly
      array[setup.bob_url1_id],
      '2017-04-01 12:00:00'::TIMESTAMP WITH TIME ZONE,
      '2017-04-01 13:00:00'::TIMESTAMP WITH TIME ZONE,
      doc_id1,           -- old_doc_id
      doc_id2,           -- new_doc_id
      '{"add": "content 2"}'::JSONB        -- delta
    )
    RETURNING pending_change_id INTO pchange_bob_url1_id;

  -- There should one pending change, covering both.
  RETURN NEXT ok((SELECT COUNT(1) = 2 FROM pending_change));
  RETURN NEXT ok((SELECT COUNT(1) = 1 FROM pending_change
                  WHERE setup.alice_url1_id = ANY(url_ids)));
  RETURN NEXT ok((SELECT COUNT(1) = 1 FROM pending_change
                  WHERE setup.bob_url1_id = ANY(url_ids)));

  -- materialize change for Alice
  SELECT materialize_change(
    ('[' || pchange_alice_url1_id || ']')::JSON,    -- pendingChangeIds
    ('{"' || setup.alice_alert_id
          || '": {"trigger": true}}')::JSON,        -- alertMatches
    '{"add": [], "del": {}}'::JSON,                 -- sections
    NULL,                                           -- not an external source
    setup.test_spider_uuid,
    '2017-04-01 12:00:01'::TIMESTAMP WITH TIME ZONE
  ) INTO result_change_id;

  -- materialize change for Bob
  SELECT materialize_change(
    ('[' || pchange_bob_url1_id || ']')::JSON,      -- pendingChangeIds
    ('{"' || setup.bob_alert_id
          || '": {"trigger": true}}')::JSON,        -- alertMatches
    '{"add": [], "del": {}}'::JSON,                 -- sections
    NULL,                                           -- not an external source
    setup.test_spider_uuid,
    '2017-04-01 12:00:01'::TIMESTAMP WITH TIME ZONE
  ) INTO result_change_id;

  -- Both pending changes should be consumed ..
  RETURN NEXT ok((SELECT COUNT(1) = 0 FROM pending_change));

  -- .. and materialized into real changes.
  RETURN NEXT ok((SELECT COUNT(1) = 2 FROM change));
END
$$ LANGUAGE plpgsql;


CREATE FUNCTION test_materialize_changes_for_varying_check_frequencies()
RETURNS SETOF TEXT
AS $$
DECLARE
  setup RECORD;
  doc_id1 INT;
  doc_id2 INT;
  doc_id3 INT;
  bob_pchange1_id INT;
  bob_pchange2_id INT;
  dave_pchange1_id INT;
  dave_pchange2_id INT;
  result_change_id INT;
BEGIN
  SELECT * FROM setup_common_test_base() INTO setup;

  -- add 3 test documents for Bob and Dave (same transformation)
  INSERT INTO spider_document (
    job_id, reception_ts, xfrm_id, contents, contents_hash
  ) VALUES (
    setup.test_job1_id,
    '2017-04-01 12:00:00'::TIMESTAMP WITH TIME ZONE,
    get_xfrm_id('html2markdown', '{"xpath": "//@id=content"}'::JSONB),
    'content 1',
    digest('content 1', 'sha256')
  ) RETURNING spider_document_id INTO doc_id1;

  INSERT INTO spider_document (
    job_id, reception_ts, xfrm_id, contents, contents_hash
  ) VALUES (
    setup.test_job1_id,
    '2017-04-01 15:00:00'::TIMESTAMP WITH TIME ZONE,
    get_xfrm_id('html2markdown', '{"xpath": "//@id=content"}'::JSONB),
    'content 2',
    digest('content 2', 'sha256')
  ) RETURNING spider_document_id INTO doc_id2;

  -- insert two mock pending changes for this change: one for Bob, one for Dave
  INSERT INTO pending_change (
      check_frequency_id,
      url_ids,
      creation_ts,
      not_before_ts,
      old_doc_id,
      new_doc_id,
      delta
    ) VALUES (
      1,  -- hourly
      array[setup.bob_url1_id],
      '2017-04-01 15:00:00'::TIMESTAMP WITH TIME ZONE,
      '2017-04-01 15:00:00'::TIMESTAMP WITH TIME ZONE,
      doc_id1,           -- old_doc_id
      doc_id2,           -- new_doc_id
      '[{"add": "content 2"}]'::JSONB        -- delta
    )
    RETURNING pending_change_id INTO bob_pchange1_id;

  INSERT INTO pending_change (
      check_frequency_id,
      url_ids,
      creation_ts,
      not_before_ts,
      old_doc_id,
      new_doc_id,
      delta
    ) VALUES (
      2,  -- daily
      array[setup.dave_url1_id],
      '2017-04-01 15:00:00'::TIMESTAMP WITH TIME ZONE,
      '2017-04-01 15:00:00'::TIMESTAMP WITH TIME ZONE,
      doc_id1,           -- old_doc_id
      doc_id2,           -- new_doc_id
      '[{"add": "content 2"}]'::JSONB        -- delta
    )
    RETURNING pending_change_id INTO dave_pchange1_id;

  CREATE TEMPORARY TABLE pending_changes1 AS
  SELECT * FROM get_pending_changes_for(
    '2017-04-01 15:00:00'::TIMESTAMP WITH TIME ZONE,
    NULL
  );

  RETURN NEXT is((SELECT COUNT(1) FROM pending_changes1), 2::BIGINT);

  -- materialize changes for Bob and Dave
  SELECT materialize_change(
    ('[' || bob_pchange1_id || ']')::JSON,          -- pendingChangeIds
    ('{"' || setup.bob_alert_id
          || '": {"trigger": true}}')::JSON,        -- alertMatches
    '[{"add": [], "del": []}]'::JSON,               -- sections
    NULL,                                           -- not an external source
    setup.test_spider_uuid,
    '2017-04-01 15:00:01'::TIMESTAMP WITH TIME ZONE
  ) INTO result_change_id;

  -- the generated change must only cover Bob's url_id.
  RETURN NEXT is(
    (SELECT array_agg(url_id) FROM change_x_url
       WHERE change_id = result_change_id),
    array[setup.bob_url1_id]
  );

  SELECT materialize_change(
    ('[' || dave_pchange1_id || ']')::JSON,         -- pendingChangeIds
    ('{"' || setup.dave_alert_id
          || '": {"trigger": true}}')::JSON,        -- alertMatches
    '[{"add": [], "del": []}]'::JSON,               -- sections
    NULL,                                           -- not an external source
    setup.test_spider_uuid,
    '2017-04-01 15:00:01'::TIMESTAMP WITH TIME ZONE
  ) INTO result_change_id;

  -- the generated change must only cover Dave's url_id.
  RETURN NEXT is(
    (SELECT array_agg(url_id) FROM change_x_url
       WHERE change_id = result_change_id),
    array[setup.dave_url1_id]
  );

  -- no more pending changes, but one change each for Bob and Dave
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 0::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM change), 2::BIGINT,
                 'expecting two changes: one for Bob and one for Dave');
  RETURN NEXT is((SELECT COUNT(1) FROM notification), 2::BIGINT,
                 'expecting two notifications: one for Bob and one for Dave');

  -- at 4pm, there should be no pending change
  CREATE TEMPORARY TABLE pending_changes2 AS
  SELECT * FROM get_pending_changes_for(
    '2017-04-01 16:00:00'::TIMESTAMP WITH TIME ZONE,
    NULL
  );
  RETURN NEXT is((SELECT COUNT(1) FROM pending_changes2), 0::BIGINT,
                 '3pm: no pending change after first materialization round');


  -- simulate another retrieved version at 6pm on April 1st.
  INSERT INTO spider_document (
    job_id, reception_ts, xfrm_id, contents, contents_hash
  ) VALUES (
    setup.test_job1_id,
    '2017-04-01 18:00:00'::TIMESTAMP WITH TIME ZONE,
    get_xfrm_id('html2markdown', '{"xpath": "//@id=content"}'::JSONB),
    'content 3',
    digest('content 3', 'sha256')
  ) RETURNING spider_document_id INTO doc_id3;

  -- insert two mock pending changes for both users, again
  INSERT INTO pending_change (
      check_frequency_id,
      url_ids,
      creation_ts,
      not_before_ts,
      old_doc_id,
      new_doc_id,
      delta
    ) VALUES (
      1,  -- hourly
      array[setup.bob_url1_id],
      '2017-04-01 18:00:00'::TIMESTAMP WITH TIME ZONE,
      '2017-04-01 18:00:00'::TIMESTAMP WITH TIME ZONE,
      doc_id2,           -- old_doc_id
      doc_id3,           -- new_doc_id
      '[{"add": "content 3"}]'::JSONB        -- delta
    )
    RETURNING pending_change_id INTO bob_pchange2_id;

  INSERT INTO pending_change (
      check_frequency_id,
      url_ids,
      creation_ts,
      not_before_ts,
      old_doc_id,
      new_doc_id,
      delta
    ) VALUES (
      2,  -- daily
      array[setup.dave_url1_id],
      '2017-04-01 18:00:00'::TIMESTAMP WITH TIME ZONE,
      '2017-04-02 15:00:00'::TIMESTAMP WITH TIME ZONE,
      doc_id2,           -- old_doc_id
      doc_id3,           -- new_doc_id
      '[{"add": "content 3"}]'::JSONB        -- delta
    )
    RETURNING pending_change_id INTO dave_pchange2_id;

  -- shortly after 6pm, there should be only one pending change that's
  -- ready to be materialized for Bob. The one for Dave isn't ready
  -- for materialization until April 2nd.
  CREATE TEMPORARY TABLE pending_changes3 AS
  SELECT * FROM get_pending_changes_for(
    '2017-04-01 18:00:01'::TIMESTAMP WITH TIME ZONE,
    NULL
  );
  RETURN NEXT is((SELECT COUNT(1) FROM pending_changes3), 1::BIGINT,
    '6pm: another update - expecting one pending change to be ready');

  -- test materializing the changes for Bob
  SELECT materialize_change(
    ('[' || bob_pchange2_id || ']')::JSON,          -- pendingChangeIds
    ('{"' || setup.bob_alert_id
          || '": {"trigger": true}}')::JSON,        -- alertMatches
    '[{"add": [], "del": []}]'::JSON,               -- sections
    NULL,                                           -- not an external source
    setup.test_spider_uuid,
    '2017-04-01 18:00:01'::TIMESTAMP WITH TIME ZONE
  ) INTO result_change_id;

  -- the generated change must only cover Bob's url_id, no change
  -- should have been generated for Dave.
  RETURN NEXT is(
    (SELECT array_agg(url_id) FROM change_x_url
       WHERE change_id = result_change_id),
    array[setup.bob_url1_id]
  );

  -- Pending change for Dave remains, but Bob's should have been
  -- materialized.
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 1::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM change), 3::BIGINT,
                 'expecting three changes: two for Bob and one for Dave');
  RETURN NEXT is((SELECT COUNT(1) FROM notification), 3::BIGINT,
                 'expecting three notifications: two for Bob and one for Dave');

  -- at 7pm, there should be no pending change that's ready to be
  -- materialized.
  CREATE TEMPORARY TABLE pending_changes4 AS
  SELECT * FROM get_pending_changes_for(
    '2017-04-01 17:00:00'::TIMESTAMP WITH TIME ZONE,
    NULL
  );
  RETURN NEXT is((SELECT COUNT(1) FROM pending_changes4), 0::BIGINT,
    '7pm: no pending changes ready for materialization');

  -- turn the clock forward to April 2nd, 3pm
  CREATE TEMPORARY TABLE pending_changes5 AS
  SELECT * FROM get_pending_changes_for(
    '2017-04-02 15:00:00'::TIMESTAMP WITH TIME ZONE,
    NULL
  );
  RETURN NEXT is((SELECT COUNT(1) FROM pending_changes5), 1::BIGINT,
    'next day: the pending change for Dave may now be materialized');

  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 1::BIGINT);
  RETURN NEXT is((SELECT url_ids FROM pending_change),
                 array[setup.dave_url1_id]);

  -- test materializing the changes for Dave - which shouldn't
  -- materialize on April 1st, yet. The backend will still forward the
  -- info back to the database with a 'trigger: false' flag.
  SELECT materialize_change(
    ('[' || dave_pchange2_id || ']')::JSON,         -- pendingChangeIds
    ('{"' || setup.dave_alert_id
          || '": {"trigger": true}}')::JSON,        -- alertMatches
    '[{"add": [], "del": []}]'::JSON,               -- sections
    NULL,                                           -- not an external source
    setup.test_spider_uuid,
    '2017-04-02 15:00:01'::TIMESTAMP WITH TIME ZONE
  ) INTO result_change_id;

  -- materialized change should be linked to Dave's URL id
  RETURN NEXT is(
    (SELECT array_agg(url_id) FROM change_x_url
       WHERE change_id = result_change_id),
    array[setup.dave_url1_id],
    E'materialized change should be linked to the URL id of Dave (only)'
  );

  -- No more pending changes, all 4 should have been delivered.
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 0::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM change), 4::BIGINT,
                 'expecting four changes: two for Bob and two for Dave');
  RETURN NEXT is((SELECT COUNT(1) FROM notification), 4::BIGINT,
                 'expecting four notifications: two for Bob and two for Dave');
END
$$ LANGUAGE plpgsql;


-- Simulate a total of three changes for a URL monitored by Bob and Dave
-- with equal keywords: kiwi and banana. However, Bob wants us to check
-- hourly, while Dave chose daily checks, only.
--
-- A first change triggers for both on keyword "banana". The second change
-- introduces "kiwis" and must only trigger for Bob.
CREATE FUNCTION test_materialize_changes_for_keywords_and_varying_check_frequencies()
RETURNS SETOF TEXT
AS $$
DECLARE
  setup RECORD;
  doc_id1 INT;
  doc_id2 INT;
  doc_id3 INT;
  doc_id4 INT;
  bob_pchange1_id INT;
  bob_pchange2_id INT;
  bob_pchange3_id INT;
  dave_pchange1_id INT;
  dave_pchange2_id INT;
  dave_pchange3_id INT;
  result_change_id INT;
BEGIN
  SELECT * FROM setup_common_test_base() INTO setup;

  -- add keywords to the alerts of Bob and Dave.
  UPDATE alert
    SET alert_option_id = 2 -- keywords
    WHERE alert_id IN (setup.bob_alert_id, setup.dave_alert_id);

  INSERT INTO alert_keyword (alert_id, alert_keyword)
    VALUES
      (setup.bob_alert_id, 'banana'),
      (setup.bob_alert_id, 'kiwi'),
      (setup.dave_alert_id, 'banana'),
      (setup.dave_alert_id, 'kiwi');


  -- add two test documents for Bob and Dave (same transformation)
  INSERT INTO spider_document (
    job_id, reception_ts, xfrm_id, contents, contents_hash
  ) VALUES (
    setup.test_job1_id,
    '2017-04-01 12:00:00'::TIMESTAMP WITH TIME ZONE,
    get_xfrm_id('html2markdown', '{"xpath": "//@id=content"}'::JSONB),
    'content 1',
    digest('content 1', 'sha256')
  ) RETURNING spider_document_id INTO doc_id1;

  INSERT INTO spider_document (
    job_id, reception_ts, xfrm_id, contents, contents_hash
  ) VALUES (
    setup.test_job1_id,
    '2017-04-01 15:00:00'::TIMESTAMP WITH TIME ZONE,
    get_xfrm_id('html2markdown', '{"xpath": "//@id=content"}'::JSONB),
    'content 2 with bananas added',
    digest('content 2 with bananas added', 'sha256')
  ) RETURNING spider_document_id INTO doc_id2;

  -- insert two mock pending changes for the first "banana" change: one
  -- for Bob, one for Dave
  INSERT INTO pending_change (
      check_frequency_id,
      url_ids,
      creation_ts,
      not_before_ts,
      old_doc_id,
      new_doc_id,
      delta
    ) VALUES (
      1,  -- hourly
      array[setup.bob_url1_id],
      '2017-04-01 15:00:00'::TIMESTAMP WITH TIME ZONE,
      '2017-04-01 15:00:00'::TIMESTAMP WITH TIME ZONE,
      doc_id1,           -- old_doc_id
      doc_id2,           -- new_doc_id
      '[{"del": "content 2", "add": "content 2 with bananas added"}]'::JSONB        -- delta
    )
    RETURNING pending_change_id INTO bob_pchange1_id;

  INSERT INTO pending_change (
      check_frequency_id,
      url_ids,
      creation_ts,
      not_before_ts,
      old_doc_id,
      new_doc_id,
      delta
    ) VALUES (
      2,  -- daily
      array[setup.dave_url1_id],
      '2017-04-01 15:00:00'::TIMESTAMP WITH TIME ZONE,
      '2017-04-01 15:00:00'::TIMESTAMP WITH TIME ZONE,
      doc_id1,           -- old_doc_id
      doc_id2,           -- new_doc_id
      '[{"del": "content 2", "add": "content 2 with bananas added"}]'::JSONB        -- delta
    )
    RETURNING pending_change_id INTO dave_pchange1_id;

  -- materialize the "banana" change for Bob
  PERFORM materialize_change(
    ('[' || bob_pchange1_id || ']')::JSON,          -- pendingChangeIds
    ('{"' || setup.bob_alert_id
          || '": {"trigger": true, '
          || '    "matches": ["banana"], '
          || '    "match_positions": {"0": [[16]]}}}')::JSON,    -- alertMatches
    '[{"add": [], "del": []}]'::JSON,               -- sections
    NULL,                                           -- not an external source
    setup.test_spider_uuid,
    '2017-04-01 15:00:01'::TIMESTAMP WITH TIME ZONE
  );

  -- materialize the "banana" change for Dave
  PERFORM materialize_change(
    ('[' || dave_pchange1_id || ']')::JSON,         -- pendingChangeIds
    ('{"' || setup.dave_alert_id
          || '": {"trigger": true, '
          || '    "matches": ["banana"], '
          || '    "match_positions": {"0": [[16]]}}}')::JSON,    -- alertMatches
    '[{"add": [], "del": []}]'::JSON,               -- sections
    NULL,                                           -- not an external source
    setup.test_spider_uuid,
    '2017-04-01 15:00:01'::TIMESTAMP WITH TIME ZONE
  );

  -- simulate another retrieved version at 6pm on April 1st, adding "kiwi"
  INSERT INTO spider_document (
    job_id, reception_ts, xfrm_id, contents, contents_hash
  ) VALUES (
    setup.test_job1_id,
    '2017-04-01 18:00:00'::TIMESTAMP WITH TIME ZONE,
    get_xfrm_id('html2markdown', '{"xpath": "//@id=content"}'::JSONB),
    'content 3 with kiwis and bananas',
    digest('content 3 with kiwis and bananas', 'sha256')
  ) RETURNING spider_document_id INTO doc_id3;

  -- insert two mock pending changes for both users, again
  INSERT INTO pending_change (
      check_frequency_id,
      url_ids,
      creation_ts,
      not_before_ts,
      old_doc_id,
      new_doc_id,
      delta
    ) VALUES (
      1,  -- hourly
      array[setup.bob_url1_id],
      '2017-04-01 18:00:00'::TIMESTAMP WITH TIME ZONE,
      '2017-04-01 18:00:00'::TIMESTAMP WITH TIME ZONE,
      doc_id2,           -- old_doc_id
      doc_id3,           -- new_doc_id
      (   '[{"del": "content 2 with bananas added", '
        ||  '"add": "content 3 with kiwis and bananas"}]'
      )::JSONB        -- delta
    )
    RETURNING pending_change_id INTO bob_pchange2_id;

  INSERT INTO pending_change (
      check_frequency_id,
      url_ids,
      creation_ts,
      not_before_ts,
      old_doc_id,
      new_doc_id,
      delta
    ) VALUES (
      2,  -- daily
      array[setup.dave_url1_id],
      '2017-04-01 18:00:00'::TIMESTAMP WITH TIME ZONE,
      '2017-04-02 15:00:00'::TIMESTAMP WITH TIME ZONE,
      doc_id2,           -- old_doc_id
      doc_id3,           -- new_doc_id
      (   '[{"del": "content 2 with bananas added", '
        ||  '"add": "content 3 with kiwis and bananas"}]'
      )::JSONB        -- delta
    )
    RETURNING pending_change_id INTO dave_pchange2_id;

  -- shortly after 6pm, there should be only one pending change that's
  -- ready to be materialized for Bob. The one for Dave isn't ready
  -- for materialization until April 2nd.
  CREATE TEMPORARY TABLE pending_changes3 AS
  SELECT * FROM get_pending_changes_for(
    '2017-04-01 18:00:01'::TIMESTAMP WITH TIME ZONE,
    NULL
  );
  RETURN NEXT is((SELECT COUNT(1) FROM pending_changes3), 1::BIGINT,
    '6pm: another update - expecting one pending change to be ready');

  -- test materializing the changes for Bob
  SELECT materialize_change(
    ('[' || bob_pchange2_id || ']')::JSON,          -- pendingChangeIds
    ('{"' || setup.bob_alert_id
          || '": {"trigger": true, '
          || '    "matches": ["banana", "kiwi"], '
          || '    "match_positions": {"0": [[26]], '
          || '                        "1": [[16]]}}}')::JSON,    -- alertMatches
    '[{"add": [], "del": []}]'::JSON,               -- sections
    NULL,                                           -- not an external source
    setup.test_spider_uuid,
    '2017-04-01 18:00:01'::TIMESTAMP WITH TIME ZONE
  ) INTO result_change_id;

  -- the generated change must only cover Bob's url_id, no change
  -- should have been generated for Dave.
  RETURN NEXT is(
    (SELECT
         array_agg(url_id::INT) OVER (ORDER BY url_id)
       FROM change_x_url
       WHERE change_id = result_change_id),
    array[setup.bob_url1_id]
  );

  -- Pending change for Dave remains, but Bob's should have been
  -- materialized.
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 1::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM change), 3::BIGINT,
                 'expecting three changes: two for Bob and one for Dave');
  RETURN NEXT is((SELECT COUNT(1) FROM notification), 3::BIGINT,
                 'expecting three notifications: two for Bob and one for Dave');

  -- at 7pm, there should be no pending change that's ready to be
  -- materialized.
  CREATE TEMPORARY TABLE pending_changes_at_seven AS
  SELECT * FROM get_pending_changes_for(
    '2017-04-01 17:00:00'::TIMESTAMP WITH TIME ZONE,
    NULL
  );
  RETURN NEXT is((SELECT COUNT(1) FROM pending_changes_at_seven), 0::BIGINT,
    '7pm: no pending changes ready for materialization');


  -- simulate the fourth version at 9pm on April 1st, dropping both
  -- keywords.
  INSERT INTO spider_document (
    job_id, reception_ts, xfrm_id, contents, contents_hash
  ) VALUES (
    setup.test_job1_id,
    '2017-04-01 21:00:00'::TIMESTAMP WITH TIME ZONE,
    get_xfrm_id('html2markdown', '{"xpath": "//@id=content"}'::JSONB),
    'content 4',
    digest('content 4', 'sha256')
  ) RETURNING spider_document_id INTO doc_id4;

  -- and insert two mock pending changes for both users a last time
  INSERT INTO pending_change (
      check_frequency_id,
      url_ids,
      creation_ts,
      not_before_ts,
      old_doc_id,
      new_doc_id,
      delta
    ) VALUES (
      1,  -- hourly
      array[setup.bob_url1_id],
      '2017-04-01 21:00:00'::TIMESTAMP WITH TIME ZONE,
      '2017-04-01 21:00:00'::TIMESTAMP WITH TIME ZONE,
      doc_id2,           -- old_doc_id
      doc_id3,           -- new_doc_id
      (   '[{"del": "content 3 with kiwis and bananas", '
        ||  '"add": "content 4"}]'
      )::JSONB        -- delta
    )
    RETURNING pending_change_id INTO bob_pchange3_id;

  INSERT INTO pending_change (
      check_frequency_id,
      url_ids,
      creation_ts,
      not_before_ts,
      old_doc_id,
      new_doc_id,
      delta
    ) VALUES (
      2,  -- daily
      array[setup.dave_url1_id],
      '2017-04-01 21:00:00'::TIMESTAMP WITH TIME ZONE,
      '2017-04-02 15:00:00'::TIMESTAMP WITH TIME ZONE,
      doc_id2,           -- old_doc_id
      doc_id3,           -- new_doc_id
      (   '[{"del": "content 3 with kiwis and bananas", '
        ||  '"add": "content 4"}]'
      )::JSONB        -- delta
    )
    RETURNING pending_change_id INTO dave_pchange3_id;

  -- we should now see 3 pending changes
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 3::BIGINT,
    E'nine o\'clock: pending changes: two for Dave, one for Bob');
  -- '

  -- one for Bob and two for Dave
  RETURN NEXT is(
    (
      SELECT array_agg(url_ids)
        FROM (
          SELECT pending_change_id, url_ids
          FROM pending_change
          ORDER BY pending_change_id
        ) AS x
    ),
    array[array[setup.dave_url1_id],
          array[setup.bob_url1_id],
          array[setup.dave_url1_id]],
    E'expecting three separate changes, for one url each');

  RETURN NEXT is((SELECT COUNT(1) FROM change), 3::BIGINT,
                 'expecting three changes: two for Bob and one for Dave');
  RETURN NEXT is((SELECT COUNT(1) FROM notification), 3::BIGINT,
                 'expecting three notifications: two for Bob and one for Dave');

  -- Determine pending changes due as of 9pm.
  CREATE TEMPORARY TABLE pending_changes_at_nine AS
    SELECT * FROM get_pending_changes_for(
      '2017-04-01 21:00:01'::TIMESTAMP WITH TIME ZONE,
      NULL
    );

  -- Only one change for Bob should be deliverable.
  RETURN NEXT is(
    (SELECT COUNT(1) FROM pending_changes_at_nine),
    1::BIGINT,
    E'nine o\'clock: only one deliverable pending change for Bob');

  -- test materializing the change for Bob
  SELECT materialize_change(
    ('[' || bob_pchange3_id || ']')::JSON,          -- pendingChangeIds
    ('{"' || setup.bob_alert_id
          || '": {"trigger": false}}')::JSON,       -- alertMatches
    '[{"add": [], "del": []}]'::JSON,               -- sections
    NULL,                                           -- not an external source
    setup.test_spider_uuid,
    '2017-04-01 21:00:01'::TIMESTAMP WITH TIME ZONE
  ) INTO result_change_id;

  -- no additional change should have been generated.
  RETURN NEXT ok(result_change_id IS NULL,
    'must not generate another change for Bob, no keyword matches');
  RETURN NEXT is((SELECT COUNT(1) FROM change), 3::BIGINT,
                 'expecting three changes: two for Bob and one for Dave');
  RETURN NEXT is((SELECT COUNT(1) FROM notification), 3::BIGINT,
                 'expecting three notifications: two for Bob and one for Dave');

  -- turn the clock forward to April 2nd, 3pm
  CREATE TEMPORARY TABLE pending_changes6 AS
  SELECT * FROM get_pending_changes_for(
    '2017-04-02 15:00:00'::TIMESTAMP WITH TIME ZONE,
    NULL
  );
  RETURN NEXT is((SELECT COUNT(1) FROM pending_changes6), 1::BIGINT,
    'next day: the pending change for Dave may now be materialized');

  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 2::BIGINT,
    'two changes for Dave now pending, one adding and one removing kiwis');
  RETURN NEXT is((SELECT array_agg(url_ids) FROM pending_change),
                 array[array[setup.dave_url1_id], array[setup.dave_url1_id]],
    E'both pending changes should belong to Dave\'s url.');

--  RETURN NEXT is(
--    (
--      SELECT row_to_json(row(job_id, agg_delta))::JSONB
--      FROM get_pending_changes_for('2017-04-02 15:00:01', NULL)
--    ),
--    (
--      '{"job_id": ' || setup.test_job1_id || ', '
--      || '"agg_delta": []}'
--    )::JSONB
--  );



  RETURN NEXT is((SELECT COUNT(1) FROM change), 3::BIGINT,
                 'expecting three changes: two for Bob and one for Dave');
  RETURN NEXT is((SELECT COUNT(1) FROM notification), 3::BIGINT,
                 'expecting three notifications: two for Bob and one for Dave');

  -- Test materializing the changes for Dave -- in theory, the 'kiwi'
  -- keyword doesn't match. Therefore we test with 'trigger: false'
  -- here. However, in practice, the backend probably counts this as a
  -- match.
  SELECT materialize_change(
    ('[' || dave_pchange2_id || ','
         || dave_pchange3_id || ']')::JSON,         -- pendingChangeIds
    ('{"' || setup.dave_alert_id
          || '": {"trigger": false}}')::JSON,       -- alertMatches
    '[{"add": [], "del": []}]'::JSON,               -- sections
    NULL,                                           -- not an external source
    setup.test_spider_uuid,
    '2017-04-02 15:00:01'::TIMESTAMP WITH TIME ZONE
  ) INTO result_change_id;

  -- no additional change should have been generated.
  RETURN NEXT ok(result_change_id IS NULL,
    'must not generate a change for Dave: '
    || 'the daily check would have missed the kiwi keyword');

  -- No more pending changes, all 4 should have been delivered.
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 0::BIGINT,
                 'expecting no more pending changes');
  RETURN NEXT is((SELECT COUNT(1) FROM change), 3::BIGINT,
                 'expecting three changes: two for Bob and one for Dave');
  RETURN NEXT is((SELECT COUNT(1) FROM notification), 3::BIGINT,
                 'expecting three notifications: two for Bob and one for Dave');
END
$$ LANGUAGE plpgsql;





-- Tests a change for Alice and Carol, who use the same
-- xpath. Therefore resulting in only one common pending_change.
CREATE FUNCTION test_materialize_common_xpath_change()
RETURNS SETOF TEXT
AS $$
DECLARE
  setup RECORD;
  doc_id1 INT;
  doc_id2 INT;
  pchange_id INT;
  result_change_id INT;
BEGIN
  SELECT * FROM setup_common_test_base() INTO setup;

  -- simulate a first change, with path '//body' for Alice and Carol
  INSERT INTO spider_document (
    job_id, reception_ts, xfrm_id, contents, contents_hash
  ) VALUES (
    setup.test_job1_id,
    '2017-04-01 12:00:00'::TIMESTAMP WITH TIME ZONE,
    get_xfrm_id('html2markdown', '{"xpath": "//body"}'::JSONB),
    'content 1 all',
    digest('content 1 all', 'sha256')
  ) RETURNING spider_document_id INTO doc_id1;

  INSERT INTO spider_document (
    job_id, reception_ts, xfrm_id, contents, contents_hash
  ) VALUES (
    setup.test_job1_id,
    '2017-04-01 12:00:00'::TIMESTAMP WITH TIME ZONE,
    get_xfrm_id('html2markdown', '{"xpath": "//body"}'::JSONB),
    'content 2 all',
    digest('content 2 all', 'sha256')
  ) RETURNING spider_document_id INTO doc_id2;

  -- insert a mock pending change, covering the url ids of Alice and Carol
  INSERT INTO pending_change (
      check_frequency_id,
      url_ids,
      creation_ts,
      not_before_ts,
      old_doc_id,
      new_doc_id,
      delta
    ) VALUES (
      1,  -- hourly
      array[setup.alice_url1_id, setup.carol_url1_id],
      '2017-04-01 12:00:00'::TIMESTAMP WITH TIME ZONE,
      '2017-04-01 13:00:00'::TIMESTAMP WITH TIME ZONE,
      doc_id1,                                      -- old_doc_id
      doc_id2,                                      -- new_doc_id
      '{"add": "content 2 all"}'::JSONB             -- delta
    )
    RETURNING pending_change_id INTO pchange_id;

  -- There should one pending change, covering both.
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 1::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.alice_url1_id = ANY(url_ids)), 1::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.carol_url1_id = ANY(url_ids)), 1::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.bob_url1_id = ANY(url_ids)), 0::BIGINT);

  -- materialize the pending change for Alice and Carol
  SELECT materialize_change(
    ('[' || pchange_id || ']')::JSON,               -- pendingChangeIds
    ('{"' || setup.alice_alert_id
          || '": {"trigger": true}, "'
          || setup.carol_alert_id
          || '": {"trigger": true}}')::JSON,        -- alertMatches
    '[{"add": [], "del": []}]'::JSON,               -- sections
    NULL,                                           -- not an external source
    setup.test_spider_uuid,
    '2017-04-01 12:00:01'::TIMESTAMP WITH TIME ZONE
  ) INTO result_change_id;

  -- The the pending change should be consumed ..
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 0::BIGINT);

  -- .. and materialized into a real change.
  RETURN NEXT is((SELECT COUNT(1) FROM change), 1::BIGINT);

  -- As there are two users watching the same URL, this should result
  -- in two notifications for that change.
  RETURN NEXT is((SELECT COUNT(1) FROM notification), 2::BIGINT);
END
$$ LANGUAGE plpgsql;


-- Test changes for Alice and Carol with the same xpath but simulate
-- different filters: Alice uses an activity alert, while Carol
-- filters by "banana".
--
-- A first change includes the keyword, but not the second.
CREATE FUNCTION test_materialize_common_xpath_different_keywords()
RETURNS SETOF TEXT
AS $$
DECLARE
  setup RECORD;
  doc_id1 INT;
  doc_id2 INT;
  pchange_id INT;
  result_change_id INT;
  match_positions1 JSONB;
BEGIN
  SELECT * FROM setup_common_test_base() INTO setup;

  -- add keywords to Carol's alert
  UPDATE alert
    SET alert_option_id = 2 -- keywords
    WHERE alert_id = setup.carol_alert_id;

  INSERT INTO alert_keyword (alert_id, alert_keyword)
    VALUES (setup.carol_alert_id, 'banana');

  -- simulate a first change, with path '//body' for Alice and Carol,
  -- including the keyword "banana".
  INSERT INTO spider_document (
    job_id, reception_ts, xfrm_id, contents, contents_hash
  ) VALUES (
    setup.test_job1_id,
    '2017-04-01 12:00:00'::TIMESTAMP WITH TIME ZONE,
    get_xfrm_id('html2markdown', '{"xpath": "//body"}'::JSONB),
    'content 1',
    digest('content 1', 'sha256')
  ) RETURNING spider_document_id INTO doc_id1;

  INSERT INTO spider_document (
    job_id, reception_ts, xfrm_id, contents, contents_hash
  ) VALUES (
    setup.test_job1_id,
    '2017-04-01 12:00:00'::TIMESTAMP WITH TIME ZONE,
    get_xfrm_id('html2markdown', '{"xpath": "//body"}'::JSONB),
    'content 2 with bananas added',
    digest('content 2 with bananas added', 'sha256')
  ) RETURNING spider_document_id INTO doc_id2;

  -- insert a mock pending change, covering the url ids of Alice and Carol
  INSERT INTO pending_change (
      check_frequency_id,
      url_ids,
      creation_ts,
      not_before_ts,
      old_doc_id,
      new_doc_id,
      delta
    ) VALUES (
      1,  -- hourly
      array[setup.alice_url1_id, setup.carol_url1_id],
      '2017-04-01 12:00:00'::TIMESTAMP WITH TIME ZONE,
      '2017-04-01 13:00:00'::TIMESTAMP WITH TIME ZONE,
      doc_id1,                                                  -- old_doc_id
      doc_id2,                                                  -- new_doc_id
      '[{"add": "content 2 with bananas added"}]'::JSONB        -- delta
    )
    RETURNING pending_change_id INTO pchange_id;

  -- There should be one pending change, covering both.
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 1::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.alice_url1_id = ANY(url_ids)), 1::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.carol_url1_id = ANY(url_ids)), 1::BIGINT);
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change
                  WHERE setup.bob_url1_id = ANY(url_ids)), 0::BIGINT);

  -- materialize the pending change for Alice and Carol
  SELECT materialize_change(
    ('[' || pchange_id || ']')::JSON,               -- pendingChangeIds
    ('{"' || setup.alice_alert_id
          || '": {"trigger": true}, "'
          || setup.carol_alert_id
          || '": {"trigger": true, '
          ||     '"matches": ["banana"], '
          ||     '"match_positions": {"0": [[16]]}}'
          || '}')::JSON,          -- alertMatches
    ('[{'
          || '"add": "content 2 with bananas added",'
          || '"del": "content 1"'
          || '}]')::JSON,                           -- sections
    NULL,                                           -- not an external source
    setup.test_spider_uuid,
    '2017-04-01 12:00:01'::TIMESTAMP WITH TIME ZONE
  ) INTO result_change_id;

  -- The the pending change should be consumed ..
  RETURN NEXT is((SELECT COUNT(1) FROM pending_change), 0::BIGINT);

  -- .. and materialized into a real change.
  RETURN NEXT is((SELECT COUNT(1) FROM change), 1::BIGINT);

  -- As there are two users watching the same URL, this should result
  -- in two notifications for that change.
  RETURN NEXT is((SELECT COUNT(1) FROM notification), 2::BIGINT);

  RETURN NEXT is(
    (SELECT array_agg(id) FROM get_prev_documents(doc_id2, 99) AS id),
    ARRAY[doc_id2, doc_id1]::BIGINT[]
  );

  SELECT c.delta->'match_positions' INTO match_positions1
    FROM change c
    LEFT JOIN notification n
      ON n.change_id = c.change_id
    WHERE c.change_id = result_change_id
      AND n.alert_id = setup.carol_alert_id;

  -- match_positions should have two keys
  RETURN NEXT ok(match_positions1 ? setup.alice_alert_id::TEXT);
  RETURN NEXT ok(match_positions1 ? setup.carol_alert_id::TEXT);

  RETURN NEXT is(
    match_positions1->(setup.alice_alert_id::TEXT),
    'null'::JSONB,
    'Alice did not define keywords, so no matches'
  );
  RETURN NEXT is(
    match_positions1->(setup.carol_alert_id::TEXT),
    '{"0": [[16]]}'::JSONB,
    'Carol is interested in "banana" at position 16'
  );

  -- The notification for Carol should be linked to a keyword.
  RETURN NEXT results_eq(
    'SELECT alert_keyword
       FROM alert_keyword
       INNER JOIN notification_x_keyword nk
         ON nk.alert_keyword_id = alert_keyword.alert_keyword_id
       WHERE nk.alert_id = ' || setup.carol_alert_id || ';',
    array['banana']
  );
END
$$ LANGUAGE plpgsql;
