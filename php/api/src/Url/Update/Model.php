<?php

namespace Datahouse\MON\Url\Update;

use Datahouse\MON\Model\UrlModel;
use Datahouse\MON\Types\Gen\Url;

/**
 * Class Model
 *
 * @package Url
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Model extends UrlModel
{

    /**
     * @param \PDO $pdo the pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * updateUrl
     *
     * @param Url $url              the url
     * @param string $urlGroupName  the group name
     * @param int $userId           the user
     *
     * @return bool
     * @throws \Exception
     */
    public function updateUrl(Url $url, $urlGroupName, $userId)
    {
        $query = '';
        try {
            $this->pdo->beginTransaction();

            $urlGroupId = $url->getUrlGroupId();
            if ($urlGroupId <= 0 || $urlGroupId == null) {
                $urlGroupId = $this->addUrlGroup(
                    $urlGroupName,
                    $userId
                );
            }

            if ($url->getFrequency() == null) {
                $url->setFrequency(self::$DEFAULT_FREQUENCY);
            }
            $query .= 'UPDATE url SET url_title = :title, ';
            $query .= ' url = :url, check_frequency_id = :frequency,';
            $query .= ' url_group_id = :urlGrpId ';
            if ($url->getXpath() != null && strlen($url->getXpath()) > 0) {
                $transformation = $this->evaluateTransformation($url->getUrl(), $url->getXpath());
                $query .=
                    ', xfrm_id = get_xfrm_id(\'' . $transformation[0] . '\',\'' .
                    $transformation[1] .
                    '\')'; // --otherwise custom xpath expression will be lost
            }
            $query .= ' WHERE url_id=:urlId ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':title', $url->getTitle(), \PDO::PARAM_STR);
            $stmt->bindValue(':url', $url->getUrl(), \PDO::PARAM_STR);
            $stmt->bindValue(':urlId', $url->getId(), \PDO::PARAM_INT);
            $stmt->bindValue(':urlGrpId', $urlGroupId, \PDO::PARAM_INT);
            $stmt->bindValue(
                ':frequency',
                $url->getFrequency(),
                \PDO::PARAM_INT
            );
            $stmt->execute();
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }
}
