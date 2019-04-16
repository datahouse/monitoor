<?php

namespace Datahouse\MON\Urlgroup\Subscriptions;

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
     * readSubscriptionList
     *
     * @param int $userId the userid
     *
     * @return UrlGroupList
     * @throws \Exception
     */
    public function readSubscriptionList($userId) {
        $urlListResponse = new UrlGroupList();
        $query = '';
        try {
            $query = 'SELECT pricing_plan_id FROM account WHERE user_id=:userId';
            $query .= ' AND pricing_plan_id in (2, 3)';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            $isFree = true;
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $isFree = false;
            }
            $query = 'SELECT g.url_group_id, g.url_group_title,  g.url_group_description, g.billable_only,';
            $query .= ' false as subscription, u.url_id, u.url_title, u.url, false AS url_subscription, g.subscription_price ';
            $query .= ' FROM url_group g JOIN url u ON (u.url_group_id = g.url_group_id) ';
            $query .= ' WHERE g.is_subscription AND u.url_active ';
            $query .= ' AND NOT EXISTS (SELECT user_id FROM access_control a ';
            $query .= ' WHERE user_id =:userId AND g.url_group_id = a.url_group_id) ';
            $query .= ' UNION ALL ';
            $query .= ' SELECT g.url_group_id, g.url_group_title, g.url_group_description, g.billable_only,';
            $query .= ' true as subscription , u.url_id, u.url_title, u.url, true AS url_subscription, g.subscription_price  ';
            $query .= ' FROM url_group g JOIN url u ON (u.url_group_id = g.url_group_id) ';
            $query .= ' WHERE g.is_subscription AND u.url_active ';
            $query .= ' AND EXISTS (SELECT user_id from access_control a ';
            $query .= ' WHERE user_id = :userId AND g.url_group_id = a.url_group_id) ';
            $query .= ' AND EXISTS (SELECT user_id from access_control a1 ';
            $query .= ' WHERE user_id = :userId AND u.url_id = a1.url_id) ';
            $query .= 'UNION ALL  ';
            $query .= 'SELECT g.url_group_id, g.url_group_title, g.url_group_description, g.billable_only, ';
            $query .= 'true as subscription , u.url_id, u.url_title, u.url, false AS url_subscription, g.subscription_price ';
            $query .= 'FROM url_group g JOIN url u ON (u.url_group_id = g.url_group_id) ';
            $query .= 'WHERE g.is_subscription AND u.url_active  AND EXISTS (SELECT user_id from access_control a ';
            $query .= 'WHERE user_id = :userId AND g.url_group_id = a.url_group_id)  AND NOT EXISTS ';
            $query .= '(SELECT user_id from access_control a1  WHERE user_id = :userId AND u.url_id = a1.url_id) ';
            $query .= ' ORDER BY url_group_title, url_group_id, url, url_id ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            $urlGroups = $this->getUrlGroups($stmt->fetchAll(), $userId, $isFree);
            $urlListResponse->setUrlGroupItems($urlGroups);
            return $urlListResponse;
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
    }

    /**
     * getUrlGroups
     *
     * @param array $res    the results
     * @param int   $userId the userid
     * @param bool  $isFree the flag if the user is paying for using the moonitoor
     *
     * @return UrlGroup
     */
    private function getUrlGroups(array $res, $userId, $isFree)
    {
        $urlGroups = array();
        $urlGroupId = null;
        $urlGroup = null;
        $urls = array();
        foreach ($res as $row) {
            $monitoorFee =  $row['billable_only'];
            if (($monitoorFee && !$isFree) || !$monitoorFee) {
                if ($urlGroupId != $row['url_group_id']) {
                    if ($urlGroup != null) {
                        $urlGroup->setUrls($urls);
                        $urlGroups[] = $urlGroup;
                    }
                    $urlGroup = new UrlGroup();
                    $urls = array();
                    $urlGroup->setId($row['url_group_id']);
                    $urlGroup->setTitle($row['url_group_title']);
                    $urlGroup->setDescription($row['url_group_description']);
                    $urlGroup->setSubscribed($row['subscription']);
                    $urlGroup->setPrice($row['subscription_price']);
                    try {
                        $this->permissionHandler->hasUrlGroupWriteAccess(
                            $userId,
                            $row['url_group_id']
                        );
                        $urlGroup->setReadOnly(false);
                    } catch (PermissionException $pe) {
                        $urlGroup->setReadOnly(true);
                    }
                }
                if ($row['url_id'] != null) {
                    $url = new Url();
                    $url->setExternal((0 === strpos($row['url'], 'external')));
                    $url->setId($row['url_id']);
                    $url->setTitle($row['url_title']);
                    $url->setUrl($row['url']);
                    $url->setSubscribed($row['url_subscription']);
                    try {
                        $this->permissionHandler->hasUrlWriteAccess(
                            $userId,
                            $row['url_id']
                        );
                        $url->setReadOnly(false);
                    } catch (PermissionException $pe) {
                        $url->setReadOnly(true);
                    }
                    $urls[] = $url;
                }
                $urlGroupId = $row['url_group_id'];
            }
        }
        $urlGroup->setUrls($urls);
        $urlGroups[] = $urlGroup;
        return $urlGroups;
    }
}
