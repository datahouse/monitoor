<?php

namespace Datahouse\MON\Widget\Request;

use Datahouse\MON\Model\UserModel;
use Datahouse\MON\Types\UserHash;

/**
 * Class Model
 *
 * @package     Widget
 * @author      Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Model extends UserModel
{
    private static $WIDGET_PRICE_PLAN = 4;

    /**
     * addWidgetToUser
     *
     * @param int    $urlGroupId the id
     * @param string $email      the email
     *
     * @return UserHash
     * @throws \Exception
     */
    public function addWidgetToUser($urlGroupId, $email)
    {
        try {
            $userHash = new UserHash();
            $this->pdo->beginTransaction();
            //check if user exists
            $userId = $this->isExistingUser($email);
            //if not - create one and activate
            if ($userId == null) {
                $userId = $this->createUser($email, self::$WIDGET_PRICE_PLAN);
                $hash = $this->createActivationHash($userId);
                $userHash->setHash($hash);
            }
            //subscribe to urlGroup
            $this->addWidgetGroup($urlGroupId, $userId);
            $this->pdo->commit();
            $userHash->setUserId($userId);
            return $userHash;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e);
        }
    }

    /**
     * addWidgetGroup
     *
     * @param int $urlGroupId the url group
     * @param int $userId     the user
     *
     * @return bool
     * @throws \Exception
     */
    private function addWidgetGroup($urlGroupId, $userId)
    {
        $query = 'SELECT user_id FROM access_control WHERE user_id=:userid AND ';
        $query .= ' url_group_id = :urlgroupid AND access_control_valid_till IS NULL ';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':urlgroupid', $urlGroupId, \PDO::PARAM_INT);
        $stmt->bindValue(':userid', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return true;
        }
        $query = 'SELECT subscribe(' . $urlGroupId . ',' . $userId .
            ',2)';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return true;
    }

    /**
     * isAllowedUrlGroup
     *
     * @param int $urlGroupId the group id
     *
     * @return bool
     * @throws \Exception
     */
    public function isAllowedUrlGroup($urlGroupId)
    {
        $query = '';
        try {
            $query = 'SELECT url_group_id FROM url_group WHERE ';
            $query .= ' url_group_id = :url_group_id AND is_widget ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':url_group_id', $urlGroupId, \PDO::PARAM_INT);
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                return true;
            }
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
        return false;
    }
}
