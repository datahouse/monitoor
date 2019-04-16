<?php

namespace Datahouse\MON\Alert\Get;

use Datahouse\MON\Exception\KeyNotFoundException;
use Datahouse\MON\I18\I18;
use Datahouse\MON\Model\AlertModel;
use Datahouse\MON\Types\Gen\Alert;
use Datahouse\MON\Types\Gen\AlertShaping;
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
     * @param I18 $i18 the i18
     */
    public function __construct(\PDO $pdo, I18 $i18)
    {
        $this->pdo = $pdo;
        $this->i18 = $i18;
    }

    /**
     * readAlert
     *
     * @param int $alertId the alert id
     * @param int $userId the user id
     *
     * @return Alert
     * @throws KeyNotFoundException
     * @throws \Exception
     */
    public function readAlert($alertId, $userId)
    {
        $alert = new Alert();

        $query = '';
        try {
            $query .= 'SELECT alert_id, alert_option_id, alert_threshold ';
            $query .= 'FROM alert WHERE alert_id=' . intval($alertId);
            $query .= ' AND user_id = ' . intval($userId);
            $query .= ' AND alert_active';
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $alert->setId($res['alert_id']);
                $alert->setUrlGroup($this->getUrlGroup($alertId));
                $alertShapingList = array(
                    $this->defineAlertShaping(
                        $alertId,
                        $userId,
                        $res['alert_option_id'],
                        round($res['alert_threshold'] * 100.0)
                    )
                );
                $alert->setAlertShapingList($alertShapingList);
                return $alert;
            }
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
        throw new KeyNotFoundException('no alert with id ' . $alertId);
    }

    /**
     * defines alertShaping
     *
     * @param $alertId         integer the alert id
     * @param $userId          integer the user id
     * @param $alertOptionId   integer the alert option id
     * @param $threshold       integer the threshold
     *
     * @return AlertShaping object
     */
    private function defineAlertShaping($alertId, $userId, $alertOptionId, $threshold)
    {
        $alertShaping = new AlertShaping();

        //keywords
        $query =
            'SELECT alert_keyword FROM alert_keyword k ';
        $query .= 'JOIN alert a ';
        $query .= 'ON (a.alert_id = k.alert_id) WHERE a.alert_id=' .
            intval($alertId);
        $query .= ' AND a.user_id=' . intval($userId);
        $query .= ' AND k.alert_keyword_active';
        $keywords = array();
        foreach ($this->pdo->query($query) as $res) {
            $keywords[] = $res['alert_keyword'];
        }
        $alertShaping->setKeywords($keywords);
        $alertShaping->setAlertThreshold($threshold);

        $option = new AlertOption();
        $option->setId($alertOptionId);
        $alertShaping->setAlertOption($option);

        //alert types
        $query =
            'SELECT tc.alert_type_id, tc.alert_cycle_id ';
        $query .= 'FROM alert_x_type_cycle a JOIN type_x_cycle tc ';
        $query .= 'ON (a.type_x_cycle_id=tc.type_x_cycle_id)';
        $query .= ' WHERE a.alert_id = ' . intval($alertId);
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $alertType = array();
        if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $alertType['id'] = $res['alert_type_id'];
            $alertType['cycleId'] = $res['alert_cycle_id'];
        }
        $alertShaping->setAlertType($alertType);

        return $alertShaping;
    }

    /**
     * getUrl
     *
     * @param int $alertId the alert id
     *
     * @return array
     * @throws KeyNotFoundException
     */
    private function getUrlGroup($alertId)
    {
        $query = 'SELECT u.url_group_id, u.url_group_title ';
        $query .= 'FROM alert_x_url_group x JOIN url_group u ';
        $query .= ' ON (x.url_group_id = u.url_group_id) ';
        $query .= ' WHERE x.alert_id=' . intval($alertId);
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $urlGroup = array(
                'id' => $res['url_group_id'],
                'title' => $res['url_group_title']
            );
            return $urlGroup;
        }
        return null;
    }
}
