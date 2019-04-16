<?php

namespace Datahouse\MON\Url\Add;

use Datahouse\MON\Exception\UrlsExceededException;
use Datahouse\MON\Model\UrlModel;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\Types\Gen\Url;

/**
 * Class Model
 *
 * @package     Url
 * @author      Peter Müller (pem) <peter.mueller@datahouse.ch>
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
     * createUrl
     *
     * @param array  $urls         the url array
     * @param string $urlGroupName the group name
     * @param int    $userId       the user
     *
     * @return string
     * @throws \Exception
     */
    public function createUrl(array $urls, $urlGroupName, $userId)
    {
        $query = '';
        try {
            $this->pdo->beginTransaction();
            $urlGroupId = null;
            $alertExists = true;
            $urlIds = array();
            foreach ($urls as $url) {
                if (($url->getUrlGroupId() <= 0 || $url->getUrlGroupId() == null) && $urlGroupId == null) {
                    $alertExists = false;
                    $urlGroupId = $this->addUrlGroup(
                        $urlGroupName,
                        $userId
                    );
                } else if ($url->getUrlGroupId() != null &&  $url->getUrlGroupId() > 0) {
                    $urlGroupId = $url->getUrlGroupId();
                }
                $this->checkNbrOfUrls($userId);
                $urlId = $this->insertUrl($url, $urlGroupId, $userId);
                $this->insertAccessControl($userId, $urlId, $urlGroupId);
                $urlIds[] = $urlId;
            }
            $this->pdo->commit();
            return array(
                'urlGroupId' => $urlGroupId,
                'alertExists' => $alertExists,
                'urlIds' => $urlIds
            );
        } catch (UrlsExceededException $ue) {
            $this->pdo->rollBack();
            throw $ue;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }

    /**
     * checkNbrOfUrls
     *
     *
     * @return void
     * @throws UrlsExceededException
     * @throws \Exception
     */
    private function checkNbrOfUrls($userId)
    {
        $count = $this->getNbrOfUrls($userId);
        if (count($count) == 0) {
            return;
        }
        $plan = $count['plan'];
        $nbr = $count['count'];
        if ($plan == 1 && $nbr >= 10) {
            throw new UrlsExceededException();
        }
        if ($plan == 2 && $nbr >= 30) {
            throw new UrlsExceededException();
        }
    }
}
