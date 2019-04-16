<?php

namespace Datahouse\MON\User\Pushtoken;

use Datahouse\MON\Types\PushToken;

/**
 * Class Model
 *
 * @package     User
 * @author      Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Model extends \Datahouse\Framework\Model
{

    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @param \PDO $pdo the pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * updateUser
     *
     * @param PushToken $pushtoken the pushtoken
     *
     * @return bool
     * @throws \Exception
     */
    public function handlePushToken(PushToken $pushtoken)
    {
        $query = '';
        try {
            $this->pdo->beginTransaction();
            $query .= 'SELECT user_id FROM push_token WHERE ';
            $query .= 'user_id = :userId  AND token = :token';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(
                ':userId',
                $pushtoken->getUserId(),
                \PDO::PARAM_INT
            );
            $stmt->bindValue(':token', $pushtoken->getToken(), \PDO::PARAM_STR);
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $query =
                    'UPDATE push_token SET denied = :denied, ts = NOW() ';
                $query .= ' WHERE user_id = :userId AND token = :token';
                $stmt = $this->pdo->prepare($query);
                $stmt->bindValue(
                    ':userId',
                    $pushtoken->getUserId(),
                    \PDO::PARAM_INT
                );
                $stmt->bindValue(
                    ':token',
                    $pushtoken->getToken(),
                    \PDO::PARAM_STR
                );
                $stmt->bindValue(
                    ':denied',
                    $pushtoken->isDenied(),
                    \PDO::PARAM_BOOL
                );
                $stmt->execute();
            } else {
                $query =
                    'INSERT INTO push_token (user_id, platform, token, denied) ';
                $query .= ' VALUES (:userId, :platform, :token, :denied )';
                $stmt = $this->pdo->prepare($query);
                $stmt->bindValue(
                    ':userId',
                    $pushtoken->getUserId(),
                    \PDO::PARAM_INT
                );
                $stmt->bindValue(
                    ':platform',
                    $pushtoken->getPlatform(),
                    \PDO::PARAM_INT
                );
                $stmt->bindValue(
                    ':token',
                    $pushtoken->getToken(),
                    \PDO::PARAM_STR
                );
                $stmt->bindValue(
                    ':denied',
                    $pushtoken->isDenied(),
                    \PDO::PARAM_BOOL
                );
                $stmt->execute();
            }
            if ($pushtoken->isDenied()) {
                $this->handleAlerts($pushtoken->getUserId());
            }
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }

    /**
     * handleAlerts
     *
     * @param $userId
     *
     * @return void
     */
    private function handleAlerts($userId)
    {
        $query = 'SELECT user_id FROM push_token WHERE ';
        $query .= 'denied = false AND user_id = :userId ';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(
            ':userId',
            $userId,
            \PDO::PARAM_INT
        );
        $stmt->execute();
        if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return;
        } else {
            //deactivate push option
            $query = 'UPDATE alert_x_type_cycle SET type_x_cycle_id = ';
            $query .= '(SELECT type_x_cycle_id FROM type_x_cycle WHERE alert_type_id = 3) ';
            $query .= ' WHERE alert_id in (SELECT a.alert_id FROM alert_x_type_cycle a ';
            $query .= ' JOIN alert b ON (a.alert_id = b.alert_id) ';
            $query .= ' WHERE type_x_cycle_id = (SELECT type_x_cycle_id FROM type_x_cycle ';
            $query .= ' WHERE alert_type_id = 4) AND b.user_id=:userId)';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(
                ':userId',
                $userId,
                \PDO::PARAM_INT
            );
            $stmt->execute();
            return;
        }
    }
}
