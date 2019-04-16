<?php
require_once(__DIR__ . '/JsonLog.php');

/**
 * Class ReadData
 * Collect Data to send to the Monitoor API
 */
class ReadXmlData
{
    static $debug_ = false;

    /**
     * turn debug mode on or off
     * @param $value
     */
    public static function setDebug($value)
    {
        static::$debug_ = ($value !== false);
    }

    /**
     * @param $xml
     * @param DataConverter $dataConverter
     * @return string[]
     */
    public static function getLinks(SimpleXMLElement $xml, DataConverter $dataConverter)
    {
        $filter = $dataConverter->getFilterDefintion();
        $links = $xml->xpath($filter);
        $out = [];
        foreach ($links as $link) {
            $out[] = $link->__toString();
        }
        return $out;
    }

    /**
     * example of what the data array is suppost to look like
     * @return array
     */
    public function first()
    {
        return array(
            'id' => 845,
            'data' => array(
                array(
                    "timestamp" => "2015-12-01T09:00:22",
                    "addition" => "Dies ist der neue Text von 1.12.2015",
                    "deletion" => ""
                ),
                array(
                    "timestamp" => "2015-12-01T09:00:23",
                    "addition" => "Dies ist der noch neuere Text von 1.12.2015",
                    "deletion" => ""
                )
            )
        );
    }

    /**
     * @param $directory
     * @param $filename_regex
     * @param $valueList
     * @param $text_data_def
     * @param SendJson $jsonHandler
     * @throws exception
     * Goes through a directory and picks all files with names corresponding to a regex
     */
    public static function sendDirectory($directory, $filename_regex, $valueList, $text_data_def, SendJson $jsonHandler)
    {
        $ok= true;
        if (!$handle = opendir($directory)) {
            $ok = false;
            throw new exception('could not open dir: \'' . $directory . '\''); /* todo how should exceptions be used? */
        }
        while ($ok && ($entry = readdir($handle)) !== false) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            if (preg_match($filename_regex, $entry)) {
                self::sendFile($directory.$entry, $valueList, $text_data_def, $jsonHandler);
            }
        }
    }

    /**
     * @param $filename
     * @param $valueList
     * @param $text_data_def
     * @param SendJson $jsonHandler
     */
    public static function sendFile($filename, $valueList, $text_data_def, SendJson $jsonHandler)
    {
        $data = static::readFile($filename, $valueList);
        $data = static::convertData($data, $text_data_def);
        $data = static::make_proper_timestamp($data);
        $jsonHandler->wrap_and_send($data);
    }

    /**
     * @param $fileName
     * @param $valueList
     * @return array
     * Interprets the xml, picks out values, puts them in placeholder array (valueList)
     */
    private static function readFile($fileName, $valueList)
    {
        $txt = file_get_contents($fileName);
        $doc = simplexml_load_string($txt);
        $results = array();
        if ($doc !== false) {
            foreach ($valueList as $listName => $keyName) {
                $a = $doc->xpath($keyName);
                if (empty($a)) {
                    $val = null;
                } else {
                    /* flatten the element with subelements and remove tags to get readable string */
                    $val = (string)($a[0]->asXML());
                }
                $results[$listName] = $val;
            }
        }
        return $results;
    }

    /**
     * @param SimpleXMLElement $xml
     * @param DataConverter $dataConverterDefinition
     * @return bool
     */
    public static function checkIncludeRequirement(SimpleXMLElement $xml, DataConverter $dataConverterDefinition)
    {
        $requirement = $dataConverterDefinition->getIncludeRequirement();
        $a = $xml->xpath($requirement);
        return (!empty($a));
    }

    /**
     *
     * Send info out of an XML which has several notice entries in it
     * $top is the node-xpath at the top of each entry
     *
     * @param SimpleXMLElement $xml
     * @param DataConverter $dataConverterDefinition
     * @param SendJson $jsonHandler
     * @throws exception
     */
    public static function sendBundledXml(SimpleXMLElement $xml, DataConverter $dataConverterDefinition, SendJson $jsonHandler)
    {
        if ($dataConverterDefinition->get_monitor_id()) {
            Logger::log("setting monitor id=" . $dataConverterDefinition->get_monitor_id());
            $jsonHandler->setMonitoorId($dataConverterDefinition->get_monitor_id());
        }
        if ($dataConverterDefinition->getTopDefinition() == '/') {
            $count = 1;
        } else {
            $subElements = @$xml->xpath($dataConverterDefinition->getTopDefinition());
            if ($subElements === false) {
                Logger::log("no subelements found for " . $dataConverterDefinition->getTopDefinition());
                return;
            }
            $count = count($subElements);
        }
        Logger::log("elements found: $count");
        
        for ($i=1 ; $i<=$count ; ++$i) {
            $newValueList = array();
            $newTop = '(' . $dataConverterDefinition->getTopDefinition() . ')[' . $i . ']';
            foreach ($dataConverterDefinition->getPlaceholderDefinition() as $getType => $list) {
                foreach ($list as $listName => $keyName) {
                    $newValueList[$getType][$listName] = $newTop . $keyName;
                }
            }

            if ($dataConverterDefinition->usesId()) {
                $id = $xml->xpath($newTop . $dataConverterDefinition->getIdTag());
                $id = empty($id) ? null : $id[0];
                $logtext = $dataConverterDefinition->getIdTag() . '=' . $id;
                $done = JsonLog::is_in_logfile($logtext);
                if ($done) {
                    if (static::$debug_) Logger::log($logtext . ' is in logfile -> not sending');
                    continue;
                }
            }

            $dataConverterDefinition->setPlaceholderArray(static::readXml($xml, $newValueList));
            $data = static::createDataArray($dataConverterDefinition);

            $sentOk = $jsonHandler->wrap_and_send($data);
            if ($sentOk) {
                if ($dataConverterDefinition->usesId()) {
                    JsonLog::add_to_logfile($logtext);
                }
            }
        }
    }

    /**
     * @param $filename
     * @param $valueList
     * @param $text_data_def
     * @param SendJson $jsonHandler
     */
    public static function sendXml(SimpleXMLElement $xml, $valueList, $text_data_def, SendJson $jsonHandler)
    {
        $data = static::readXml($xml, $valueList);
        $data = static::convertData($data, $text_data_def);
        $data = static::make_proper_timestamp($data);
        $jsonHandler->wrap_and_send($data);
    }

    private static function readXml(SimpleXMLElement $xml, $valueList)
    {
        $results = [];
        foreach ($valueList as $getType => $list) {
            foreach ($list as $listName => $keyName) {
                $occurrence = @$xml->xpath($keyName);
                if ($occurrence === false) {
                    Logger::log("finding occurrence for $keyName failed");
                    $val = null;
                } else if (empty($occurrence)) {
                    $val = null;
                } else {
                    $val = [];
                    foreach ($occurrence as $x){
                        $val[] = static::interpretField($x, $getType);
                    }
                }
                $results[$listName] = $val;
            }
        }
        return $results;
    }

    private static function interpretField(SimpleXMLElement $x, $getType)
    {
        if ($getType == '') {
            $val = (string)$x->asXML();
            /* remove zero-width spaces, convert htmlentities to html, add newlines, remove tags, replace non breaking spaces, trim */
            $val = preg_replace( '/[\x{200B}-\x{200D}]/u', ' ', $val);
            $val = html_entity_decode($val);
            $val = preg_replace(['/<p>/','/<br *\/>/'],"\n",$val);
            $val = strip_tags($val);
            $val = str_replace("\xc2\xa0", ' ', $val);
            $val = trim($val);
        } else {
            $val = (string)$x->attributes()[$getType];
        }
        return $val;
    }

    /**
     * @param array $placeHolders
     * @param array $text_data_def
     * @return array
     * Takes placeholder array $placeHolders and $textDefArray
     * and replaces placeholders, marked with ##name## in $textDefArray texts
     */
    private static function convertData(array $placeHolders, array $text_data_def) {
        $placeholders = array_map(function($a) {
            return '##' . $a . '##';
        },array_keys($placeHolders));
        $out = [];
        foreach ($text_data_def as $name => $text_def) {
            $out[$name] = str_replace($placeholders,$placeHolders,$text_def);
        }
        return $out;
    }

    /**
     * @param $data array
     * @return array
     */
    private static function make_proper_timestamp($data) {
        /* make proper timestamp like "timestamp" => "2015-12-01T09:00:22", */
        $t = new DateTime($data['timestamp']);
        $data['timestamp'] = $t->format('Y-m-d\TH:i:s');
        return $data;
    }
    
    private function createDataArray(DataConverter $dataConverterDefinition)
    {
        return [
            'timestamp' => $dataConverterDefinition->getTimeStamp(),
            'addition' => $dataConverterDefinition->getText(),
            'deletion' => ''
        ];
    }

}
