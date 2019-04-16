#! /usr/bin/php
<?php

require_once(__DIR__ . '/../common/ReadXmlData.php');
require_once(__DIR__ . '/../common/SendJson.php');
require_once(__DIR__ . '/../common/JsonLog.php');
require_once(__DIR__ . '/../common/Logger.php');

require_once(__DIR__ . '/DataConverterAwp.php');

$param = getopt('c:',array('config:','file:','dir-in:','dir-out:','date:','no-send','dump::','debug','help'));
//var_dump($param);
$configName = isset($param['c']) ? $param['c'] : null;
if (is_null($configName)) {
    $configName = isset($param['config']) ? $param['config'] : null;}
$dir_in  = isset($param['dir-in'])  ? $param['dir-in']  : null;
$dir_out = isset($param['dir-out']) ? $param['dir-out'] : null;

if (is_null($configName) || is_null($dir_in) || isset($param['help'])) {
    echo "\nusage: -c|--config [test|live] --dir-in [input_dir] --dir-out [output_dir]\n";
    echo "--config / -c\tloads conf_###.php\n";
    echo "--dir-in input directory\n";

    echo "\noptional:\n";
    echo "--dir-out directory to move done files to\n";
    echo "--file\t\tloads input from given file instead of directory\n";
    echo "--date\t\tloads different date. possible: --date now / yesterday / 20.5.2016\n";
    echo "--no-send\tdo not send json (log will not be written)\n";
    echo "--debug\t\toutput JSON to stdout\n";
    echo "--help\t\tthis output\n\n";

    die(1);
}
require(__DIR__ . '/config_' . $configName . '.php');

if (isset($param['date'])) {
    $d = new DateTime($param['date']);
} else {
    $d = new DateTime();
    $d->add(DateInterval::createFromDateString('now'));
}

$files = [];
try {
    if (isset($param['file'])) {
        $files[] = $param['file'];
    } else {
        /* just 2 min old files, as they may be still in upload */
        $files = explode("\n",shell_exec('find "' . $param['dir-in'] . '" -mmin +2 -iname \*.xml'));
        array_pop($files);
    }
} catch (exception $e) {
    Logger::send_exception_mail($e,array(MON_PASSWORD));
    die;
}

$jsonHandler = new SendJson(MON_NAME, MON_PASSWORD, MON_LOGIN_URL, MON_SEND_URL);
//$jsonHandler->setDebug(1);
if (isset($param['debug'])) {
    $jsonHandler->setDebug(1);
}
if (isset($param['no-send'])) {
    $jsonHandler->setDoNotSend();
}

foreach ($files as $filename) {
    try {
        $xml = file_get_contents($filename);
        $doc = simplexml_load_string($xml);
        $d = new DataConverterAwp();
        if (ReadXmlData::checkIncludeRequirement($doc,$d)) {
            ReadXmlData::sendBundledXml($doc, $d, $jsonHandler);
        }
        if (!isset($param['no-send']) && isset($param['dir-out'])) {
            $newname = explode('/',$filename);
            $newname = $param['dir-out'] . '/' . array_pop($newname);
            rename($filename, $newname);
        }

    } catch (exception $e) {
        Logger::send_exception_mail($e,array(MON_PASSWORD));
        die;
    }
}

