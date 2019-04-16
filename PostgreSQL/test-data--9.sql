INSERT INTO mon_user(user_email, user_password, user_password_salt,
                     user_valid_from)
VALUES
    ('peter.mueller@datahouse.ch',
     '5aa5a330c478658204d4753b02484cf3c2d9be3d703dc36e8d21ad8ff784d003',
     'e[(?3oMm+Xc|YHMF', NOW()),
    ('dena.moshfegh@datahouse.ch',
     '5aa5a330c478658204d4753b02484cf3c2d9be3d703dc36e8d21ad8ff784d003',
     'e[(?3oMm+Xc|YHMF', NOW()),
    ('markus.wanner@datahouse.ch',
     '5aa5a330c478658204d4753b02484cf3c2d9be3d703dc36e8d21ad8ff784d003',
     'e[(?3oMm+Xc|YHMF', NOW());

INSERT INTO account(user_id, account_name_first, account_name_last,
                    account_mobile)
VALUES
    (1, 'Peter', 'Müller', '+41794888778');

INSERT INTO url(url_title, url, url_creator_user_id, check_frequency_id, xfrm_id)
VALUES
    ('Datahouse Website', 'http://www.datahouse.ch/technologie', 1, 1, 1),
    ('Wüest & Partner', 'http://www.wuestundpartner.com/en/online-services/overview.html', 1, 1, 1),
    ('Tagesanzeiger Online', 'http://www.tagesanzeiger.ch', 1, 2, 1),
    ('IFES Evaluationen', 'http://www.ifes-ipes.ch/de/ueber-das-ifes/', 1, 2, 1),
    ('Abacus Produkte', 'http://www.abacus.ch/produkte/abacus-vi/produktportrait/', 1, 3, 1),
    ('Datahouse Mitarbeiter', 'http://www.datahouse.ch/team', 1, 3, 1),
    ('PHP Storm News', 'https://www.jetbrains.com/phpstorm/whatsnew/', 1, 2, 1),
    ('Kinoprogramm Zürich', 'http://www.cineman.ch/kinoprogramm/Zürich/', 1, 2, 1),
    ('ZKB Finanzinformationen', 'https://zkb-finance.mdgms.com/home/index.html', 1, 2, 1),
    ('Meet Jenkins', 'https://wiki.jenkins-ci.org/display/JENKINS/Meet+Jenkins', 1, 2, 1),
    ('PHP Release', 'http://php.net/', 1, 2, 1),
    ('ESRI Schweiz', 'http://www.esri.ch/schulung/kursangebot', 1, 2, 1),
    ('Geopost Daten', 'https://www.post.ch/post-startseite/post-adress-services-match/post-gis/post-gis-geoportal.htm', 1, 2, 1);

INSERT INTO alert(user_id)
VALUES
    (1),
    (1),
    (1),
    (1),
    (1),
    (1),
    (1),
    (1),
    (1),
    (1),
    (1),
    (1),
    (1);

-- FIXME: before, there used to be an alert_x_url table, but now we
-- need a url_group. However, it's not created for the test data, yet.

INSERT INTO alert_x_type_cycle(alert_id, type_x_cycle_id)
VALUES
    (1, 1),
    (1, 2),
    (2, 1),
    (3, 3),
    (4, 1),
    (4, 3),
    (5, 1),
    (6, 1),
    (6, 2),
    (7, 1),
    (7, 2),
    (8, 1),
    (8, 2),
    (8, 3),
    (9, 2),
    (10, 2),
    (11, 1),
    (11, 2),
    (12, 3),
    (13, 2),
    (13, 3);

INSERT INTO alert_keyword(alert_keyword)
VALUES
    ('data'),
    ('house'),
    ('statistic'),
    ('test'),
    ('php');

INSERT INTO alert_x_keyword (alert_id, alert_keyword_id)
VALUES
    (1,1),
    (1,2),
    (2,3),
    (5,4),
    (10,5);

INSERT INTO spider_document (job_id, reception_ts, contents, xfrm_id, contents_hash)
  VALUES (11, 'Tue, 15 Nov 1994 08:12:31 GMT', 'Some sample data.', 1, digest('Some sample data.', 'sha256')),
         (11, 'Tue, 15 Nov 2004 08:12:31 GMT', 'Some more sample data.', 1, digest('Some more sample data.', 'sha256')),
		 (1, 'Fri, 22 May 2013 05:29:19 GMT', 'Datahouse Technologie v1', 1, digest('Datahouse Technologie v1', 'sha256')),
		 (1, 'Fri, 22 May 2014 05:29:19 GMT', 'Datahouse Technology v2', 1, digest('Datahouse Technology v2', 'sha256')),
		 (1, 'Fri, 22 May 2015 05:29:19 GMT', 'Datahouse Technology and Services v3', 1, digest('Datahouse Technology and Services v3', 'sha256'));

INSERT INTO spider (spider_uuid, spider_last_seen, spider_last_hostname)
  VALUES ('deadbeef-0b92-461f-aa5e-5872a6ce8057', now(), 'nowhere.example.com');

INSERT INTO notification (alert_id, url_id, type_x_cycle_id,
	   old_doc_id, new_doc_id, spider_uuid, delivery_ts)
  VALUES
    (1, 1, 1, 3, 4, 'deadbeef-0b92-461f-aa5e-5872a6ce8057', now() - INTERVAL '5 hours'),
    (1, 1, 2, 3, 4, 'deadbeef-0b92-461f-aa5e-5872a6ce8057', NULL),
    (1, 1, 1, 4, 5, 'deadbeef-0b92-461f-aa5e-5872a6ce8057', now() - INTERVAL '5 hours'),
    (1, 1, 2, 4, 5, 'deadbeef-0b92-461f-aa5e-5872a6ce8057', NULL),
    (11, 11, 1, 1, 2, 'deadbeef-0b92-461f-aa5e-5872a6ce8057', now() - INTERVAL '3 days'),
    (11, 11, 2, 1, 2, 'deadbeef-0b92-461f-aa5e-5872a6ce8057', NULL);
