-- clean up

DROP TABLE IF EXISTS site;
DROP TABLE IF EXISTS path;
DROP TABLE IF EXISTS version;
DROP TABLE IF EXISTS hit;

-- table structure

CREATE TABLE site (
    site_id MEDIUMINT(6) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    site_uri VARCHAR(500) NOT NULL,
    site_description TEXT NULL,
    site_active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE path (
    path_id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    site_id MEDIUMINT(6) NOT NULL,
    path_xpath VARCHAR(500) NOT NULL,
    path_format VARCHAR(100) NULL,
    path_body_width SMALLINT(3) NULL,
    path_description TEXT NULL
);

CREATE TABLE version (
    version_id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    path_id INT(11) NOT NULL,
    version_hash VARCHAR(32),
    version_timestamp datetime NOT NULL
);
-- version_timestamp is a cache-only field; could be recalculated
-- but it makes getting the latest version(s) faster by orders of magnitude

CREATE TABLE hit (
    hit_id BIGINT(20) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    version_id INT(11) NOT NULL,
    hit_timestamp datetime NOT NULL
);

-- example values

INSERT INTO site (site_id, site_uri, site_description) VALUES
  (1,'http://www.datahouse.ch','Datahouse'),
  (2,'http://www.astra.admin.ch/dokumentation/00109/00113/index.html?lang=de','ASTRA'),
  (3,'http://www.wuestundpartner.com/de/','Wüest & Partner'),
  (4,'http://www.zh.ch/internet/de/home.html','Kanton ZH'),
  (5,'http://www.bbc.com/news/','BBC'),
  (6,'http://www.watson.ch','watson');

INSERT INTO path (path_id, site_id, path_xpath, path_format, path_body_width, path_description) VALUES
  (1,1,'//body',NULL,80,'(gesamte Seite)'),
  (2,2,'//div[@class="rssTitel" or @class="rssColumnLeft"]//text()','%s -- %s',0,'Medienmitteilungen'),
  (3,3,'//div[@class="col_9 maincol"]',NULL,80,'Homepage Text'),
  (4,3,'//div[@class="newsTeaserList smallnews"]/p/span[@class="date"]/../*/text()','%s, %s',0,'News'),
  (5,4,'//div[@class="row"]/p[@class="date"]/text()|//div[@class="row"]/h3/*/text()','%s; %s',0,'News'),
  (6,5,'//div[@id="container-top-stories-with-splash"]//a[@class="story"]/text()',NULL,0,'Top Stories'),
  (7,6,'//body',NULL,80,'(gesamte Seite)');

