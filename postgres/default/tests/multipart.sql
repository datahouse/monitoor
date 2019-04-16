CREATE FUNCTION test_multipart_on_empty_string()
RETURNS SETOF TEXT
AS $$
DECLARE
  boundary TEXT := '====================548487216==';
  mime_types TEXT[];
  content_ids TEXT[];
  timestamps TIMESTAMP WITH TIME ZONE[];
  bodies TEXT[][];
BEGIN
  SELECT array_agg(mime_type), array_agg(content_id), array_agg(ts)
    INTO mime_types, content_ids, timestamps
    FROM split_multipart('', boundary);

  RETURN NEXT is(array_length(mime_types, 1), NULL);
END
$$ LANGUAGE plpgsql;


CREATE FUNCTION test_multipart_simple_case()
RETURNS SETOF TEXT
AS $$
DECLARE
  test_input CONSTANT TEXT
    := E'====================548487216==
Content-Type: text/markdown
Content-Id: 1

## First Entry

[Link](https://www.example.com/first)

Not quite a real English sentence this first entry to prevent the usual
lorem ipsum crap sports.


====================548487216==
Content-Type: text/markdown
Content-Id: 2
Date: Wed, 12 Apr 2017 14:30:31 -0200

## Second Entry

[Link](https://www.example.com/second)

To confuse another entry you just more.


====================548487216==
';
  boundary TEXT := '====================548487216==';
  mime_types TEXT[];
  content_ids TEXT[];
  timestamps TIMESTAMP WITH TIME ZONE[];
  bodies TEXT[][];
BEGIN
  SELECT array_agg(mime_type), array_agg(content_id), array_agg(ts)
    INTO mime_types, content_ids, timestamps
    FROM split_multipart(test_input, boundary);

  RETURN NEXT is(array_length(mime_types, 1), 2);
  RETURN NEXT is(mime_types[1], 'text/markdown');
  RETURN NEXT is(mime_types[2], 'text/markdown');

  RETURN NEXT is(array_length(content_ids, 1), 2);
  RETURN NEXT is(content_ids, ARRAY['1', '2']);

  RETURN NEXT is(array_length(timestamps, 1), 2);
  RETURN NEXT is(timestamps[1], NULL);
  RETURN NEXT is(timestamps[2], 'Wed, 12 Apr 2017 14:30:31 -0200');
END
$$ LANGUAGE plpgsql;


CREATE FUNCTION test_multipart_content_id_diff()
RETURNS SETOF TEXT
AS $$
DECLARE
  test_left CONSTANT TEXT
    := E'====================548487216==
Content-Type: text/markdown
Content-Id: 1

## First Entry

====================548487216==
Content-Type: text/markdown
Content-Id: 2
Date: Wed, 12 Apr 2017 14:30:31 -0200

## Second Entry

====================548487216==
';

  test_right CONSTANT TEXT
    := E'====================548487216==
Content-Type: text/markdown
Content-Id: 1

## First Entry

====================548487216==
Content-Type: text/markdown
Content-Id: 3
Date: Wed, 12 Apr 2017 14:30:31 -0200

## Third Entry

====================548487216==
';

  boundary TEXT := '===';
  left_doc_id INT;
  right_doc_id INT;

  mime_types TEXT[];
  content_ids TEXT[];

  setup RECORD;
BEGIN
  SELECT * FROM setup_common_test_base() INTO setup;

  INSERT INTO spider_document (job_id, xfrm_id, contents, contents_hash)
    VALUES (
      setup.test_job1_id, get_xfrm_id('rss2markdown-split', '{}'::JSONB),
      convert_to(test_left, 'utf-8'), digest(test_left, 'sha256')
    ) RETURNING spider_document_id INTO left_doc_id;

  INSERT INTO spider_document (job_id, xfrm_id, contents, contents_hash)
    VALUES (
      setup.test_job1_id, get_xfrm_id('rss2markdown-split', '{}'::JSONB),
      convert_to(test_right, 'utf-8'), digest(test_right, 'sha256')
    ) RETURNING spider_document_id INTO right_doc_id;

  -- left minus right
  SELECT array_agg(mime_type), array_agg(content_id)
    INTO mime_types, content_ids
    FROM diff_multipart_by_content_id(ARRAY[left_doc_id], ARRAY[right_doc_id]);

  RETURN NEXT is(array_length(mime_types, 1), 1);
  RETURN NEXT is(mime_types[1], 'text/markdown');

  RETURN NEXT is(array_length(content_ids, 1), 1);
  RETURN NEXT is(content_ids, ARRAY['2']);

  -- right minus left
  SELECT array_agg(mime_type), array_agg(content_id)
    INTO mime_types, content_ids
    FROM diff_multipart_by_content_id(ARRAY[right_doc_id], ARRAY[left_doc_id]);

  RETURN NEXT is(array_length(mime_types, 1), 1);
  RETURN NEXT is(mime_types[1], 'text/markdown');

  RETURN NEXT is(array_length(content_ids, 1), 1);
  RETURN NEXT is(content_ids, ARRAY['3']);
END
$$ LANGUAGE plpgsql;
