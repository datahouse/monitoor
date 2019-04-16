<?php

namespace Datahouse\MON\User\Pwd;

use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Model\PasswordModel;

/**
 * Class Model
 *
 * @package Alert
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Model extends PasswordModel
{

    /**
     * @param \PDO $pdo the pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * changePwd
     *
     * @param string $pwd          the password
     * @param string $recoveryHash the recovery hash
     *
     * @return int
     * @throws \Exception
     */
    public function changePwd($pwd, $recoveryHash)
    {
        $query = '';
        try {
            $this->pdo->beginTransaction();
            $query .= 'SELECT user_id FROM user_pw_recovery WHERE ';
            $query .= 'user_pw_recovery_hash = :hash ';
            $query .= ' AND user_pw_recovery_used IS NULL AND';
            $query .= ' user_pw_recovery_created + INTERVAL \'1 DAYS\' > NOW() ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':hash', $recoveryHash, \PDO::PARAM_STR);
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $userId = $res['user_id'];
                $pwdSalt = $this->createPwdSalt();
                $pwdEnc = $this->encrypt($pwd, $pwdSalt);
                $query = 'UPDATE mon_user SET user_password = :pwd, ';
                $query .= 'user_password_salt = :salt ';
                $query .= ' WHERE user_id = :userId';
                $stmt = $this->pdo->prepare($query);
                $stmt->bindValue(':pwd', $pwdEnc, \PDO::PARAM_STR);
                $stmt->bindValue(':salt', $pwdSalt, \PDO::PARAM_STR);
                $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
                $stmt->execute();
                $query =
                    'UPDATE user_pw_recovery SET user_pw_recovery_used = NOW() ';
                $query .= ' WHERE user_pw_recovery_hash = :hash';
                $stmt = $this->pdo->prepare($query);
                $stmt->bindValue(':hash', $recoveryHash, \PDO::PARAM_STR);
                $stmt->execute();
                $this->pdo->commit();
                return $userId;
            }
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
        throw new PermissionException('recovery failed for ' . $recoveryHash);
    }
}
