-- Use this to convert an existing database at schema version v36 into something
-- that ddb can handle. Has been applied to the demo and live system as is.

CREATE SCHEMA __dh;

SET search_path = __dh, pg_catalog;

CREATE TABLE __applied_migrations (
	ts timestamp with time zone DEFAULT now() NOT NULL,
	hash character(40) NOT NULL,
	filename text NOT NULL
);

ALTER TABLE __applied_migrations
	ADD CONSTRAINT __applied_migrations_pkey PRIMARY KEY (hash, ts);

INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:49.237637+01', 'a32034c7f381a6529b446d4ece94ba7f6f2e4f5a', 'postgres/default/schema/init.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.046634+01', '38264ac93a2ff514227c623ab31396d85ad69213', 'postgres/default/funcs/xor.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.052165+01', '21ec992396b69393254c5d45b5bcc118f468ca84', 'postgres/default/schema/add_change_has_delta_check.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.057129+01', 'e4b7d8f0d46835419735914147d4801e4804737f', 'postgres/default/funcs/notification_counter_inc.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.065276+01', '75677f24e3f6723d4862bc5346af5df4bcdb5572', 'postgres/default/schema/add_notification_counter_period_start_default.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.069866+01', 'c68ded8be7cb460eb3f17868fd45d45423f3b697', 'postgres/default/funcs/json_array_collapse.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.081876+01', '5cae191b73aaf5b584e5a2896f1eda92306856a7', 'postgres/default/funcs/json_uniq.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.087121+01', '0e83d4686a53ac0780504b36fff7927de20b14a3', 'postgres/default/funcs/add_external_change.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.091481+01', '1171dfefb134e358d3eb29308e17b6e5cee4cb5c', 'postgres/default/funcs/delete_changes_for.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.098323+01', '3df5042950f0a3283c7badc12948d9b7bb3ac834', 'postgres/default/funcs/change_type_agg.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.106159+01', '5bdfc1c8c3ad8590bac7fb667a3da3d655bf44e6', 'postgres/default/funcs/dequeue_live_task.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.112034+01', '207da7fe60358d89e114231b337edc350ea5aa8e', 'postgres/default/funcs/enqueue_live_task.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.12309+01', '8455fddc2b10326b24e42d4ca27382e61d0ac27a', 'postgres/default/views/spider_job_alert_type_cycle.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.144675+01', '5cae6acc89dff00c0deecbcfab92939536a8c92a', 'postgres/default/funcs/get_pending_changes_for.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.15608+01', 'd0e830cd61f4fb1465f6d8ac12c2c4ced4ce574d', 'postgres/default/funcs/get_xfrm_id.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.162265+01', '9526d517d67978c628abab373aa2a2276a31a022', 'postgres/default/funcs/materialize_change.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.178859+01', '04bd2a41b740d1cc154613602263b1380c22bfc8', 'postgres/default/funcs/spider_heartbeat.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.194277+01', '068cdbe71acb0ec5f6b0ee0b64a332ae605fc575', 'postgres/default/funcs/spider_job_maybe_add.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.206354+01', '51edb7ddf4536d63de33838bdf968792b35c478b', 'postgres/default/funcs/spider_job_update_or_delete.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.219855+01', 'd46fce72bda4fcb7bb33e44f27ae21f9950334e2', 'postgres/default/views/spider_status.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.234139+01', '72ae20ee19b2a376123950c7aeaef881ff3485a4', 'postgres/default/funcs/spider_rebalance_jobs.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.23915+01', '08b0f71c8577f9c395f94848d8be250acdc98d60', 'postgres/default/funcs/spider_store_document.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.247129+01', 'e64a564f2100462cc7e3c49f950b01e5fce16504', 'postgres/default/funcs/spider_update_job_meta.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.253028+01', '473e7187016d9e9e04672407bdf3b82e88a878bf', 'postgres/default/funcs/subscribe.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.259455+01', '8728811ea6ee19002fbc03f0f6df03deb227f806', 'postgres/default/funcs/unsubscribe.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.264381+01', '0d09ad0892bab86a0e7de092a77ec4de96f152c6', 'postgres/default/funcs/update_keywords_for_alert.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.269195+01', '25b4754205b569f3f4f6e0a3cab60c1735017f25', 'postgres/default/funcs/uri_encode.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.27357+01', 'fc8ff6a8c8461b41fd9be3332ea14da874ada9b7', 'postgres/default/funcs/url_add.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.277912+01', 'fc32161847ed1c0ace4851cb8e70b212b539bb87', 'postgres/default/funcs/url_filter.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.281899+01', '67b4e421caae82ca569080f4e81d4af0360ba19f', 'postgres/default/funcs/url_triggers.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.291189+01', '6f8dfbe159f107e067eb65651513e5c62f7c156f', 'postgres/default/triggers/url.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.295085+01', 'f72e09abbb70436e4e29b5de507609ef882cc523', 'postgres/default/views/alert_latest_notification.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.303731+01', '2d82c165c57ba08b212c5243a33d0a393803658a', 'postgres/default/views/notification_counter.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.309324+01', 'ba086b97f4ac82597d5bbd04d012e35edbdef5f9', 'postgres/default/views/spider_document_meta.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.31488+01', '5ad372d00a3dc778032c4a3d82f05954006f8641', 'postgres/default/views/spider_job_change.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.323411+01', 'fbfc7e32fe821fe6087641859426fa9422a4cf88', 'postgres/default/views/spider_load_agg.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.33554+01', '27350cf01eac772f393ddf91c22c7fe66ca363e7', 'postgres/default/views/v_alert_url_u_group.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.348083+01', 'f49400e727110ff58a05ca1b5018590a7a302033', 'postgres/default/views/v_change.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.35634+01', '8afdbd02584076f6701046c57c4baf8b83a9e5b4', 'postgres/default/views/notification_keyword.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.36321+01', 'bec1b681818e6bbae94c29f0907c7433b231432c', 'postgres/default/funcs/materialize_change.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.376415+01', 'd8491032289530add0fabf65992852954538b3fd', 'postgres/default/schema/add_user_id_to_notification_counter.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.40561+01', 'b514052dfe228e90323f2657a96c11660b03498b', 'postgres/default/views/notification_counter.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.411155+01', 'b990dfb23e2c325db9dca62852546acf3f9c2d9c', 'postgres/default/funcs/materialize_change.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.423177+01', 'b48c69d8d9d2e4b1049c7433628195ff09cba0d7', 'postgres/default/funcs/notification_counter_inc.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.431268+01', 'e933635ddb6ce7c4faf3ffa7e3ea61bf12d7030a', 'postgres/default/schema/repopulate_notification_counters.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.438551+01', 'b346aeb7d1b40a45d38865b0ecce5e06f0b4fd57', 'postgres/default/funcs/materialize_change.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.453487+01', '3343900647f79be326971a2e98f04f273f32783e', 'postgres/default/funcs/notification_counter_inc.sql');
INSERT INTO __applied_migrations VALUES ('2017-03-15 09:47:50.459981+01', '071d2829694629103047ad0ef7257fa4c50e75df', 'postgres/default/views/v_change.sql');

