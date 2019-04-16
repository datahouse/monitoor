INSERT INTO check_frequency
  (check_frequency_id, check_frequency_text, check_frequency_interval)
VALUES
  (0, 'n/a', 0);

CREATE OR REPLACE FUNCTION xor(a BOOL, b BOOL)
    RETURNS BOOL
    RETURNS NULL ON NULL INPUT
    IMMUTABLE
    AS $xor$
SELECT (a AND NOT b) OR (NOT a AND b);
$xor$ LANGUAGE sql;

-- For external URLs, the check_frequency_id *MUST* be
-- NULL. Therefore, we drop the NOT NULL constraint and add another
-- one.
ALTER TABLE url
  -- Just to be extra sure. (Used to diverge between live and demo.)
  ALTER COLUMN url_group_id DROP NOT NULL,
  -- Changing for support of external data sources.
  ALTER COLUMN check_frequency_id DROP NOT NULL,
  ALTER COLUMN spider_job_id DROP NOT NULL;

-- Again, we should use the URI extenision of Postgres, here.
--
-- TODO: replace this with the url_encode extension!
ALTER TABLE url
  ADD CONSTRAINT external_check CHECK (
    XOR(
      split_part(url, ':', 1) = 'external',
      check_frequency_id IS NOT NULL
    )
  );

CREATE TABLE change (
  change_id SERIAL PRIMARY KEY,
  ts TIMESTAMP WITH TIME ZONE DEFAULT now() NOT NULL,
  -- pretty flexible, but basically an array of (annotated) changes
  delta JSONB NULL,
  old_doc_id INT NULL,
  new_doc_id INT NULL,
  -- may well be null, if our own backend inserts the change
  user_id INT NULL,
  -- may well be null: for external data
  spider_uuid UUID NULL,
  check_frequency_id INT NOT NULL,
  CONSTRAINT change_has_delta_check CHECK (
    (old_doc_id IS NULL AND new_doc_id IS NULL AND delta IS NOT NULL)
    OR (old_doc_id IS NOT NULL AND new_doc_id IS NOT NULL AND delta IS NULL)
  ),
  CONSTRAINT change_has_origin_check CHECK (
    XOR(user_id IS NOT NULL, spider_uuid IS NOT NULL)
  )
);

CREATE TABLE change_x_url (
  url_id INT NOT NULL REFERENCES url(url_id),
  change_id BIGINT NOT NULL REFERENCES change(change_id),
  PRIMARY KEY (url_id, change_id)
);

CREATE TABLE new_notification (
  alert_id INTEGER NOT NULL,
  change_id INTEGER NOT NULL,
  type_x_cycle_id INTEGER NOT NULL,
  delivery_ts TIMESTAMP WITH TIME ZONE DEFAULT NULL,
  push_ts TIMESTAMP WITH TIME ZONE DEFAULT NULL,
  is_retained BOOLEAN NOT NULL DEFAULT False,
  deprecated_url_ids INT[] NULL,
  deprecated_new_doc_id INT NULL
);

CREATE TABLE IF NOT EXISTS new_notification_x_keyword (
  alert_id INT NOT NULL,
  change_id BIGINT NOT NULL,
  type_x_cycle_id INT NOT NULL,
  alert_keyword_id INT NOT NULL
);

CREATE TABLE IF NOT EXISTS new_rating (
  change_id BIGINT,
  user_id INT NOT NULL,
  deprecated_new_doc_id BIGINT,
  rating_value_id INT NOT NULL
);

-- Merge notifications on the same spider_job and check_frequency but
-- for different URLs, using a helper function.
CREATE OR REPLACE FUNCTION get_or_create_change(
  in_ts TIMESTAMP WITH TIME ZONE,
  in_old_doc_id BIGINT,
  in_new_doc_id BIGINT,
  in_check_frequency_id INT,
  in_spider_uuid UUID
)
RETURNS INT
RETURNS NULL ON NULL INPUT
AS $$
DECLARE
  retval INT;
BEGIN
  SELECT change_id INTO retval
  FROM change
  WHERE old_doc_id = in_old_doc_id
    AND new_doc_id = in_new_doc_id
    AND check_frequency_id = in_check_frequency_id;

  IF NOT found THEN
    INSERT INTO change (ts, old_doc_id, new_doc_id, check_frequency_id, spider_uuid)
    VALUES (in_ts, in_old_doc_id, in_new_doc_id, in_check_frequency_id, in_spider_uuid)
    RETURNING change_id INTO retval;
  END IF;

  RETURN retval;
END
$$ LANGUAGE plpgsql;

INSERT INTO new_notification
  (alert_id, change_id, type_x_cycle_id, delivery_ts, push_ts, is_retained,
   deprecated_url_ids, deprecated_new_doc_id)
SELECT
  alert_id,
  get_or_create_change(
    min(creation_ts),
    old_doc_id,
    new_doc_id,
    u.check_frequency_id,
    (array_agg(spider_uuid))[1]
  ) AS change_id,
  type_x_cycle_id,
  min(delivery_ts) AS delivery_ts,
  min(push_ts) AS push_ts,
  avg(is_retained::INT) > 0.5 AS is_retained,
  array_agg(u.url_id) AS deprecated_url_ids,
  new_doc_id AS deprecated_new_doc_id
FROM notification n
LEFT JOIN url u
  ON n.url_id = u.url_id
GROUP BY
  n.alert_id, n.new_doc_id, n.type_x_cycle_id, n.old_doc_id,
  u.check_frequency_id;

DROP FUNCTION IF EXISTS get_or_create_change(
  TIMESTAMP WITH TIME ZONE, BIGINT, BIGINT, INT, UUID);

-- Link the newly generated changes to urls
INSERT INTO change_x_url
SELECT url_id, change_id
FROM (
  SELECT unnest(deprecated_url_ids) AS url_id, change_id
  FROM new_notification
) x
GROUP BY url_id, change_id;

-- Rewrite the notification_x_keyword table, also merging on url_id.
INSERT INTO new_notification_x_keyword
SELECT nn.alert_id, nn.change_id, nn.type_x_cycle_id, x.alert_keyword_id
FROM new_notification nn
INNER JOIN notification n
  ON n.alert_id = nn.alert_id
  AND n.url_id = ANY(nn.deprecated_url_ids)
  AND n.type_x_cycle_id = nn.type_x_cycle_id
  AND n.new_doc_id = nn.deprecated_new_doc_id
INNER JOIN notification_x_keyword x
  ON x.alert_id = n.alert_id
  AND x.url_id = n.url_id
  AND x.type_x_cycle_id = n.type_x_cycle_id
  AND x.new_doc_id = n.new_doc_id
GROUP BY
  nn.alert_id, nn.change_id, nn.type_x_cycle_id, x.alert_keyword_id;

-- Rewrite the rating table
INSERT INTO new_rating
  (change_id, user_id, deprecated_new_doc_id, rating_value_id)
SELECT
  nn.change_id,
  user_id,
  new_doc_id AS deprecated_new_doc_id,
  round(avg(rating_value_id)) AS rating_value_id
FROM rating
INNER JOIN new_notification nn
  ON nn.deprecated_new_doc_id = rating.new_doc_id
GROUP BY
  nn.change_id, new_doc_id, user_id, rating_value_id;



SAVEPOINT x;

ALTER TABLE notification
  RENAME CONSTRAINT notification_pkey TO old_notification_pkey;
ALTER TABLE notification
  RENAME CONSTRAINT notification_alert_id_fkey TO old_notification_alert_id_fkey;
ALTER TABLE notification
  RENAME CONSTRAINT notification_old_doc_id_fkey TO old_notification_old_doc_id_fkey;
ALTER TABLE notification
  RENAME CONSTRAINT notification_new_doc_id_fkey TO old_notification_new_doc_id_fkey;
ALTER TABLE notification
  RENAME CONSTRAINT notification_spider_uuid_fkey TO old_notification_spider_uuid_fkey;
ALTER TABLE notification
  RENAME CONSTRAINT notification_type_x_cycle_id_fkey TO old_notification_type_x_cycle_id_fkey;
ALTER TABLE notification
  RENAME CONSTRAINT notification_url_id_fkey TO old_notification_url_id_fkey;
ALTER TABLE notification
  RENAME TO old_notification;


ALTER TABLE notification_x_keyword
  RENAME CONSTRAINT notification_x_keyword_pkey TO old_notification_x_keyword_pkey;
ALTER TABLE notification_x_keyword
  RENAME CONSTRAINT alert_keyword_id_fk TO old_notification_x_keyword_alert_keyword_fkey;
ALTER TABLE notification_x_keyword
  RENAME CONSTRAINT notification_x_keyword_notification_fk TO old_notification_x_keyword_notification_fk;
ALTER TABLE notification_x_keyword
  RENAME TO old_notification_x_keyword;

ALTER TABLE new_notification_x_keyword
  RENAME TO notification_x_keyword;
ALTER TABLE notification_x_keyword
  ADD CONSTRAINT notification_x_keyword_pkey
    PRIMARY KEY (alert_id, change_id, type_x_cycle_id, alert_keyword_id),
  ADD CONSTRAINT notification_x_keyword_change_fkey
    FOREIGN KEY (change_id)
    REFERENCES change(change_id),
  ADD CONSTRAINT notification_x_keyword_alert_fkey
    FOREIGN KEY (alert_id)
    REFERENCES alert(alert_id),
  ADD CONSTRAINT notification_x_keyword_type_x_cycle_fkey
    FOREIGN KEY (type_x_cycle_id)
    REFERENCES type_x_cycle(type_x_cycle_id),
  ADD CONSTRAINT notification_x_keyword_alert_keyword_fkey
    FOREIGN KEY (alert_keyword_id)
    REFERENCES alert_keyword(alert_keyword_id);

ALTER TABLE new_notification
  RENAME TO notification;
ALTER TABLE notification
  ADD CONSTRAINT notification_pkey
    PRIMARY KEY (alert_id, change_id, type_x_cycle_id),
  ADD CONSTRAINT notification_alert_id_fkey
    FOREIGN KEY (alert_id)
    REFERENCES alert(alert_id),
  ADD CONSTRAINT notification_change_id_fkey
    FOREIGN KEY (change_id)
    REFERENCES change(change_id),
  ADD CONSTRAINT notification_type_x_cycle_id_fkey
    FOREIGN KEY (type_x_cycle_id)
    REFERENCES type_x_cycle(type_x_cycle_id);

ALTER TABLE notification_x_keyword
  ADD CONSTRAINT notification_x_keyword_notification_fkey
    FOREIGN KEY (alert_id, change_id, type_x_cycle_id)
    REFERENCES notification (alert_id, change_id, type_x_cycle_id);

ALTER TABLE rating
  RENAME CONSTRAINT rating_pkey TO old_rating_pkey;
ALTER TABLE rating
  RENAME CONSTRAINT alert_fk TO old_rating_alert_fk;
ALTER TABLE rating
  RENAME CONSTRAINT doc_fk TO old_rating_new_doc_fk;
ALTER TABLE rating
  RENAME CONSTRAINT rating_value_fk TO old_rating_value_fk;
ALTER TABLE rating
  RENAME CONSTRAINT usr_fk TO old_rating_user_fk;
ALTER TABLE rating
  RENAME TO old_rating;

ALTER TABLE new_rating
  RENAME TO rating;
ALTER TABLE rating
  ADD CONSTRAINT rating_pkey
    PRIMARY KEY (change_id, user_id),
  ADD CONSTRAINT rating_change_fkey
    FOREIGN KEY (change_id)
    REFERENCES change(change_id),
  ADD CONSTRAINT rating_user_fkey
    FOREIGN KEY (user_id)
    REFERENCES mon_user(user_id);

ALTER TABLE change
  ADD CONSTRAINT change_new_doc_id_fkey
    FOREIGN KEY (new_doc_id)
    REFERENCES spider_document(spider_document_id),
  ADD CONSTRAINT change_old_doc_id_fkey
    FOREIGN KEY (old_doc_id)
    REFERENCES spider_document(spider_document_id),
  ADD CONSTRAINT change_user_id_fkey
    FOREIGN KEY (user_id)
    REFERENCES mon_user(user_id),
  ADD CONSTRAINT change_spider_uuid_fkey
    FOREIGN KEY (spider_uuid)
    REFERENCES spider(spider_uuid),
  -- only needed during this migration, can be looked up via
  -- change_x_url in the future.
  DROP COLUMN check_frequency_id;

SAVEPOINT y;



DROP VIEW v_change;
DROP VIEW alert_latest_notification;


CREATE OR REPLACE VIEW notification_keyword AS
  SELECT n.alert_id, n.change_id, n.type_x_cycle_id,
    n.delivery_ts, n.push_ts, n.is_retained,
    kw.alert_keyword_id, kw.alert_keyword, kw.alert_keyword_active
  FROM notification n
  LEFT JOIN notification_x_keyword nk
    ON n.alert_id = nk.alert_id
    AND n.change_id = nk.change_id
    AND n.type_x_cycle_id = nk.type_x_cycle_id
  LEFT JOIN alert_keyword kw
    ON kw.alert_keyword_id = nk.alert_keyword_id;

CREATE OR REPLACE VIEW v_change AS
  SELECT n.alert_id, n.type_x_cycle_id,
         c.change_id, c.new_doc_id, c.old_doc_id, c.delta,
         c.ts AS creation_ts, cu.url_id, u.url,
         u.url_title, g.url_group_id, g.url_group_title, acc.user_id
    FROM change c
    LEFT JOIN notification n
      ON n.change_id = c.change_id
    INNER JOIN change_x_url cu ON c.change_id = cu.change_id
    INNER JOIN url u ON u.url_id = cu.url_id
    INNER JOIN url_group g ON u.url_group_id = g.url_group_id
    INNER JOIN access_control acc
      ON acc.url_id = u.url_id
        OR acc.url_group_id = g.url_group_id
    WHERE n.is_retained IS NULL OR NOT n.is_retained
    GROUP BY n.alert_id, n.type_x_cycle_id,
             c.change_id, c.new_doc_id, cu.url_id, u.url, u.url_title,
             c.old_doc_id, c.delta, g.url_group_id, g.url_group_title,
             acc.user_id;

CREATE VIEW alert_latest_notification AS
  SELECT
    n.alert_id,
    cu.url_id,
    n.type_x_cycle_id,
    c.new_doc_id AS latest_doc_id,
    c.delta AS latest_delta,
    c.ts AS latest_notification_ts
  FROM (
      SELECT
        n1.alert_id,
        n1.type_x_cycle_id,
        ( SELECT c2.change_id
          FROM notification n2
          INNER JOIN change c1 ON n1.change_id = c1.change_id
          LEFT JOIN change_x_url cu1 ON cu1.change_id = c1.change_id
          INNER JOIN change c2 ON n2.change_id = c2.change_id
          LEFT JOIN change_x_url cu2 ON cu2.change_id = c2.change_id
          WHERE n2.alert_id = n1.alert_id
            AND cu1.url_id = cu2.url_id
            AND n1.type_x_cycle_id = n2.type_x_cycle_id
            -- Disregard retained notifications
            AND NOT is_retained
            -- This ORDER BY clause exactly matches the index
            -- notification_creation_ts_idx and allows quick (reverse)
            -- index scanning to retrieve the max(creation_ts)
          ORDER BY n2.alert_id DESC, cu2.url_id DESC,
                   n2.type_x_cycle_id DESC, c2.ts DESC
          LIMIT 1
        ) AS latest_change_id
        FROM notification n1
        WHERE NOT n1.is_retained
     ) AS a
  LEFT JOIN notification n
    ON n.alert_id = a.alert_id
      AND n.change_id = a.latest_change_id
      AND n.type_x_cycle_id = a.type_x_cycle_id
  LEFT JOIN change c
    ON c.change_id = n.change_id
  LEFT JOIN change_x_url cu
    ON c.change_id = cu.change_id;



-- This is a helper table to speed up the lookup for the latest change
-- per job, xfrm_id and check_frequency.
CREATE TABLE latest_change (
  job_id INT NOT NULL REFERENCES spider_job(job_id),
  xfrm_id INT NOT NULL REFERENCES xfrm(xfrm_id),
  check_frequency_id INT NOT NULL REFERENCES check_frequency(check_frequency_id),
  change_id INT NOT NULL REFERENCES change(change_id),
  PRIMARY KEY (job_id, xfrm_id, check_frequency_id)
);

INSERT INTO latest_change
SELECT
  u.spider_job_id,
  u.xfrm_id,
  check_frequency_id,
  (
    SELECT c.change_id
    FROM change c
    LEFT JOIN change_x_url cu ON c.change_id = cu.change_id
    WHERE cu.url_id = ANY(array_agg(u.url_id))
    ORDER BY ts DESC
    LIMIT 1
  ) AS newest_change_id
FROM url u
LEFT JOIN check_frequency USING (check_frequency_id)
GROUP BY u.spider_job_id, u.xfrm_id, check_frequency_id
HAVING EXISTS (
 SELECT c.change_id
    FROM change c
    LEFT JOIN change_x_url cu ON c.change_id = cu.change_id
    WHERE cu.url_id = ANY(array_agg(u.url_id))
    ORDER BY ts DESC
    LIMIT 1
);

CREATE TABLE pending_change (
  pending_change_id SERIAL PRIMARY KEY,
  check_frequency_id INT NOT NULL REFERENCES check_frequency(check_frequency_id),
  url_ids INT[] NOT NULL,
  creation_ts TIMESTAMP WITH TIME ZONE DEFAULT now() NOT NULL,
  not_before_ts TIMESTAMP WITH TIME ZONE DEFAULT now() NOT NULL,
  old_doc_id INT NULL REFERENCES spider_document(spider_document_id),
  new_doc_id INT NULL REFERENCES spider_document(spider_document_id),
  delta JSONB NULL
);


-- Update this view.
CREATE OR REPLACE VIEW v_change AS
  SELECT n.alert_id, n.type_x_cycle_id,
         c.change_id, c.new_doc_id, c.old_doc_id, c.delta,
         c.ts AS creation_ts, cu.url_id, u.url,
         u.url_title, g.url_group_id, g.url_group_title, a.user_id
    FROM notification n
    INNER JOIN change c ON n.change_id = c.change_id
    LEFT JOIN change_x_url cu ON c.change_id = cu.change_id
    INNER JOIN alert a ON a.alert_id = n.alert_id
    INNER JOIN url u ON u.url_id = cu.url_id
    INNER JOIN url_group g
      ON u.url_group_id = g.url_group_id
    INNER JOIN alert_x_url_group aug
      ON a.alert_id = aug.alert_id
      AND g.url_group_id = aug.url_group_id
    LEFT JOIN access_control acc
      ON a.user_id = acc.user_id
        AND acc.url_group_id = g.url_group_id
    WHERE a.alert_active = TRUE
      AND n.is_retained = FALSE
    GROUP BY n.alert_id, n.type_x_cycle_id, a.user_id,
             c.change_id, c.new_doc_id, cu.url_id, u.url, u.url_title,
             c.old_doc_id, c.delta, g.url_group_id, g.url_group_title;
