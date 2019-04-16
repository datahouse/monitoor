<?php

namespace Datahouse\MON\Frequency\Listing;

use Datahouse\MON\I18\I18;
use Datahouse\MON\Types\Gen\Frequency;

/**
 * Class Model
 *
 * @package Frequency
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Model extends \Datahouse\Framework\Model
{
    private $i18;

    /**
     * @param \PDO $pdo the pdo
     * @param I18  $i18 the i18
     */
    public function __construct(\PDO $pdo, I18 $i18)
    {
        $this->pdo = $pdo;
        $this->i18 = $i18;
    }

    /**
     * readFrequencyList
     *
     * @param int $langCode the language code
     *
     * @return array
     * @throws \Exception
     */
    public function readFrequencyList($langCode)
    {
        try {
            return $this->getFrequencies($langCode);
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ');
        }
    }

    /**
     * getFrequencies
     *
     * @param int $langCode the language code
     *
     * @return array
     */
    private function getFrequencies($langCode)
    {
        $query = 'SELECT check_frequency_id, check_frequency_text
                  FROM check_frequency
                  WHERE check_frequency_id > 0
                  ORDER BY check_frequency_id
                  ;';

        $frequencyList = array();
        foreach ($this->pdo->query($query) as $res) {
            $frequency = new Frequency();
            $frequency->setId($res['check_frequency_id']);
            $frequency->setTitle(
                $this->i18->translate(
                    'check_frequency_' . $frequency->getId(),
                    $langCode
                )
            );
            $frequencyList[] = $frequency;
        }
        return $frequencyList;
    }
}
