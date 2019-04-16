<?php

namespace Datahouse\MON\Alert\Delete;

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

    /**
     * @param \PDO $pdo the pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * deleteAlert
     *
     * @param int $alertId the alert id
     * @param int $userId  the user id
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteAlert($alertId, $userId)
    {

        $query = '';
        try {
            $this->pdo->beginTransaction();

            $query = 'UPDATE alert SET alert_active = false WHERE alert_id = :alertId';
            $query .= ' AND user_id = :userId';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':alertId', $alertId, \PDO::PARAM_INT);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->execute();

            $query = 'UPDATE alert_keyword SET alert_keyword_active = false WHERE alert_id = :alertId;';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':alertId', $alertId, \PDO::PARAM_INT);
            $stmt->execute();

            $query = 'DELETE FROM alert_x_url_group WHERE alert_id = :alertId;';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':alertId', $alertId, \PDO::PARAM_INT);
            $stmt->execute();

            $query = 'DELETE FROM alert_x_type_cycle WHERE alert_id = :alertId;';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':alertId', $alertId, \PDO::PARAM_INT);
            $stmt->execute();

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }
}
