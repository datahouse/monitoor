<?php

namespace Datahouse\MON\Alert\Add;

use Datahouse\MON\Model\AlertModel;
use Datahouse\MON\Types\Gen\Alert;

/**
 * Class Model
 *
 * @package Alert
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Model extends AlertModel
{

    /**
     * @param \PDO $pdo the pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * createAlert
     *
     * @param Alert $alert  the alert
     * @param int   $userId the user
     *
     * @return string
     * @throws \Exception
     */
    public function createAlert(Alert $alert, $userId)
    {
        $query = '';
        try {
            $this->pdo->beginTransaction();

            //insert alert
            $query =
                'INSERT INTO alert(user_id, alert_option_id, alert_threshold) ';
            $query .= 'VALUES (:userId, :alertOptionId, :threshold) RETURNING alert_id';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            //TODO at the moment only one alert shaping possible in the DB
            $stmt->bindValue(':alertOptionId', $alert->getAlertShapingList()[0]->getAlertOption()->getId(), \PDO::PARAM_INT);
            $stmt->bindValue(
                ':threshold',
                // convert to a ratio between 0 and 1
                $alert->getAlertShapingList()[0]->getAlertThreshold()
                    / 100.0
            );
            $stmt->execute();
            $alertId = $stmt->fetchColumn();

            $this->insertAlertXUrlGroup($alertId, $alert->getUrlGroup());

            $alertShapingList = $alert->getAlertShapingList();
            foreach ($alertShapingList as $alertShaping) {
                //insert keywords
                $this->insertKeywords(
                    $alertShaping->getKeywords(),
                    $alertId,
                    $userId
                );
                //insert type
                $this->insertAlertType($alertId, $alertShaping->getAlertType());
                //TODO at the moment only one alert shaping possible in the DB
                $this->pdo->commit();
                return $alertId;
            }
            throw new \Exception('missing alert shaping');
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }

    /**
     * checkForExistingAlert
     *
     * @param int $urlGroupId the url group id
     * @param int $userId     the user id
     *
     * @return bool
     * @throws \Exception
     */
    public function checkForExistingAlert($urlGroupId, $userId)
    {
        $query = '';
        try {
            $query =
                'SELECT x.alert_id FROM alert_x_url_group AS x ';
            $query .= 'JOIN alert a ON (x.alert_id = a.alert_id) ';
            $query .= 'WHERE url_group_id= :urlGroupId ';
            $query .= 'AND a.user_id = :userId AND a.alert_active ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':urlGroupId', $urlGroupId, \PDO::PARAM_INT);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            $stmt->execute();
            if ($stmt->fetch()) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }
}
