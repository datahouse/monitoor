<?php

namespace Datahouse\MON\Urlgroup\Get;

use Datahouse\MON\Exception\KeyNotFoundException;
use Datahouse\MON\Types\Gen\UrlGroup;
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

    /**
     * @param \PDO $pdo the pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * readUrlGroup
     *
     * @param int $urlGroupId the alert id
     * @param int $userId     the user id
     *
     * @return UrlGroup
     * @throws KeyNotFoundException
     * @throws \Exception
     */
    public function readUrlGroup($urlGroupId, $userId)
    {
        $urlGroup = new UrlGroup();

        $query = '';
        try {
            $query = 'SELECT g.url_group_id, g.url_group_title, g.url_group_description, ';
            $query .= 'u.url_id, u.url_title, u.url, g.is_subscription ';
            $query .= ' FROM url_group g ';
            $query .= ' LEFT JOIN url u ON (g.url_group_id = u.url_group_id) ';
            $query .= ' WHERE g.url_group_id = :urlGroupId';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':urlGroupId', $urlGroupId, \PDO::PARAM_INT);
            $stmt->execute();
            $urls = array();
            foreach ($stmt->fetchAll() as $res) {
                $urlGroup->setId($res['url_group_id']);
                $urlGroup->setTitle($res['url_group_title']);
                $urlGroup->setAlertId($this->getAlertId($res['url_group_id'], $userId));
                $urlGroup->setDescription($res['url_group_description']);
                $urlGroup->setReadOnly($res['is_subscription']);
                $urlGroup->setSubscribed($res['is_subscription']);
                if ($res['url_id'] != null) {
                    $url = new Url();
                    $url->setExternal((0 === strpos($res['url'], 'external')));
                    $url->setId($res['url_id']);
                    $url->setTitle($res['url_title']);
                    $url->setUrl($res['url']);
                    $urls[] = $url;
                }
            }
            $urlGroup->setUrls($urls);
            if ($urlGroup->getId() > 0) {
                return $urlGroup;
            }
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
        throw new KeyNotFoundException('no url group with id ' . $urlGroupId);
    }

    /**
     * getAlertId
     *
     * @param int $urlGroupId the group id
     * @param int $userId     the userid
     *
     * @return null
     */
    private function getAlertId($urlGroupId, $userId)
    {
        $query = 'SELECT a.alert_id FROM alert_x_url_group x ';
        $query .=
            'JOIN alert a ON (x.alert_id = a.alert_id) WHERE url_group_id=' .
            intval($urlGroupId);
        $query .= ' AND a.user_id = ' . intval($userId) . ' AND alert_active ';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $res['alert_id'];
        }
        return null;
    }
}
