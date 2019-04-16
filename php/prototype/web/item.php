<?php

require_once('db.inc.php');

$path = 0;
$back = 0;
$type = 'text';

if (isset($_GET['path'])) $path = intval($_GET['path']);
if (isset($_GET['back'])) $back = intval($_GET['back']);
if (isset($_GET['type']) && in_array($_GET['type'],array('text','date','uri'))) $type = $_GET['type'];

$q = mysql_query("SELECT version_hash AS hash, version_timestamp AS date, site_uri AS uri FROM version JOIN path USING (path_id) JOIN site USING (site_id) WHERE path_id=$path ORDER BY version_timestamp DESC LIMIT $back,1",$db);

while ($row = mysql_fetch_assoc($q)) {
    $hash = $row['hash'];
    $date = $row['date'];
    $uri = $row['uri'];
}


header("Content-type: text/plain; charset=utf-8");

switch ($type) {
case 'text':
     $file = '/srv/monitor/store/' . substr($hash,0,1) . '/' . substr($hash,1,1) . '/' . substr($hash,2,1) . '/' . substr($hash,3);
     $fp = fopen($file, 'rb');
     fpassthru($fp);
     break;
case 'date':
     echo $date;
     break;
case 'uri':
     echo $uri;
     break;
}

