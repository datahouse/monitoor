<?php

namespace Datahouse\MON\Alert\Update;

use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Model\AlertModel;
use Datahouse\MON\Types\Gen\Alert;
use Datahouse\MON\Types\Gen\AlertOption;

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
     * updateAlert
     *
     * @param Alert $alert  the alert
     * @param int   $userId the alert
     *
     * @return bool
     * @throws \Exception
     */
    public function updateAlert(Alert $alert, $userId)
    {
        $query = '';
        try {
            $this->pdo->beginTransaction();
            $alertShapingList = $alert->getAlertShapingList();
            foreach ($alertShapingList as $alertShaping) {
                //insert keywords
                $this->insertKeywords(
                    $alertShaping->getKeywords(),
                    $alert->getId(),
                    $userId
                );
                //insert type
                $this->deleteAlertTypes($alert->getId());
                $this->insertAlertType($alert->getId(), $alertShaping->getAlertType());
                //insert option
                $this->udpateAlert(
                    $alert->getId(),
                    $alertShaping->getAlertOption(),
                    $alertShaping->getAlertThreshold()
                );
                //TODO at the moment only one alert shaping possible in the DB
                break;
            }

            //url/group
            $query = 'DELETE FROM alert_x_url_group WHERE alert_id = :alertId ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':alertId', $alert->getId(), \PDO::PARAM_INT);
            $stmt->execute();
            $this->insertAlertXUrlGroup($alert->getId(), $alert->getUrlGroup());

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }

    /**
     * udpateAlert
     *
     * @param $alertId     int         alertId
     * @param $alertOption AlertOption alertOption
     * @param $threshold   int         threshold
     *
     * @return void
     */
    private function udpateAlert(
        $alertId,
        AlertOption $alertOption,
        $threshold
    ) {
        $query = 'UPDATE alert SET alert_option_id = :alertOptionId, ';
        $query .= 'alert_threshold = :threshold WHERE alert_id = :alertId ';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(
            ':alertOptionId',
            $alertOption->getId(),
            \PDO::PARAM_INT
        );
        $stmt->bindValue(':alertId', $alertId, \PDO::PARAM_INT);
        $stmt->bindValue(':threshold', $threshold / 100.0);
        $stmt->execute();
        return;
    }

}
