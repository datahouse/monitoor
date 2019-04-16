#! /usr/bin/php
<?php

require_once(__DIR__ . '/../common/Communicator.php');
require_once(__DIR__ . '/../common/ReadXmlData.php');
require_once(__DIR__ . '/../common/SendJson.php');
require_once(__DIR__ . '/../common/JsonLog.php');
require_once(__DIR__ . '/../common/Logger.php');

$converterClasses = [
    'Handelsregister', /* Handelsregister */
    'SB01',            /* SB01 = Betreibungsamtliche Grundstücksteigerung */
    'SB02',            /* SB02 = Zahlungsbefehle */
    'SB03',            /* SB03 = Arrestbefehle und -urkunden */
    'SB04',            /* SB04 = Pfändungsanzeigen und -urkunden */
    'SB05',            /* SB05 = Bereinigung des Eigentumsvorbehaltsregisters */
    'SB06',            /* SB06 = Verschiedenes */
    'KK08',            /* KK08 = Konkursamtliche Grundstücksteigerung */
];
foreach ($converterClasses as $class) {
    require_once(__DIR__ . '/DataConverter' . $class . '.php');
}

$param = getopt('c:',array('config:','file:','date:','no-send','dump::','debug','help'));
//var_dump($param);
$configName = isset($param['c']) ? $param['c'] : null;
if (is_null($configName)) {
    $configName = isset($param['config']) ? $param['config'] : null;}
if (is_null($configName) || isset($param['help'])) {
    echo "\nusage: -c|--config [test|live] [--file filename.xml] [--date date] [--no-send] [--dump[=filename.xml]] [--debug]\n";
    echo "--config / -c\tloads conf_###.php\n";
    echo "--file\t\tloads input from given file instead of soap\n";
    echo "--date\t\tloads different date. possible: --date now / yesterday / 20.5.2016\n";
    echo "--no-send\tdo not send json (log will not be written)\n";
    echo "--dump\t\toutput to xml file logfiles/shab_##-##-##.xml or given name in logfiles/ \n";
    echo "--debug\t\toutput JSON to stdout\n";
    echo "--help\t\tthis output\n\n";

    die(1);
}
require(__DIR__ . '/config_' . $configName . '.php');

if (isset($param['date'])) {
    $d = new DateTime($param['date']);
} else {
    $d = new DateTime();
    $d->add(DateInterval::createFromDateString('yesterday'));
}
$date = $d->format('Y-m-d');
$tempDir = __DIR__ . '/temp/';
if (isset($param['dump'])) {
    if ($param['dump']) {
        Logger::set_log_filename(__DIR__ . '/logfiles/' . $param['dump'] . '.log');
    } else {
        Logger::set_log_filename(__DIR__ . '/logfiles/' . "shab_log_$date.log");
    }
}
ReadXmlData::setDebug(isset($param['debug']));

try {
    if (isset($param['file'])) {
        $xml = simplexml_load_string(file_get_contents($param['file']));
    } else {
        /*
         * step 1: load bulk with references to publications
         */
        Logger::log('Step 1: load bulk XML with references to publications');
        $requestSize = 2000; /* 2000 ist the max for the API */
        $xml = Communicator::communicationGetXml(API_URL . '&pageRequest.page=0&publicationDate.start=' . $date . '&publicationDate.end=' . $date . '&pageRequest.size=' . $requestSize);

        $t = $xml->xpath('//bulk:bulk-export/total');
        if (count($t) < 1) {
            throw new exception('Total not found in SHAB XML');
        }
        $total = intval($t[0]->__toString());
        Logger::log("total=$total");
        $repeats = floor(($total - 1) / $requestSize);
        Logger::log('pages=' . ($repeats + 1));

        $bulk = dom_import_simplexml($xml->xpath('//bulk:bulk-export')[0]);
        /* join pages */
        for ($i = 1 ; $i <= $repeats ; ++$i) {
            Logger::log(API_URL . '&pageRequest.page=' . $i . '&publicationDate.start=' . $date . '&publicationDate.end=' . $date . '&pageRequest.size=' . $requestSize);
            $xml2 = Communicator::communicationGetXml(API_URL . '&pageRequest.page=' . $i . '&publicationDate.start=' . $date . '&publicationDate.end=' . $date . '&pageRequest.size=' . $requestSize);
            foreach ($xml2->xpath('//bulk:bulk-export/publication') as $publication) {
                /* deep stiching two xml files together doesn't work in simplexml, so use DomNodes */
                $insert_dom = $bulk->ownerDocument->importNode(dom_import_simplexml($publication), true);
                $bulk->appendChild($insert_dom);
            }
        }
    }
    if (isset($param['dump'])) {
        $xml_filename = $param['dump'] ? $param['dump'] : 'shab_bulk_' . $date . '.xml';
        Logger::log('saving to ' . __DIR__ . '/logfiles/' . $xml_filename);
        $xml->saveXML(__DIR__ . '/logfiles/' . $xml_filename);
    }

    /*
     * step 2: download wanted publications and stich into one xml
     */
    Logger::log('Step 2: Download publications and save as ' . count($converterClasses) . ' XML files');
    foreach ($converterClasses as $classPart) {
        Logger::log("filtering $classPart");
        $classXml = new domDocument;
        $classXml->loadXML("<root/>");
        $classXml->encoding = 'utf-8';
        $class = 'DataConverter' . $classPart;
        $link_list = ReadXmlData::getLinks($xml, new $class());
        $max = count($link_list);
        $count = 0;
        foreach ($link_list as $link) {
            Logger::log("downloading $link, " . ++$count . " / $max");
            $xmlPublication = Communicator::communicationGetXml($link);
            $insert_dom = $classXml->importNode(dom_import_simplexml($xmlPublication), true);
            $classXml->getElementsByTagName('root')[0]->appendChild($insert_dom);
        }
        $classXml->save($tempDir . $classPart . '.xml');
    }

} catch (exception $e) {
    if (isset($param['debug'])) {
        Logger::log(var_export($e, true));
    } else {
        Logger::send_exception_mail($e, array(MON_PASSWORD, SOAP_PASSWORD));
    }
    die;
}

/*
 * step 3: read information out of xml files and send to Monitoor
 */
Logger::log('Step 3: Generate Monitoor entries ans send via JSON');
$jsonHandler = new SendJson(MON_NAME, MON_PASSWORD, MON_LOGIN_URL, MON_SEND_URL);

if (isset($param['debug'])) {
    $jsonHandler->setDebug(1);
}
if (isset($param['no-send'])) {
    $jsonHandler->setDoNotSend();
}

Logger::log('sending to Monitoor');
try {
    foreach ($converterClasses as $classPart) {
        Logger::log("sending $classPart to Monitoor");
        $class = 'DataConverter' . $classPart;
        $class = new $class();
        $xml = simplexml_load_string(file_get_contents($tempDir . $classPart . '.xml'));
        Logger::log('Using "' . $tempDir . $classPart . '.xml"');
        ReadXmlData::sendBundledXml($xml, $class, $jsonHandler);
    }

} catch (exception $e) {
    if (isset($param['debug'])) {
        Logger::log(var_export($e, true));
    } else {
        Logger::send_exception_mail($e,array(MON_PASSWORD,SOAP_PASSWORD));
    }
    die;
}

if (isset($param['dump'])) {
    foreach ($converterClasses as $classPart) {
        rename($tempDir . $classPart . '.xml', __DIR__ . '/logfiles/shab_' . $classPart . '_' . $date . '.xml');
    }
}

Logger::log('finished');

