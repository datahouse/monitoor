<?php

require_once('db.inc.php');

$path = 0;
$back = 0;

if (isset($_GET['path'])) $path = intval($_GET['path']);
if (isset($_GET['back'])) $back = intval($_GET['back']);

$q = mysql_query("SELECT version_hash AS hash FROM version WHERE path_id=$path ORDER BY version_timestamp DESC LIMIT $back,2",$db);

$hash1 = null;
$hash2 = null;
if ($row = mysql_fetch_assoc($q)) {
    $hash1 = $row['hash'];
}
if ($row = mysql_fetch_assoc($q)) {
    $hash2 = $row['hash'];
}


header("Content-type: text/plain; charset=utf-8");

if (!empty($hash1) && !empty($hash2)) {

    $file1 = '/srv/monitor/store/' . substr($hash1,0,1) . '/' . substr($hash1,1,1) . '/' . substr($hash1,2,1) . '/' . substr($hash1,3);
    $file2 = '/srv/monitor/store/' . substr($hash2,0,1) . '/' . substr($hash2,1,1) . '/' . substr($hash2,2,1) . '/' . substr($hash2,3);
    $cmd = "diff $file2 $file1 | grep '^[<>]' | sed 's/^>/+/' | sed 's/^</-/'";
    echo system($cmd);

}

