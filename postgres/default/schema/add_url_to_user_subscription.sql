ALTER TABLE user_subscription ADD COLUMN url_id integer ;

UPDATE user_subscription a SET url_id = (SELECT MAX(url_id) FROM url b WHERE a.url_group_id = b.url_group_id);

ALTER TABLE user_subscription ALTER COLUMN url_id SET NOT NULL;

ALTER TABLE ONLY user_subscription
    ADD CONSTRAINT user_subscription_url_id_fkey FOREIGN KEY (url_id)
    REFERENCES url(url_id);
