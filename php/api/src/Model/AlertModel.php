<?php

namespace Datahouse\MON\Model;

use Datahouse\Framework\Model;
use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Types\Gen\AlertOption;
use Datahouse\MON\Types\Gen\AlertType;

/**
 * Class AlertTypeModel
 *
 * @package Alert
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
abstract class AlertModel extends Model
{

    /**
     * @var \PDO
     */
    protected $pdo;
    protected $i18;

    /**
     * getAlertTypes
     *
     * @param int $langCode the langcode
     * @param int $userId   the user
     *
     * @return array
     */
    protected function getAlertTypes($langCode, $userId)
    {
        $hasPush = $this->hasUserPushActive($userId);
        $query =
            'SELECT t.alert_type_id, t.alert_type_name, c.alert_cycle_id, ';
        $query .= 'c.alert_cycle_name FROM type_x_cycle tc ';
        $query .= 'JOIN alert_type t ON (t.alert_type_id=tc.alert_type_id) ';
        $query .= 'JOIN alert_cycle c ON (tc.alert_cycle_id=c.alert_cycle_id) ';
        $query .= 'WHERE tc.is_active ';
        if (!$hasPush) {
            $query .= ' AND t.alert_type_id <> 4 ';
        }
        $query .= ' ORDER BY t.sort_order, c.alert_cycle_id';
        $alertType = null;
        $alertTypeIdLast = null;
        $alertTypes = array();
        $cycles = array();
        foreach ($this->pdo->query($query) as $res) {
            $alertTypeId = $res['alert_type_id'];
            if ($alertTypeId != $alertTypeIdLast) {
                if ($alertType != null) {
                    $alertType->setCycle($cycles);
                    $alertTypes[] = $alertType;
                    $cycles = array();
                }
                $alertType = new AlertType();
                $alertType->setId($alertTypeId);
                $alertType->setTitle(
                    $this->i18->translate(
                        'alert_type_' . $res['alert_type_id'],
                        $langCode
                    )
                );
            }
            $cycles[] = array(
                'id' => $res['alert_cycle_id'],
                'title' => $this->i18->translate(
                    'alert_cycle_' . $res['alert_cycle_id'],
                    $langCode
                ),
                'selected' => false
            );
            $alertTypeIdLast = $alertTypeId;
        }
        if ($alertType != null) {
            $alertType->setCycle($cycles);
            $alertTypes[] = $alertType;
        }
        return $alertTypes;
    }

    /**
     * hasUserPushActive
     *
     * @param $userId
     *
     * @return bool
     * @throws \Exception
     */
    protected function hasUserPushActive($userId)
    {
        $query = '';
        try {
            $query .= 'SELECT user_id FROM push_token WHERE ';
            $query .= 'user_id = :userId  AND denied = false ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(
                ':userId',
                $userId,
                \PDO::PARAM_INT
            );
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
    }

    /**
     * insertKeywords
     *
     * @param array $keywords the keywords
     * @param int   $alertId  the alert id
     * @param int   $userId   the user id
     *
     * @return void
     */
    protected function insertKeywords(array $keywords, $alertId, $userId)
    {
        $fn = function($v) {
            return $this->pdo->quote($v);
        };
        $keywords_esc = array_map($fn, $keywords);
        $query = 'SELECT update_keywords_for_alert(';
        $query .= intval($alertId) . ', ' . intval($userId) . ', ';
        $query .= "ARRAY[" . implode(", ", $keywords_esc) . "]::TEXT[]";
        $query .= ");";
        $this->pdo->exec($query);
    }

    /**
     * insertAlarmTypes
     *
     * @param int   $alertId    the alert id
     * @param mixed $alertTypes the types
     *
     * @return void
     */
    protected function insertAlertType($alertId, AlertType $alertType)
    {
        //insert types
        $typeCycleIds = array();
        $query = 'SELECT type_x_cycle_id FROM type_x_cycle ';
        $query .= 'WHERE alert_type_id = :alertType ';
        $query .= 'AND alert_cycle_id = :cycleType ';
        $stmt = $this->pdo->prepare($query);
        $type = $alertType->getId();
        $cycle = $alertType->getCycle();
        $stmt->bindValue(':alertType', $type, \PDO::PARAM_INT);
        $stmt->bindValue(':cycleType', $cycle, \PDO::PARAM_INT);
        $stmt->execute();
        $typeCycleIds[] = $stmt->fetchColumn();
        $query =
            'INSERT INTO alert_x_type_cycle (type_x_cycle_id, alert_id) ';
        $query .= 'VALUES (:typeCycle, :alertId) ';
        $stmt = $this->pdo->prepare($query);
        $typeCycleIds = array_unique($typeCycleIds);
        foreach ($typeCycleIds as $typeCycleId) {
            $stmt->bindValue(':typeCycle', $typeCycleId, \PDO::PARAM_INT);
            $stmt->bindValue(':alertId', $alertId, \PDO::PARAM_INT);
            $stmt->execute();
            return;
        }
    }

    /**
     * deleteAlertTypes
     *
     * remove all entries in alert_x_type_cycle where alertId is matching
     *
     * @param $alertId int the alert id
     * @return void
     */
    protected function deleteAlertTypes($alertId)
    {
        $query = 'DELETE FROM alert_x_type_cycle';
        $query .= ' WHERE alert_id = :alertId';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':alertId', $alertId, \PDO::PARAM_INT);
        $stmt->execute();
        return;
    }

    /**
     * insertUrl
     *
     * @param int   $alertId  the alert id
     * @param array $urlGroup the url array
     *
     * @return void
     * @throws ValidationException
     */
    protected function insertAlertXUrlGroup($alertId, $urlGroup)
    {
        //insert alert_url_group
        $query = 'INSERT INTO alert_x_url_group(alert_id, url_group_id) ';
        $query .= 'VALUES (:alertId, :urlGroupId)';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':alertId', $alertId, \PDO::PARAM_INT);
        $stmt->bindValue(
            ':urlGroupId',
            $urlGroup['id'],
            \PDO::PARAM_INT
        );
        $stmt->execute();
        return;
    }
}
