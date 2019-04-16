<?php

namespace Datahouse\MON\Urlgroup\Add;

use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\Types\Gen\UrlGroup;

/**
 * Class Model
 *
 * @package Url
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Model extends \Datahouse\Framework\Model
{

    private $pdo;

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
     * @param UrlGroup $urlGroup the url group
     * @param int      $userId   the user
     *
     * @return UrlGroup
     * @throws \Exception
     */
    public function createUrlGroup(UrlGroup $urlGroup, $userId)
    {
        $query = '';
        try {
            $this->pdo->beginTransaction();
            //insert url
            $query .= 'INSERT INTO url_group(url_group_title, url_group_creator_user_id) ';
            $query .= 'VALUES (:title, :userId) RETURNING url_group_id';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':title', $urlGroup->getTitle(), \PDO::PARAM_STR);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            $urlGroupId = $stmt->fetchColumn();
            //access control
            $query = 'INSERT INTO access_control(user_id, url_group_id, access_type_id, access_control_valid_from) ';
            $query .= 'VALUES (:userId, :urlGroupId, :accessType, NOW())';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':urlGroupId', $urlGroupId, \PDO::PARAM_INT);
            $stmt->bindValue(':accessType', PermissionHandler::ACCESS_TYPE_RW, \PDO::PARAM_INT);
            $stmt->execute();
            //group admin check
            $userGroupId = null;
            $query =
                'SELECT user_group_id FROM mon_user WHERE user_id = :userid ';
            $query .= ' AND user_group_id IS NOT NULL AND is_group_admin ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':userid', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $userGroupId = $res['user_group_id'];
            }
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
            $this->pdo->commit();
            $urlGroup->setId($urlGroupId);
            return $urlGroup;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }
}
