-- Depends on the xor function, so this constraint had to be applied only
-- after that function, rather than in init.sql, directly.
ALTER TABLE change
  ADD CONSTRAINT change_has_origin_check
    CHECK (xor((user_id IS NOT NULL), (spider_uuid IS NOT NULL)));
