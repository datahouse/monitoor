<?php

namespace Datahouse\MON\Urlgroup\Listing;

use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\Types\Gen\Url;
use Datahouse\MON\Types\Gen\UrlGroup;
use Datahouse\MON\Types\Gen\UrlGroupList;

/**
 * Class Model
 *
 * @package Alert
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Model extends \Datahouse\Framework\Model
{

    private $pdo;
    protected $permissionHandler;

    /**
     * @param \PDO              $pdo               the pdo
     * @param PermissionHandler $permissionHandler the permissionHandler
     */
    public function __construct(\PDO $pdo, PermissionHandler $permissionHandler)
    {
        $this->pdo = $pdo;
        $this->permissionHandler = $permissionHandler;
    }

    /**
     * readUrlgroupList
     *
     * @param int      $offset   the offset
     * @param int      $size     the size
     * @param string   $sorting  the sorting string
     * @param UrlGroup $urlGroup the url filter
     * @param int      $userId   the user id
     *
     * @return UrlGroupList
     * @throws \Exception
     */
    public function readUrlGroupList(
        $offset,
        $size,
        $sorting,
        UrlGroup $urlGroup,
        $userId
    ) {
        $urlListResponse = new UrlGroupList();

        $fromString = ' FROM url_group LEFT JOIN url u ON ';
        $fromString .= ' (url_group.url_group_id = u.url_group_id) WHERE (EXISTS ';
        $fromString .= ' (SELECT url_group_id FROM ACCESS_CONTROL a WHERE user_id = :userId';
        $fromString .= ' AND url_group.url_group_id = a.url_group_id) ';
        $fromString .= ' OR EXISTS (SELECT url_group_id FROM ACCESS_CONTROL a ';
        $fromString .= ' WHERE url_group.url_group_id = a.url_group_id AND ';
        $fromString .= ' user_group_id = (SELECT user_group_id FROM mon_user ';
        $fromString .= ' WHERE user_id=:userId AND user_group_id IS NOT NULL))) ';
        $fromString .= ' AND EXISTS (select url_id FROM access_control acc WHERE u.url_id=acc.url_id AND acc.user_id=:userId) ';
        $fromString .= ' AND (u.url_active OR u.url_active IS NULL) ';
        $bindParams = $this->createFilter($urlGroup, $whereFilter);
        $bindParams[':userId'] =
            [$userId, \PDO::PARAM_INT];
        $query = '';
        try {
            $urlGroups = array();
            $query .= 'SELECT count(distinct url_group.url_group_id) as count ' . $fromString . $whereFilter;
            $count = 0;
            $stmt = $this->pdo->prepare($query);
            foreach ($bindParams as $bindParam => $value) {
                $stmt->bindValue($bindParam, $value[0], $value[1]);
            }
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $count = $res['count'];
            }
            if ($count > 0) {
                $query = 'SELECT url_group.url_group_id, url_group_title, ';
                $query .= ' is_subscription, u.url_id, u.url_title, u.url ';
                $query .= $fromString . $whereFilter;
                $query .= $this->getOrderBy($sorting) .
                    $this->createLimit($offset, $size);
                $stmt = $this->pdo->prepare($query);
                foreach ($bindParams as $bindParam => $value) {
                    $stmt->bindValue($bindParam, $value[0], $value[1]);
                }
                $stmt->execute();
                $urlGroups = $this->getUrlGroups($stmt->fetchAll(), $userId);
            }
            $urlListResponse->count = $count;
            $urlListResponse->setUrlgroupItems($urlGroups);
            return $urlListResponse;
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
    }

    /**
     * createLimit
     *
     * @param int $offset the offset
     * @param int $size   the size
     *
     * @return string
     */
    private function createLimit($offset, $size)
    {
        $off = ' OFFSET ' . intval($offset);
        $limit = '';
        if ($size != null) {
            $limit = ' LIMIT ' . $size;
        }
        return $off . $limit;
    }

    /**
     * getOrderBy
     *
     * @param string $sorting the sorting string
     *
     * @return string
     */
    private function getOrderBy($sorting)
    {
        $orderBy = '';
        //sorting
        $sort = explode(',', $sorting);
        $orderBy .= ' ORDER BY ';
        if ($sorting != null && strlen($sorting) > 0) {
            foreach ($sort as $sortOrder) {
                $order = strtolower(str_replace('-', '', $sortOrder));
                switch ($order) {
                    case 'title':
                        $orderBy .= ' url_group_title ';
                        $orderBy .= $this->getSortOrder($sortOrder);
                        break;
                }
            }
        }
        return $orderBy . 'url_group_title, url_title';
    }

    /**
     * getSortOrder
     *
     * @param string $value the value
     *
     * @return string
     */
    private function getSortOrder($value)
    {
        if (strpos($value, '-') !== false) {
            return ' DESC,';
        }
        return ' ASC,';
    }

    /**
     * createFilter
     *
     * @param Urlgroup $urlGroup    the url group
     * @param string   $wherefilter the where string
     *
     * @return array
     */
    private function createFilter(Urlgroup $urlGroup, &$wherefilter)
    {
        $bindParams = array();
        if ($urlGroup != null) {
            if ($this->checkString($urlGroup->getTitle()) != null) {
                $wherefilter .= ' AND url_group_title ILIKE :titel ';
                $bindParams[':titel'] =
                    ['%' . $urlGroup->getTitle() . '%', \PDO::PARAM_STR];
            }
        }
        return $bindParams;
    }

    /**
     * checkString
     *
     * @param string $value the value
     *
     * @return string
     */
    private function checkString($value)
    {
        if ($value != null && strlen($value) > 0) {
            return $value;
        }
        return null;
    }

    /**
     * getUrlGroups
     *
     * @param array $res    the results
     * @param int   $userId the userid
     *
     * @return UrlGroup
     */
    private function getUrlGroups(array $res, $userId)
    {
        $urlGroups = array();
        $urlGroupId = null;
        $urlGroup = null;
        $urls = array();
        foreach ($res as $row) {
            if ($urlGroupId != $row['url_group_id']) {
                if ($urlGroup != null) {
                    $urlGroup->setUrls($urls);
                    $urlGroups[] = $urlGroup;
                }
                $urlGroup = new UrlGroup();
                $urls = array();
                $urlGroup->setId($row['url_group_id']);
                $urlGroup->setTitle($row['url_group_title']);
                $urlGroup->setAlertId($this->getAlertId($urlGroup->getId(), $userId));
                try {
                    $this->permissionHandler->hasUrlGroupWriteAccess($userId, $row['url_group_id']);
                    $urlGroup->setReadOnly(false);
                } catch (PermissionException $pe) {
                    $urlGroup->setReadOnly(true);
                }
                $urlGroup->setSubscribed($row['is_subscription']);
            }
            if ($row['url_id'] != null) {
                $url = new Url();
                $url->setExternal((0 === strpos($row['url'], 'external')));
                $url->setId($row['url_id']);
                $url->setTitle($row['url_title']);
                $url->setUrl($row['url']);

                try {
                    $this->permissionHandler->hasUrlWriteAccess($userId, $row['url_id']);
                    $url->setReadOnly(false);
                } catch (PermissionException $pe) {
                    $url->setReadOnly(true);
                }
                $urls[] = $url;
            }
            $urlGroupId = $row['url_group_id'];
        }
        $urlGroup->setUrls($urls);
        $urlGroups[] = $urlGroup;
        return $urlGroups;
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
        $query .= ' AND a.user_id = ' . intval($userId) . ' AND alert_active';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $res['alert_id'];
        }
        return null;
    }
}
