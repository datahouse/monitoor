<?php

namespace Datahouse\MON\Permission;

use Datahouse\MON\Exception\PermissionException;

/**
 * Class PermissionHandler
 *
 * @package Permission
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class PermissionHandler
{

    const ACCESS_TYPE_R = 1;
    const ACCESS_TYPE_RW = 2;
    private $urlGroup = 'url_group_id';
    private $url = 'url_id';

    private $pdo;

    /**
     * @param \PDO $pdo the pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * assertRole
     *
     * @param int $userId the user id
     * @param int $roleId the role id
     *
     * @return bool
     * @throws PermissionException
     */
    public function assertRole($userId, $roleId)
    {
        if ($userId != null) {
            return true;
        }
        throw new PermissionException(
            'user ' . $userId . ' has no permission for role ' . $roleId
        );
    }

    /**
     * hasUrlReadAccess
     *
     * @param int $userId the user id
     * @param int $urlId  the url id
     *
     * @return bool
     * @throws PermissionException
     */
    public function hasUrlReadAccess($userId, $urlId)
    {
        return $this->hasUrlAccess($userId, $urlId, self::ACCESS_TYPE_R, $this->url);
    }

    /**
     * hasUrlWriteAccess
     *
     * @param int $userId the user id
     * @param int $urlId  the url id
     *
     * @return bool
     * @throws PermissionException
     */
    public function hasUrlWriteAccess($userId, $urlId)
    {
        return $this->hasUrlAccess($userId, $urlId, self::ACCESS_TYPE_RW, $this->url);
    }

    /**
     * checks if user has access to alert
     *
     * @param int $userId  the user id
     * @param int $alertId the alert id
     *
     * @return bool if user has access to alert
     * @throws PermissionException
     */
    public function hasAlertAccess($userId, $alertId)
    {
        $query = 'SELECT * FROM alert WHERE ';
        $query .= ' user_id = :userId AND alert_id = :alertId AND ';
        $query .= ' alert_active = true;';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':alertId', $alertId, \PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            return true;
        }
        throw new PermissionException(
            'user ' . $userId . ' has no permission for alert ' . $alertId
        );
    }

    /**
     * hasUrlgroupWriteAccess
     *
     * @param int $userId     the user id
     * @param int $urlGroupId the url group id
     *
     * @return bool
     * @throws PermissionException
     */
    public function hasUrlGroupWriteAccess($userId, $urlGroupId)
    {
        return $this->hasUrlAccess(
            $userId,
            $urlGroupId,
            self::ACCESS_TYPE_RW,
            $this->urlGroup
        );
    }

    /**
     * hasUrlGroupReadAccess
     *
     * @param int $userId     the user id
     * @param int $urlGroupId the url group id
     *
     * @return bool
     * @throws PermissionException
     */
    public function hasUrlGroupReadAccess($userId, $urlGroupId)
    {
        return $this->hasUrlAccess(
            $userId,
            $urlGroupId,
            self::ACCESS_TYPE_R,
            $this->urlGroup
        );
    }

    /**
     * hasUrlAccess
     *
     * @param int    $userId     the user id
     * @param int    $urlId      the url id
     * @param int    $accessType the access type
     * @param string $urlField   the url DB field (group or not)
     *
     * @return bool
     * @throws PermissionException
     */
    private function hasUrlAccess($userId, $urlId, $accessType, $urlField)
    {
        $query = 'SELECT * FROM access_control WHERE ';
        $query .= ' (user_id = :userId OR user_group_id = (SELECT user_group_id ';
        $query .= ' FROM mon_user WHERE user_id = :userId AND user_group_id IS NOT NULL)) ';
        $query .= ' and ' . $urlField . ' = :url AND access_type_id >= :accessType ';
        $query .= ' AND (access_control_valid_till IS NULL ';
        $query .= ' or access_control_valid_till > NOW()) ';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':url', $urlId, \PDO::PARAM_INT);
        $stmt->bindValue(':accessType', $accessType, \PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            return true;
        }
        throw new PermissionException(
            'user ' . $userId . ' has no permission for url ' . $urlId
        );
    }

    /**
     * isValidUserId
     *
     * @param int $userId the userid
     *
     * @return bool
     * @throws PermissionException
     */
    public function isValidUserId($userId) {
        $query = 'SELECT user_id FROM mon_user WHERE user_activated ';
        $query .= ' AND (user_valid_till IS NULL OR user_valid_till > NOW()) ';
        $query .= ' AND user_id=:userId';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return true;
        }
        throw new PermissionException('userid not valid ' . $userId);
    }
}
