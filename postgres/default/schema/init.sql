--
-- First, some superuser stuff.
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;

-- schema thirdparty - to separate datahouse code from other's code
CREATE SCHEMA thirdparty;
GRANT USAGE ON SCHEMA thirdparty TO PUBLIC;

-- just for gen_random_uuid(), so far
CREATE EXTENSION pgcrypto WITH SCHEMA thirdparty;

-- useful for uniq() and sort() methods on int[]
CREATE EXTENSION intarray WITH SCHEMA thirdparty;


--
-- Then, switch to user project_mon, who sadly is the owner of everything.
--
SET ROLE project_mon;
SET search_path TO public, thirdparty;



CREATE TYPE action AS ENUM (
    'subscribe',
    'unsubscribe'
);

CREATE TYPE change_type_enum AS ENUM (
    'insert',
    'update',
    'delete'
);



CREATE TABLE access_control (
    access_control_id SERIAL PRIMARY KEY,
    user_id integer,
    user_group_id integer,
    url_id integer,
    url_group_id integer,
    access_type_id integer NOT NULL,
    access_control_valid_from timestamp without time zone DEFAULT now() NOT NULL,
    access_control_valid_till timestamp without time zone
);

CREATE TABLE access_type (
    access_type_id integer PRIMARY KEY,
    access_type_name character varying(10) NOT NULL,
    access_type_description character varying(50) NOT NULL
);

CREATE TABLE account (
    account_id SERIAL PRIMARY KEY,
    user_id integer NOT NULL,
    account_name_first character varying(255),
    account_name_last character varying(255),
    account_mobile character varying(20),
    account_company character varying(255),
    pricing_plan_id integer NOT NULL,
    voucher_id integer,
    UNIQUE (user_id)
);

CREATE TABLE alert (
    alert_id SERIAL PRIMARY KEY,
    alert_active boolean DEFAULT true NOT NULL,
    user_id integer NOT NULL,
    alert_option_id integer DEFAULT 1 NOT NULL,
    alert_threshold double precision
);

CREATE TABLE alert_cycle (
    alert_cycle_id integer PRIMARY KEY,
    alert_cycle_name character varying(50) NOT NULL,
    alert_cycle_description character varying(200) NOT NULL
);

CREATE TABLE alert_keyword (
    alert_keyword_id SERIAL PRIMARY KEY,
    alert_id integer NOT NULL,
    alert_keyword text NOT NULL,
    alert_keyword_active boolean DEFAULT true NOT NULL,
    UNIQUE (alert_id, alert_keyword),
    CONSTRAINT alert_keyword_lowercase CHECK ((alert_keyword = lower(alert_keyword)))
);

CREATE TABLE change (
    change_id SERIAL PRIMARY KEY,
    ts timestamp with time zone DEFAULT now() NOT NULL,
    delta jsonb,
    old_doc_id integer,
    new_doc_id integer,
    user_id integer,
    spider_uuid uuid,
    CONSTRAINT change_has_delta_check
      CHECK (((delta IS NOT NULL) OR ((old_doc_id IS NOT NULL) AND (new_doc_id IS NOT NULL))))
);

CREATE TABLE change_x_url (
    url_id integer NOT NULL,
    change_id bigint NOT NULL,
    url_group_id integer,
    ts timestamp with time zone DEFAULT now() NOT NULL,
    PRIMARY KEY (url_id, change_id)
);


CREATE TABLE notification (
    alert_id integer NOT NULL,
    change_id integer NOT NULL,
    type_x_cycle_id integer NOT NULL,
    delivery_ts timestamp with time zone,
    push_ts timestamp with time zone,
    is_retained boolean DEFAULT false NOT NULL,
    deprecated_url_ids integer[],
    deprecated_new_doc_id integer,
    creation_ts timestamp with time zone DEFAULT now() NOT NULL,
    PRIMARY KEY (alert_id, change_id, type_x_cycle_id)
);

CREATE TABLE alert_option (
    alert_option_id integer PRIMARY KEY,
    alert_option_name character varying(50) NOT NULL,
    alert_option_description character varying(200) NOT NULL
);

CREATE TABLE alert_type (
    alert_type_id integer PRIMARY KEY,
    alert_type_name character varying(50) NOT NULL,
    alert_type_description character varying(200) NOT NULL
);

CREATE TABLE alert_x_type_cycle (
    alert_id integer NOT NULL,
    type_x_cycle_id integer NOT NULL,
    PRIMARY KEY (alert_id, type_x_cycle_id)
);

CREATE TABLE alert_x_url_group (
    alert_id integer NOT NULL,
    url_group_id integer NOT NULL,
    PRIMARY KEY (url_group_id, alert_id)
);

CREATE TABLE check_frequency (
    check_frequency_id integer PRIMARY KEY,
    check_frequency_text character varying(10) NOT NULL,
    check_frequency_interval bigint NOT NULL
);

CREATE TABLE live_task_queue (
    task_uuid uuid DEFAULT thirdparty.gen_random_uuid() NOT NULL,
    task_kind text NOT NULL,
    task_data jsonb,
    spider_uuid uuid
);

CREATE TABLE live_task_result (
    task_uuid uuid NOT NULL,
    task_result jsonb,
    spider_uuid uuid NOT NULL
);

CREATE TABLE mon_user (
    user_id SERIAL PRIMARY KEY,
    user_email character varying(100) NOT NULL,
    user_password character(64),
    user_password_salt character(16),
    user_valid_from timestamp without time zone DEFAULT now() NOT NULL,
    user_valid_till timestamp without time zone,
    user_last_login timestamp without time zone,
    user_activated boolean DEFAULT false NOT NULL,
    user_group_id integer,
    is_group_admin boolean DEFAULT false NOT NULL,
    UNIQUE (user_email)
);

CREATE TABLE notification_x_keyword (
    alert_id integer NOT NULL,
    change_id bigint NOT NULL,
    type_x_cycle_id integer NOT NULL,
    alert_keyword_id integer NOT NULL,
    PRIMARY KEY (alert_id, change_id, type_x_cycle_id, alert_keyword_id)
);

CREATE TABLE url_group (
    url_group_id SERIAL PRIMARY KEY,
    parent_url_group_id integer,
    url_group_title character varying(100) NOT NULL,
    url_group_description character varying(200),
    url_group_creator_user_id integer NOT NULL,
    is_subscription boolean DEFAULT false NOT NULL,
    is_demo boolean DEFAULT false NOT NULL,
    is_widget boolean DEFAULT false NOT NULL,
    subscription_price integer,
    billable_only boolean DEFAULT false NOT NULL
);

-- internal counter table, possibly featuring multiple entries for
-- parallel updates.
CREATE TABLE IF NOT EXISTS notification_counter_internal (
  url_group_id INT NOT NULL,
  -- some random uid to make conflicts reasonably improbable, but still
  -- allowing us to identify individual entries.
  entry_uid INT NOT NULL DEFAULT floor(random() * 2147483647),
  period_start TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  count BIGINT NOT NULL DEFAULT 1,
  PRIMARY KEY (url_group_id, entry_uid)
);

-- FIXME: presumably a deprecated table... remove?
CREATE TABLE old_rating (
    alert_id integer NOT NULL,
    new_doc_id bigint NOT NULL,
    user_id integer NOT NULL,
    rating_value_id integer NOT NULL,
    PRIMARY KEY (alert_id, new_doc_id, user_id)
);

CREATE TABLE rating (
    change_id BIGINT,
    user_id INT NOT NULL,
    deprecated_new_doc_id BIGINT,
    rating_value_id INT NOT NULL,
    PRIMARY KEY (change_id, user_id)
);

CREATE TABLE rating_value (
    rating_value_id integer PRIMARY KEY,
    rating_value_desc character varying(50) NOT NULL
);

CREATE TABLE pending_change (
    pending_change_id SERIAL PRIMARY KEY,
    check_frequency_id integer NOT NULL,
    url_ids integer[] NOT NULL,
    creation_ts timestamp with time zone DEFAULT now() NOT NULL,
    not_before_ts timestamp with time zone DEFAULT now() NOT NULL,
    old_doc_id integer,
    new_doc_id integer,
    delta jsonb
);

CREATE TABLE pricing_plan (
    pricing_plan_id integer PRIMARY KEY,
    pricing_plan_text character varying(20) NOT NULL,
    pricing_plan_sort_order integer NOT NULL
);


CREATE TABLE push_token (
    push_token_id SERIAL PRIMARY KEY,
    user_id integer NOT NULL,
    token character varying(2096) NOT NULL,
    platform integer NOT NULL,
    denied boolean NOT NULL,
    ts timestamp with time zone DEFAULT now(),
    UNIQUE (user_id, token),
    CONSTRAINT push_token_platform_check CHECK (((platform >= 0) AND (platform <= 1)))
);

CREATE TABLE role (
    role_id SERIAL PRIMARY KEY,
    role_short character varying(10) NOT NULL,
    role_description character varying(100) NOT NULL
);

CREATE TABLE spider (
    spider_uuid UUID PRIMARY KEY,
    spider_last_seen timestamp with time zone DEFAULT now() NOT NULL,
    spider_last_hostname text NOT NULL
);

CREATE TABLE spider_change (
    change_id BIGSERIAL PRIMARY KEY,
    job_id integer NOT NULL,
    change_type change_type_enum NOT NULL
);

CREATE TABLE spider_document (
    spider_document_id BIGSERIAL PRIMARY KEY,
    job_id integer NOT NULL,
    reception_ts timestamp with time zone DEFAULT now() NOT NULL,
    contents bytea NOT NULL,
    xfrm_id integer NOT NULL,
    contents_hash bytea NOT NULL,
    CONSTRAINT spider_document_hash_matches CHECK ((thirdparty.digest(contents, 'sha256'::text) = contents_hash))
);

CREATE TABLE spider_errlog (
    spider_uuid uuid NOT NULL,
    ts timestamp with time zone DEFAULT now() NOT NULL,
    msg text NOT NULL,
    PRIMARY KEY (spider_uuid, ts)
);


CREATE TABLE spider_job (
    job_id SERIAL PRIMARY KEY,
    url text NOT NULL,
    url_lang text NOT NULL,
    min_check_interval bigint NOT NULL,
    job_spider_uuid uuid,
    job_active boolean DEFAULT true NOT NULL,
    last_check_ts timestamp with time zone,
    last_modification text,
    last_hash bytea,
    last_entity_tag text,
    UNIQUE (url, url_lang)
);

CREATE TABLE url (
    url_id SERIAL PRIMARY KEY,
    url_title character varying(100) NOT NULL,
    url text NOT NULL,
    url_lang text DEFAULT 'de'::text NOT NULL,
    check_frequency_id integer NOT NULL,
    url_creator_user_id integer NOT NULL,
    url_active boolean DEFAULT true NOT NULL,
    spider_job_id integer,
    xfrm_id integer NOT NULL,
    url_group_id integer,
    url_latest_change_id integer
);

CREATE TABLE xfrm (
    xfrm_id SERIAL PRIMARY KEY,
    xfrm_commands text NOT NULL,
    xfrm_args jsonb
);

ALTER TABLE ONLY xfrm
    ADD CONSTRAINT xfrm_commands_args_key UNIQUE (xfrm_commands, xfrm_args);

CREATE TABLE spider_load (
    spider_uuid uuid NOT NULL,
    ts timestamp with time zone DEFAULT now() NOT NULL,
    load_one double precision NOT NULL,
    load_five double precision NOT NULL,
    load_fifteen double precision NOT NULL,
    cpu_user_time double precision NOT NULL,
    cpu_nice_time double precision NOT NULL,
    cpu_system_time double precision NOT NULL,
    cpu_idle_time double precision NOT NULL,
    cpu_iowait_time double precision NOT NULL,
    cpu_irq_time double precision NOT NULL,
    bytes_sent bigint NOT NULL,
    bytes_recv bigint NOT NULL,
    packets_sent bigint NOT NULL,
    packets_recv bigint NOT NULL,
    PRIMARY KEY (spider_uuid, ts)
);

CREATE TABLE type_x_cycle (
    type_x_cycle_id SERIAL PRIMARY KEY,
    alert_type_id integer NOT NULL,
    alert_cycle_id integer NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    UNIQUE (alert_type_id, alert_cycle_id)
);

CREATE TABLE url_blacklist (
    url_blacklist_id SERIAL PRIMARY KEY,
    url_blacklist character varying(100) NOT NULL
);

CREATE TABLE user_activation (
    user_activation_id SERIAL PRIMARY KEY,
    user_id integer NOT NULL,
    user_activation_created timestamp without time zone NOT NULL,
    user_activation_used timestamp without time zone,
    user_activation_hash character(40) NOT NULL
);

ALTER TABLE user_activation
        ADD CONSTRAINT user_activation_hash_key UNIQUE (user_activation_hash);

ALTER TABLE user_activation
        ADD CONSTRAINT user_activation_id_key UNIQUE (user_activation_id);

CREATE TABLE user_group (
    user_group_id SERIAL PRIMARY KEY,
    user_group_name character varying(100) NOT NULL
);

CREATE TABLE user_pw_recovery (
    user_pw_recovery_id SERIAL PRIMARY KEY,
    user_id integer NOT NULL,
    user_pw_recovery_created timestamp without time zone NOT NULL,
    user_pw_recovery_used timestamp without time zone,
    user_pw_recovery_hash character(40) NOT NULL,
    UNIQUE (user_pw_recovery_hash)
);

CREATE TABLE user_subscription (
    user_subscription_id SERIAL PRIMARY KEY,
    user_id integer NOT NULL,
    url_group_id integer NOT NULL,
    subscription_ts timestamp without time zone DEFAULT now() NOT NULL,
    user_action action NOT NULL
);

CREATE TABLE user_x_role (
    user_x_role_id SERIAL PRIMARY KEY,
    user_id integer NOT NULL,
    role_id integer NOT NULL,
    UNIQUE (user_id, role_id)
);

CREATE TABLE voucher (
    voucher_id SERIAL NOT NULL,
    voucher_code character(8) NOT NULL,
    voucher_used timestamp without time zone
);

ALTER TABLE ONLY voucher
    ADD CONSTRAINT voucher_pkey PRIMARY KEY (voucher_id),
    ADD CONSTRAINT voucher_code UNIQUE (voucher_code);

-- indices
CREATE INDEX access_control_url_group_id_idx ON access_control USING btree (url_group_id);
CREATE INDEX access_control_url_id_idx ON access_control USING btree (url_id);
CREATE INDEX alert_active_user_id_idx ON alert USING btree (user_id) WHERE alert_active;
CREATE INDEX alert_user_id_idx ON alert USING btree (user_id);
CREATE INDEX alert_x_url_group_alert_id_idx ON alert_x_url_group USING btree (alert_id);
CREATE INDEX change_by_url_group_and_ts_idx ON change_x_url USING btree (url_group_id, ts);
CREATE INDEX change_x_url_change_id_idx ON change_x_url USING btree (change_id);
CREATE INDEX change_x_url_ts_idx ON change_x_url USING btree (ts);
CREATE INDEX notification_creation_ts_idx ON notification USING btree (creation_ts);
CREATE INDEX spider_document_last_doc_idx ON spider_document USING btree (job_id, xfrm_id, spider_document_id);
CREATE INDEX url_group_creator_user_id_idx ON url_group USING btree (url_group_creator_user_id);
CREATE INDEX url_spider_job_id ON url USING btree (spider_job_id);
CREATE INDEX url_url_group_id_idx ON url USING btree (url_group_id);


-- foreign key constraints
ALTER TABLE ONLY alert_keyword
    ADD CONSTRAINT a_id_fk FOREIGN KEY (alert_id)
    REFERENCES alert(alert_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY access_control
    ADD CONSTRAINT acc_typ_fk FOREIGN KEY (access_type_id)
    REFERENCES access_type(access_type_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY account
    ADD CONSTRAINT accprice_fk FOREIGN KEY (pricing_plan_id)
    REFERENCES pricing_plan(pricing_plan_id);

ALTER TABLE ONLY type_x_cycle
    ADD CONSTRAINT alert_cycle_id_fk FOREIGN KEY (alert_cycle_id)
    REFERENCES alert_cycle(alert_cycle_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY alert_x_type_cycle
    ADD CONSTRAINT alert_id_fk FOREIGN KEY (alert_id)
    REFERENCES alert(alert_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY alert_x_type_cycle
    ADD CONSTRAINT alert_type_fk FOREIGN KEY (type_x_cycle_id)
    REFERENCES type_x_cycle(type_x_cycle_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY type_x_cycle
    ADD CONSTRAINT alert_type_id_fk FOREIGN KEY (alert_type_id)
    REFERENCES alert_type(alert_type_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY alert
    ADD CONSTRAINT alert_user_fk FOREIGN KEY (user_id)
    REFERENCES mon_user(user_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY alert_x_url_group
    ADD CONSTRAINT alertid_fk FOREIGN KEY (alert_id)
    REFERENCES alert(alert_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY alert
    ADD CONSTRAINT alopt_fk FOREIGN KEY (alert_option_id)
    REFERENCES alert_option(alert_option_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY change
    ADD CONSTRAINT change_new_doc_id_fkey FOREIGN KEY (new_doc_id)
    REFERENCES spider_document(spider_document_id);

ALTER TABLE ONLY change
    ADD CONSTRAINT change_old_doc_id_fkey FOREIGN KEY (old_doc_id)
    REFERENCES spider_document(spider_document_id);

ALTER TABLE ONLY change
    ADD CONSTRAINT change_spider_uuid_fkey FOREIGN KEY (spider_uuid)
    REFERENCES spider(spider_uuid);

ALTER TABLE ONLY change
    ADD CONSTRAINT change_user_id_fkey FOREIGN KEY (user_id)
    REFERENCES mon_user(user_id);

ALTER TABLE ONLY change_x_url
    ADD CONSTRAINT change_x_url_change_id_fkey FOREIGN KEY (change_id)
    REFERENCES change(change_id);

ALTER TABLE ONLY change_x_url
    ADD CONSTRAINT change_x_url_url_id_fkey FOREIGN KEY (url_id)
    REFERENCES url(url_id);

ALTER TABLE ONLY url
    ADD CONSTRAINT feq_fk FOREIGN KEY (check_frequency_id)
    REFERENCES check_frequency(check_frequency_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY url_group
    ADD CONSTRAINT group_group_fk FOREIGN KEY (parent_url_group_id)
    REFERENCES url_group(url_group_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY live_task_queue
    ADD CONSTRAINT live_task_queue_spider_uuid_fkey FOREIGN KEY (spider_uuid)
    REFERENCES spider(spider_uuid);

ALTER TABLE ONLY live_task_result
    ADD CONSTRAINT live_task_result_spider_uuid_fkey FOREIGN KEY (spider_uuid)
    REFERENCES spider(spider_uuid);

ALTER TABLE ONLY mon_user
    ADD CONSTRAINT mon_user_user_group_id_fkey FOREIGN KEY (user_group_id)
    REFERENCES user_group(user_group_id);

ALTER TABLE ONLY notification
    ADD CONSTRAINT notification_alert_id_fkey FOREIGN KEY (alert_id)
    REFERENCES alert(alert_id);

ALTER TABLE ONLY notification
    ADD CONSTRAINT notification_change_id_fkey FOREIGN KEY (change_id)
    REFERENCES change(change_id);

ALTER TABLE ONLY notification_counter_internal
    ADD CONSTRAINT notification_counter_internal_url_group_id_fkey FOREIGN KEY (url_group_id)
    REFERENCES url_group(url_group_id);

ALTER TABLE ONLY notification
    ADD CONSTRAINT notification_type_x_cycle_id_fkey FOREIGN KEY (type_x_cycle_id)
    REFERENCES type_x_cycle(type_x_cycle_id);

ALTER TABLE ONLY notification_x_keyword
    ADD CONSTRAINT notification_x_keyword_alert_fkey FOREIGN KEY (alert_id)
    REFERENCES alert(alert_id);

ALTER TABLE ONLY notification_x_keyword
    ADD CONSTRAINT notification_x_keyword_alert_keyword_fkey FOREIGN KEY (alert_keyword_id)
    REFERENCES alert_keyword(alert_keyword_id);

ALTER TABLE ONLY notification_x_keyword
    ADD CONSTRAINT notification_x_keyword_change_fkey FOREIGN KEY (change_id)
    REFERENCES change(change_id);

ALTER TABLE ONLY notification_x_keyword
    ADD CONSTRAINT notification_x_keyword_notification_fkey FOREIGN KEY (alert_id, change_id, type_x_cycle_id)
    REFERENCES notification(alert_id, change_id, type_x_cycle_id);

ALTER TABLE ONLY notification_x_keyword
    ADD CONSTRAINT notification_x_keyword_type_x_cycle_fkey FOREIGN KEY (type_x_cycle_id)
    REFERENCES type_x_cycle(type_x_cycle_id);

ALTER TABLE ONLY old_rating
    ADD CONSTRAINT old_rating_alert_fk FOREIGN KEY (alert_id)
    REFERENCES alert(alert_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY old_rating
    ADD CONSTRAINT old_rating_new_doc_fk FOREIGN KEY (new_doc_id)
    REFERENCES spider_document(spider_document_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY old_rating
    ADD CONSTRAINT old_rating_user_fk FOREIGN KEY (user_id)
    REFERENCES mon_user(user_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY old_rating
    ADD CONSTRAINT old_rating_value_fk FOREIGN KEY (rating_value_id)
    REFERENCES rating_value(rating_value_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY pending_change
    ADD CONSTRAINT pending_change_check_frequency_id_fkey FOREIGN KEY (check_frequency_id)
    REFERENCES check_frequency(check_frequency_id);

ALTER TABLE ONLY pending_change
    ADD CONSTRAINT pending_change_new_doc_id_fkey FOREIGN KEY (new_doc_id)
    REFERENCES spider_document(spider_document_id);

ALTER TABLE ONLY pending_change
    ADD CONSTRAINT pending_change_old_doc_id_fkey FOREIGN KEY (old_doc_id)
    REFERENCES spider_document(spider_document_id);

ALTER TABLE ONLY rating
    ADD CONSTRAINT rating_change_fkey FOREIGN KEY (change_id)
    REFERENCES change(change_id);

ALTER TABLE ONLY rating
    ADD CONSTRAINT rating_user_fkey FOREIGN KEY (user_id)
    REFERENCES mon_user(user_id);

ALTER TABLE ONLY user_x_role
    ADD CONSTRAINT role_fk FOREIGN KEY (role_id)
    REFERENCES role(role_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY spider_change
    ADD CONSTRAINT spider_change_job_id_fkey FOREIGN KEY (job_id)
    REFERENCES spider_job(job_id);

ALTER TABLE ONLY spider_document
    ADD CONSTRAINT spider_document_job_id_fkey FOREIGN KEY (job_id)
    REFERENCES spider_job(job_id);

ALTER TABLE ONLY spider_job
    ADD CONSTRAINT spider_job_job_spider_uuid_fkey FOREIGN KEY (job_spider_uuid)
    REFERENCES spider(spider_uuid);

ALTER TABLE ONLY spider_job
    ADD CONSTRAINT spider_uuid_fk FOREIGN KEY (job_spider_uuid)
    REFERENCES spider(spider_uuid);

ALTER TABLE ONLY access_control
    ADD CONSTRAINT url_fk FOREIGN KEY (url_id)
    REFERENCES url(url_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY alert_x_url_group
    ADD CONSTRAINT url_group_fk FOREIGN KEY (url_group_id)
    REFERENCES url_group(url_group_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY user_subscription
    ADD CONSTRAINT url_group_fk FOREIGN KEY (url_group_id)
    REFERENCES url_group(url_group_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY access_control
    ADD CONSTRAINT url_grp_fk FOREIGN KEY (url_group_id)
    REFERENCES url_group(url_group_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY url
    ADD CONSTRAINT url_spider_job_id_fkey FOREIGN KEY (spider_job_id)
    REFERENCES spider_job(job_id);

ALTER TABLE ONLY url
    ADD CONSTRAINT url_url_group_id_fkey FOREIGN KEY (url_group_id)
    REFERENCES url_group(url_group_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY url
    ADD CONSTRAINT url_url_latest_change_id_fkey FOREIGN KEY (url_latest_change_id)
    REFERENCES change(change_id);

ALTER TABLE ONLY access_control
    ADD CONSTRAINT ursr_grp_fk FOREIGN KEY (user_group_id)
    REFERENCES user_group(user_group_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY push_token
    ADD CONSTRAINT user_fk FOREIGN KEY (user_id)
    REFERENCES mon_user(user_id);

ALTER TABLE ONLY user_subscription
    ADD CONSTRAINT user_fk FOREIGN KEY (user_id)
    REFERENCES mon_user(user_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY url
    ADD CONSTRAINT user_url_fk FOREIGN KEY (url_creator_user_id)
    REFERENCES mon_user(user_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY account
    ADD CONSTRAINT userid_fk FOREIGN KEY (user_id)
    REFERENCES mon_user(user_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY user_x_role
    ADD CONSTRAINT userid_fk FOREIGN KEY (user_id)
    REFERENCES mon_user(user_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY access_control
    ADD CONSTRAINT usr_fk FOREIGN KEY (user_id)
    REFERENCES mon_user(user_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY url_group
    ADD CONSTRAINT usr_urlgrp_fk FOREIGN KEY (url_group_creator_user_id)
    REFERENCES mon_user(user_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY user_pw_recovery
    ADD CONSTRAINT usrid_fk FOREIGN KEY (user_id)
    REFERENCES mon_user(user_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY user_activation
    ADD CONSTRAINT usrid_fk FOREIGN KEY (user_id)
    REFERENCES mon_user(user_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY account
    ADD CONSTRAINT voucher_fk FOREIGN KEY (voucher_id)
    REFERENCES voucher(voucher_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY url
    ADD CONSTRAINT xfrm_id_fk FOREIGN KEY (xfrm_id)
    REFERENCES xfrm(xfrm_id);

ALTER TABLE ONLY spider_document
    ADD CONSTRAINT xfrm_id_fk FOREIGN KEY (xfrm_id)
    REFERENCES xfrm(xfrm_id);
