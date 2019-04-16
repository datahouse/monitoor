-- Ensure this column of the new rating table may be NULL.
ALTER TABLE rating
  ALTER COLUMN deprecated_new_doc_id DROP NOT NULL;

-- Rather than that separate helper table, we need to keep track per
-- URL, as we don't have a spider job for external data URLs. Given
-- that, we can use the url table directly.
DROP TABLE latest_change;

ALTER TABLE url
  ADD COLUMN url_latest_change_id INT REFERENCES change(change_id);


UPDATE url
  SET url_latest_change_id = x.newest_change_id
  FROM (
    SELECT
      u.url_id,
      (
        SELECT c.change_id
        FROM change c
        LEFT JOIN change_x_url cu ON c.change_id = cu.change_id
        WHERE cu.url_id = u.url_id
        ORDER BY ts DESC
        LIMIT 1
      ) AS newest_change_id
    FROM url u
  ) x
WHERE url.url_id = x.url_id AND x.newest_change_id IS NOT NULL;
