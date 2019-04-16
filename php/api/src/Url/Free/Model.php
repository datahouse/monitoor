<?php

namespace Datahouse\MON\Url\Free;

use Datahouse\MON\Exception\UrlsExceededException;
use Datahouse\MON\Model\UrlModel;
use Datahouse\MON\Types\Gen\Url;
use Datahouse\MON\Types\UserHash;

/**
 * Class Model
 *
 * @package     Widget
 * @author      Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Model extends UrlModel
{
    private static $FREE_PRICE_PLAN = 1;


    /**
     * addWidgetToUser
     *
     * @param Url    $url   url
     * @param string $email the email
     *
     * @return UserHash
     * @throws \Exception
     */
    public function addFreeUrl(Url $url, $email)
    {
        try {
            $userHash = new UserHash();
            $this->pdo->beginTransaction();
            //check if user exists
            $userId = $this->isExistingUser($email);
            //if not - create one and activate
            if ($userId == null) {
                $userId = $this->createUser($email, self::$FREE_PRICE_PLAN);
                $hash = $this->createActivationHash($userId);
                $userHash->setHash($hash);
            }
            //add url
            $count = $this->getNbrOfUrls($userId);
            if ($count['count'] >= 10 && $count['plan'] == 1) {
                throw new UrlsExceededException();
            }
            $urlGroupId = $this->addUrlGroup(null, $userId);
            $urlId = $this->insertUrl($url, $urlGroupId, $userId);
            $this->insertAccessControl($userId, $urlId, $urlGroupId);
            $this->addAlert($userId, $urlGroupId);
            $this->pdo->commit();
            $userHash->setUserId($userId);
            return $userHash;
        } catch (UrlsExceededException $e) {
            $this->pdo->rollBack();
            throw new UrlsExceededException($e);
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e);
        }
    }

    private function addAlert($userId, $urlGroupId) {

        $query = "SELECT a.alert_id FROM alert a, alert_x_url_group b WHERE a.alert_id = b.alert_id ";
        $query .= "AND alert_active AND user_id=:userid AND url_group_id=:groupid";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':userid', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':groupid', $urlGroupId, \PDO::PARAM_INT);
        $stmt->execute();
        //alert urlgroup  exists
        if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return;
        }
        $query =
            'INSERT INTO alert(user_id, alert_option_id) VALUES (:userid, 1) returning alert_id';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':userid', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        $alertId = $stmt->fetchColumn();
        $query =
            'INSERT INTO alert_x_url_group(alert_id, url_group_id) VALUES (:alertid, :groupid)';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':alertid', $alertId, \PDO::PARAM_INT);
        $stmt->bindValue(':groupid', $urlGroupId, \PDO::PARAM_INT);
        $stmt->execute();
        $query =
            'INSERT INTO alert_x_type_cycle (type_x_cycle_id, alert_id) VALUES (3, :alertid)';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':alertid', $alertId, \PDO::PARAM_INT);
        $stmt->execute();
    }
}
