<?php

namespace Datahouse\MON\Url\Get;

use Datahouse\MON\Exception\KeyNotFoundException;
use Datahouse\MON\I18\I18;
use Datahouse\MON\Types\Gen\Frequency;
use Datahouse\MON\Types\Gen\Url;

/**
 * Class Model
 *
 * @package Url
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
     * readUrl
     *
     * @param int $urlId the alert id
     * @param int $langCode the lang cdode
     *
     * @return Url
     * @throws KeyNotFoundException
     * @throws \Exception
     */
    public function readUrl($urlId, $langCode)
    {
        $url = new Url();

        $query = '';
        try {
            $query .= 'SELECT u.url_id, url_title, url, u.check_frequency_id, ';
            $query .= ' check_frequency_text, u.url_group_id, xfrm_args->>\'xpath\' AS xpath FROM url u  ';
            $query .= ' LEFT JOIN check_frequency c ON (u.check_frequency_id = c.check_frequency_id) ';
            $query .= ' LEFT JOIN xfrm x ON (u.xfrm_id = x.xfrm_id) ';
            $query .= ' WHERE u.url_id = ' . intval($urlId) . ' AND url_active';
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $url->setExternal((0 === strpos($res['url'], 'external')));
                $url->setUrl($res['url']);
                $url->setId($res['url_id']);
                $url->setTitle($res['url_title']);
                $url->setUrlGroupId($res['url_group_id']);
                $url->setFrequency($res['check_frequency_id']);
                $url->setFrequencyOptions($this->getFrequencyList($langCode));
                $url->setXpath($res['xpath']);
                return $url;
            }
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
        throw new KeyNotFoundException('no url with id ' . $urlId);
    }

    /**
     * getFrequencies
     *
     * @param int $langCode the lang cdode
     *
     * @return array
     */
    private function getFrequencyList($langCode)
    {
        $query =
            'select check_frequency_id, check_frequency_text from check_frequency ';
        $query .= ' order by check_frequency_id ';
        $frequencyList = array();
        foreach ($this->pdo->query($query) as $res) {
            $frequency = new Frequency();
            $frequency->setTitle(
                $this->i18->translate(
                    'check_frequency_' . $res['check_frequency_id'],
                    $langCode
                )
            );
            $frequency->setId($res['check_frequency_id']);
            $frequencyList[] = $frequency;
        }
        return $frequencyList;
    }
}
