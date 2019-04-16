<?php

namespace Datahouse\MON\Model;

use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\Types\Gen\Url;

/**
 * Class UrlModel
 *
 * @package Model
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
abstract class UrlModel extends UserModel
{

    /**
     * @var \PDO the pdo
     */
    protected $pdo;

    protected static $DEFAULT_TRANSFORMATION_KEY = 'xpath|html2markdown'; // default ist xpath and html2markdown
    protected static $DEFAULT_TRANSFORMATION_VALUE = '{"xpath": "//body"}';

    protected static $RSS_TRANSFORMATION_KEY = 'rss2markdown';
    protected static $RSS_TRANSFORMATION_VALUE = '{}';

    protected static $PDF_TRANSFORMATION_KEY = 'pdf2txt';
    protected static $PDF_TRANSFORMATION_VALUE = '{}';

    protected static $DEFAULT_FREQUENCY = 2;

    /**
     * checkBlackList
     *
     * @param string $domain the domain
     *
     * @return bool
     * @throws \Exception
     */
    public function checkBlackList($domain)
    {
        $query = '';
        try {
            $query = 'SELECT * FROM url_blacklist WHERE ';
            $query .= ' url_blacklist = :url';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':url', $domain, \PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
    }

    /**
     * evaluateTransformation
     *
     * @param string $url   the url
     * @param string $xpath the xpath expression
     *
     * @return array
     */
    protected function evaluateTransformation($url, $xpath)
    {
        if ($xpath != null && isset($xpath)) {
            $xpath = str_replace('"', '\"', str_replace('\'', '"', $xpath));
            $log = new \rpt_rpt(
                \rpt_level::L_INFO,
                'urlmodel'
            );
            $log->add('xpath ' . $xpath . ' set for url ' . $url)->end();
            return array(
                self::$DEFAULT_TRANSFORMATION_KEY,
                '{"xpath": "' . $xpath . '"}'
            );
        }
        $headers = @get_headers($url, 1);
        $content_type = isset($headers)
                && array_key_exists('Content-Type', $headers)
            ? $headers['Content-Type']
            : 'text/html'; // default

        // Simply use the first one, if multiple content types are defined.
        if (is_array($content_type)) {
            $content_type = $content_type[0];
        }
        $content_type = strtolower($content_type);

        // Possibly strip any encoding or other additional information
        $idx = strpos($content_type, ';');
        if ($idx !== false) {
            $content_type = substr($content_type, 0, $idx);
        }

        if ($content_type == 'application/pdf') {
            return array(
                self::$PDF_TRANSFORMATION_KEY,
                self::$PDF_TRANSFORMATION_VALUE
            );
        } elseif ($content_type == 'text/xml' || $content_type == 'application/rss+xml') {
            // Get the first 512 bytes of the resource.
            $content = @file_get_contents($url, false, null, 0, 512);
            if (
                isset($content) && (
                    strpos($content, '<channel>') !== false ||
                    strpos($content, '<feed>') !== false ||
                    strpos($content, '<rdf>') !== false)
            ) {
                return array(
                    self::$RSS_TRANSFORMATION_KEY,
                    self::$RSS_TRANSFORMATION_VALUE
                );
            }
        }

        // default
        return array(
            self::$DEFAULT_TRANSFORMATION_KEY,
            self::$DEFAULT_TRANSFORMATION_VALUE
        );
    }

    /**
     * addUrlGroup
     *
     * @param string $urlGroupName  the group name
     * @param int $userId           the user
     *
     * @return int $urlGroupId
     */
    protected function addUrlGroup($urlGroupName, $userId)
    {
        $urlGroupId = null;
        if ($urlGroupName == null) {
            $urlGroupName = 'My pages';
            $query = 'SELECT url_group_id FROM url_group WHERE url_group_title = :title ';
            $query .= ' AND url_group_creator_user_id = :userId';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':title', $urlGroupName, \PDO::PARAM_STR);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $urlGroupId = $res['url_group_id'];
            }
        }

        if ($urlGroupId == null) {
            $query =
                'INSERT INTO url_group(url_group_title, url_group_creator_user_id) ';
            $query .= 'VALUES (:title, :userId) RETURNING url_group_id';
            $stmt = $this->pdo->prepare($query);
            if ($urlGroupName == null) {
                $urlGroupName = 'My pages';
            }
            $stmt->bindValue(':title', $urlGroupName, \PDO::PARAM_STR);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            $urlGroupId = $stmt->fetchColumn();
        }
        //access control
        $query =
            'INSERT INTO access_control(user_id, url_group_id, access_type_id, access_control_valid_from) ';
        $query .= 'VALUES (:userId, :urlGroupId, :accessType, NOW())';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':urlGroupId', $urlGroupId, \PDO::PARAM_INT);
        $stmt->bindValue(
            ':accessType',
            PermissionHandler::ACCESS_TYPE_RW,
            \PDO::PARAM_INT
        );
        $stmt->execute();
        $userGroupId  = $this->isAdmin($userId);
        // if user is admin set read access for the whole group
        if ($userGroupId != null) {
            $query =
                'INSERT INTO access_control(user_group_id, url_group_id, access_type_id, access_control_valid_from) ';
            $query .= 'VALUES (:userGroupId, :urlGroupId, :accessType, NOW())';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':userGroupId', $userGroupId, \PDO::PARAM_INT);
            $stmt->bindValue(':urlGroupId', $urlGroupId, \PDO::PARAM_INT);
            $stmt->bindValue(
                ':accessType',
                PermissionHandler::ACCESS_TYPE_R,
                \PDO::PARAM_INT
            );
            $stmt->execute();
        }
        return $urlGroupId;
    }

    /**
     * insertUrl
     *
     * @param Url $url
     * @param     $urlGroupId
     * @param     $userId
     *
     * @return int
     */
    protected function insertUrl(Url $url, $urlGroupId, $userId) {
        if ($url->getFrequency() == null) {
            $url->setFrequency(self::$DEFAULT_FREQUENCY);
        }

        $transformation =
            $this->evaluateTransformation($url->getUrl(), $url->getXpath());
        //insert url
        $query = 'INSERT INTO url(url_title, url, url_creator_user_id, ';
        $query .= ' check_frequency_id, xfrm_id, url_group_id) ';
        $query .= 'VALUES (:title, :url, :userId, :frequency, ';
        $query .= 'get_xfrm_id(\'' . $transformation[0] . '\',\'' .
            $transformation[1] . '\'), :urlGrpId) ';
        $query .= 'RETURNING url_id';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':title', $url->getTitle(), \PDO::PARAM_STR);
        $stmt->bindValue(':url', $url->getUrl(), \PDO::PARAM_STR);
        $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':urlGrpId', $urlGroupId, \PDO::PARAM_INT);
        $stmt->bindValue(
            ':frequency',
            $url->getFrequency(),
            \PDO::PARAM_INT
        );
        $stmt->execute();
        $urlId = $stmt->fetchColumn();
        return $urlId;
    }

    /**
     * insertAccessControl
     *
     * @param $userId
     * @param $urlId
     * @param $urlGroupId
     *
     * @return void
     */
    public function insertAccessControl($userId, $urlId, $urlGroupId)
    {
        //access control
        $query =
            'INSERT INTO access_control(user_id, url_id, access_type_id, access_control_valid_from) ';
        $query .= 'VALUES (:userId, :urlId, :accessType, NOW())';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':urlId', $urlId, \PDO::PARAM_INT);
        $stmt->bindValue(
            ':accessType',
            PermissionHandler::ACCESS_TYPE_RW,
            \PDO::PARAM_INT
        );
        $stmt->execute();
        $this->addReadAccess($urlId, $urlGroupId);
        $userGroupId = $this->isAdmin($userId);
        // if user is admin set read access to the whole group
        if ($userGroupId != null) {
            $query =
                'INSERT INTO access_control(user_group_id, url_id, access_type_id, access_control_valid_from) ';
            $query .= 'VALUES (:userGroupId, :urlId, :accessType, NOW())';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':userGroupId', $userGroupId, \PDO::PARAM_INT);
            $stmt->bindValue(':urlId', $urlId, \PDO::PARAM_INT);
            $stmt->bindValue(
                ':accessType',
                PermissionHandler::ACCESS_TYPE_R,
                \PDO::PARAM_INT
            );
            $stmt->execute();
        }
    }

    /**
     * addReadAccess
     *
     * @param int $urlId      the id
     * @param int $urlGroupId the id
     *
     * @return void
     */
    private function addReadAccess($urlId, $urlGroupId)
    {
        //add read access to user which have read access to the group
        $query =
            'INSERT INTO access_control(user_id, url_id, access_type_id, access_control_valid_from) ';
        $query .= 'SELECT DISTINCT user_id, ' . $urlId . ',' .
            PermissionHandler::ACCESS_TYPE_R . ' , NOW() FROM access_control ';
        $query .= ' WHERE user_id IS NOT NULL AND url_group_id=:urlgroupid AND access_type_id=' .
            PermissionHandler::ACCESS_TYPE_R;
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':urlgroupid', $urlGroupId, \PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * check if the user is group admin
     *
     * @param int $userId the userid
     *
     * @return int
     * @throws \Exception
     */
    protected function isAdmin($userId)
    {
        $query = '';
        try {
            $query = 'SELECT user_group_id FROM mon_user WHERE user_group_id IS NOT NULL ';
            $query .= ' AND user_id = :userid AND is_group_admin ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':userid', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                return $res['user_group_id'];
            }
            return null;
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
    }

    /**
     * getNbrOfUrls
     *
     * @param $userId
     *
     * @return array
     * @throws \Exception
     */
    public function getNbrOfUrls($userId)
    {
        $count = array('plan' =>0, 'count' => 0);
        $query =
            'SELECT pricing_plan_id, count(*) AS count FROM access_control c JOIN account a ';
        $query .= ' ON (c.user_id=a.user_id) WHERE c.url_id IS NOT NULL AND c.user_id= :userid AND c.access_type_id=2 ';
        $query .= ' GROUP BY pricing_plan_id ';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':userid', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $count['plan'] = $res['pricing_plan_id'];
            $count['count'] = $res['count'];
        }
        return $count;
    }
}
