<?php

namespace Datahouse\MON\User\Delete;

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
     * @param int $userId the user id
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteUser($userId)
    {

        $query = '';
        try {
            $this->pdo->beginTransaction();
            $query = 'DELETE FROM account WHERE user_id = :userId ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            $query =
                'UPDATE mon_user SET user_valid_till = NOW() WHERE user_id = :userId';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }
}
