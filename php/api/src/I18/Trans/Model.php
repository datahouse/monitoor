<?php

namespace Datahouse\MON\I18\Trans;

/**
 * Class Model
 *
 * @package Alert
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Model extends \Datahouse\Framework\Model
{

    /**
     * @param \PDO $pdo the pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * getTranslation
     *
     * @param $part         string  name of page which should be translated
     * @param $langCode     string  langCode (e.g. de) for file
     * @return mixed
     * @throws \Exception
     */
    public function getTranslation($part, $langCode)
    {
        try {
/*            $query = 'SELECT i18_trans_lang_id, i18_trans_lang_desc, ';
            $query .= ' i18_trans_key, i18_trans_text FROM i18_trans ';
            $query .= ' ORDER BY i18_trans_lang_id, i18_trans_key ';
            $translations = array();
            $trans = array();
            $langId = null;
            foreach ($this->pdo->query($query) as $res) {
                if ($res['i18_trans_lang_desc'] != $langId) {
                    if ($langId != null) {
                        $translations[$langId] = $trans;
                    }
                    $trans = array();
                }
                $trans[$res['i18_trans_key']] = $res['i18_trans_text'];
                $langId = $res['i18_trans_lang_desc'];
            }
            $translations[$langId] = $trans;*/
            if ($langCode <> 1 && $langCode <> 2) {
                $langCode = 1;
            }
            $lang_file = dirname(__FILE__) . '/trans_' . $langCode . '.json';
            $lang_file_content = file_get_contents($lang_file);
            $translations = json_decode($lang_file_content, true);

            $trans = array();
            foreach($translations as $key => $value){
                $expKey = explode('.', $key);
                if($expKey[0] == $part){
                    $trans[$key] = $value;
                }
            }
            return $trans;
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ');
        }
    }
}
