<?php

namespace Datahouse\MON\I18;

/**
 * Class I18
 *
 * @package I18
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class I18
{
    /**
     * translate
     *
     * @param string $phrase the phrase to translate
     * @param int    $langId the lang id
     *
     * @return mixed
     */
    public function translate($phrase, $langId)
    {
        $lang_file = dirname(__FILE__) . '/i18.json';
        $lang_file_content = file_get_contents($lang_file);
        $translations = json_decode($lang_file_content, true);

        if (array_key_exists($langId . '_' . $phrase, $translations)) {
            return $translations[$langId . '_' . $phrase];
        }
        if (array_key_exists(1 . '_' . $phrase, $translations)) {
            return $translations[1 . '_' . $phrase];
        }
        return $phrase;
    }
}
