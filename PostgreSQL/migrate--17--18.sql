CREATE TABLE IF NOT EXISTS push_token (
  push_token_id SERIAL NOT NULL,
  user_id INTEGER NOT NULL,
  token varchar(2096) NOT NULL,
  platform INTEGER NOT NULL CHECK(platform BETWEEN 0 AND 1),
  denied BOOLEAN NOT NULL,
  ts TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  PRIMARY KEY (push_token_id),
  UNIQUE (user_id, token)
) ;

ALTER TABLE push_token ADD CONSTRAINT user_fk FOREIGN KEY (user_id)
    REFERENCES mon_user (user_id);

ALTER TABLE notification ADD COLUMN push_ts TIMESTAMP WITH TIME ZONE DEFAULT NULL;

UPDATE account SET pricing_plan_id=3 WHERE pricing_plan_id=1;

UPDATE pricing_plan SET pricing_plan_text = 'Free' WHERE pricing_plan_id=1;
UPDATE pricing_plan SET pricing_plan_text = 'Basic' WHERE pricing_plan_id=2;
UPDATE pricing_plan SET pricing_plan_text = 'Professional' WHERE pricing_plan_id=3;

