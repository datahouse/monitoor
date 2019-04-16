ALTER TABLE mon_user
  ADD CONSTRAINT mon_user_user_email_key
    UNIQUE (user_email);

ALTER TABLE user_pw_recovery
  ADD CONSTRAINT user_pw_recovery_user_pw_recovery_hash_key
    UNIQUE (user_pw_recovery_hash);

ALTER TABLE account
  ADD CONSTRAINT account_user_id_key
    UNIQUE (user_id),
  ADD CONSTRAINT userid_fk FOREIGN KEY (user_id)
    REFERENCES mon_user (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE user_pw_recovery
  ADD CONSTRAINT usrid_fk FOREIGN KEY (user_id)
    REFERENCES mon_user (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE user_x_group
  ADD CONSTRAINT user_x_group_user_id_user_group_id_key
    UNIQUE (user_id, user_group_id),
  ADD CONSTRAINT user_fk FOREIGN KEY (user_id)
    REFERENCES mon_user (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT user_group_fk FOREIGN KEY (user_group_id)
    REFERENCES user_group (user_group_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE user_x_role
  ADD CONSTRAINT user_x_role_user_id_role_id_key
    UNIQUE (user_id, role_id),
  ADD CONSTRAINT userid_fk FOREIGN KEY (user_id)
    REFERENCES mon_user (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT role_fk FOREIGN KEY (role_id)
    REFERENCES role (role_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE spider_job
  ADD CONSTRAINT spider_job_url_url_lang_key
    UNIQUE (url, url_lang),
  ADD CONSTRAINT spider_uuid_fk FOREIGN KEY (job_spider_uuid)
    REFERENCES spider(spider_uuid);

ALTER TABLE xfrm
  ADD CONSTRAINT xfrm_commands_args_key
    UNIQUE (xfrm_commands, xfrm_args);

ALTER TABLE url
  ADD CONSTRAINT user_url_fk FOREIGN KEY (url_creator_user_id)
    REFERENCES mon_user (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT feq_fk FOREIGN KEY (check_frequency_id)
    REFERENCES check_frequency (check_frequency_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT xfrm_id_fk FOREIGN KEY (xfrm_id)
    REFERENCES xfrm (xfrm_id);

ALTER TABLE url_group
  ADD CONSTRAINT group_group_fk FOREIGN KEY (parent_url_group_id)
    REFERENCES url_group (url_group_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT usr_urlgrp_fk FOREIGN KEY (url_group_creator_user_id)
    REFERENCES mon_user (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE url_x_group
  ADD CONSTRAINT url_x_group_url_id_url_group_id_key
    UNIQUE (url_id, url_group_id),
  ADD CONSTRAINT urlid_fk FOREIGN KEY (url_id)
    REFERENCES url (url_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT url_group_fk FOREIGN KEY (url_group_id)
    REFERENCES url_group (url_group_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE spider_change
  ADD CONSTRAINT job_id_fk FOREIGN KEY (job_id)
    REFERENCES spider_job(job_id);

ALTER TABLE spider_document
  ADD CONSTRAINT job_id_fk FOREIGN KEY (job_id)
    REFERENCES spider_job(job_id),
  ADD CONSTRAINT xfrm_id_fk FOREIGN KEY (xfrm_id)
    REFERENCES xfrm (xfrm_id),
  ADD CONSTRAINT spider_document_hash_matches
    CHECK(digest(contents, 'sha256') = contents_hash);

ALTER TABLE url
  ADD CONSTRAINT spider_job_id_fk FOREIGN KEY (spider_job_id)
    REFERENCES spider_job(job_id);

ALTER TABLE alert
  ADD CONSTRAINT alert_user_fk FOREIGN KEY (user_id)
    REFERENCES mon_user (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE alert_x_url_group
  ADD CONSTRAINT alert_x_url_group_alert_id_url_group_id_key
    UNIQUE (alert_id, url_group_id),
  ADD CONSTRAINT alertid_fk FOREIGN KEY (alert_id)
    REFERENCES alert (alert_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT url_group_fk FOREIGN KEY (url_group_id)
    REFERENCES url_group (url_group_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE type_x_cycle
  ADD CONSTRAINT type_x_cycle_alert_type_id_alert_cycle_id_key
    UNIQUE (alert_type_id, alert_cycle_id),
  ADD CONSTRAINT alert_type_id_fk FOREIGN KEY (alert_type_id)
    REFERENCES alert_type (alert_type_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT alert_cycle_id_fk FOREIGN KEY (alert_cycle_id)
    REFERENCES alert_cycle (alert_cycle_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE alert_x_type_cycle
  ADD CONSTRAINT alert_id_fk FOREIGN KEY (alert_id)
    REFERENCES alert (alert_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT alert_type_fk FOREIGN KEY (type_x_cycle_id)
    REFERENCES type_x_cycle (type_x_cycle_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE notification
  ADD CONSTRAINT type_x_cycle_id_fk FOREIGN KEY (type_x_cycle_id)
    REFERENCES type_x_cycle(type_x_cycle_id),
  ADD CONSTRAINT old_doc_id_fk FOREIGN KEY (old_doc_id)
    REFERENCES spider_document(spider_document_id),
  ADD CONSTRAINT new_doc_id_fk FOREIGN KEY (new_doc_id)
    REFERENCES spider_document(spider_document_id),
  ADD CONSTRAINT spider_uuid_fk FOREIGN KEY (spider_uuid)
    REFERENCES spider(spider_uuid);

ALTER TABLE notification_x_keyword
  ADD CONSTRAINT alert_keyword_id_fk FOREIGN KEY (alert_keyword_id)
    REFERENCES alert_keyword(alert_keyword_id);

ALTER TABLE alert_keyword
  ADD CONSTRAINT alert_keyword_alert_id_alert_keyword_key
    UNIQUE (alert_id, alert_keyword),
  ADD CONSTRAINT alert_keyword_lowercase
    CHECK (alert_keyword = lower(alert_keyword)),
  ADD CONSTRAINT a_id_fk FOREIGN KEY (alert_id)
    REFERENCES alert (alert_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE access_control
  ADD CONSTRAINT acc_typ_fk FOREIGN KEY (access_type_id)
    REFERENCES access_type (access_type_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT usr_fk FOREIGN KEY (user_id)
    REFERENCES mon_user (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT ursr_grp_fk FOREIGN KEY (user_group_id)
    REFERENCES user_group (user_group_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT url_fk FOREIGN KEY (url_id)
    REFERENCES url (url_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT url_grp_fk FOREIGN KEY (url_group_id)
    REFERENCES url_group (url_group_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

CREATE INDEX url_spider_job_id
  ON url(spider_job_id);
CREATE INDEX alert_user_id_idx
  ON alert(user_id);
CREATE INDEX alert_x_url_group_url_group_id_idx
  ON alert_x_url_group(url_group_id);
CREATE INDEX spider_document_last_doc_idx
  ON spider_document(job_id, xfrm_id, spider_document_id);
CREATE INDEX url_group_parent_url_group_id_idx
  ON url_group(parent_url_group_id);

ALTER TABLE rating
  ADD CONSTRAINT usr_fk FOREIGN KEY (user_id)
    REFERENCES mon_user (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT alert_fk FOREIGN KEY (alert_id)
    REFERENCES alert (alert_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT doc_fk FOREIGN KEY (new_doc_id)
    REFERENCES spider_document (spider_document_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT rating_value_fk FOREIGN KEY (rating_value_id)
    REFERENCES rating_value (rating_value_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

