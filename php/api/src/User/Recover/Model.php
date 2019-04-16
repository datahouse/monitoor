<?php

namespace Datahouse\MON\User\Recover;


/**
 * Class Model
 *
 * @package Alert
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Model extends \Datahouse\Framework\Model
{

    /**
     * @param \PDO $pdo the pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * createPwdRecovery
     *
     * @param string $email the email
     *
     * @return string
     * @throws \Exception
     */
    public function createPwdRecovery($email)
    {
        $query = '';
        try {
            $query = 'SELECT user_id from mon_user ';
            $query .= 'WHERE user_email = :email';
            $query .= ' AND user_activated ';
            $query .= ' AND (user_valid_till is null OR user_valid_till > NOW())';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':email', $email, \PDO::PARAM_STR);
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $userId = ($res['user_id']);
                $hash = $this->createRecoveryHash();
                $query = 'INSERT INTO user_pw_recovery (user_id, ';
                $query .= 'user_pw_recovery_created, user_pw_recovery_hash)';
                $query .= ' VALUES (:userId, NOW(), :hash)';
                $stmt = $this->pdo->prepare($query);
                $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
                $stmt->bindValue(':hash', $hash, \PDO::PARAM_STR);
                $stmt->execute();
                return $hash;
            }
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
        return null;
    }

    /**
     * createRecoveryHash
     *
     *
     * @return string
     */
    private function createRecoveryHash()
    {
        $isUnique = false;
        $query = 'select user_pw_recovery_id FROM user_pw_recovery ';
        $query .= ' WHERE user_pw_recovery_hash = :hash';
        $stmt = $this->pdo->prepare($query);
        do {
            $hash = sha1(rand());
            $stmt->bindValue(':hash', $hash, \PDO::PARAM_STR);
            $stmt->execute();
            if (!$res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $res['user_pw_recovery_id'];
                $isUnique = true;
            }
        } while (!$isUnique);
        return $hash;
    }
}
