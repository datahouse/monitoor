<?php

namespace Datahouse\MON\User\Password;

use Datahouse\MON\Exception\OldPasswordIncorrectException;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Model\PasswordModel;

/**
 * Class Model
 *
 * @package User
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
     * @param string $pwd    the password
     * @param string $oldPwd the old passwordh
     * @param int    $userId the user id
     *
     * @return boolean
     * @throws \Exception
     */
    public function changePwd($pwd, $oldPwd, $userId)
    {
        $query = '';
        try {
            $this->pdo->beginTransaction();
            $query =
                'SELECT user_password, user_password_salt FROM mon_user WHERE ';
            $query .= ' user_id=' . intval($userId);
            $query .= ' AND user_activated ';
            $query .= ' AND (user_valid_till is null OR user_valid_till > NOW())';
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $pwdSalt = $res['user_password_salt'];
                $pwdHash = $res['user_password'];
                if ($this->checkPwdMatch($oldPwd, $pwdHash, $pwdSalt)) {
                    // auth ok set new Password
                    $pwdEnc = $this->encrypt($pwd, $pwdSalt);
                    $query = 'UPDATE mon_user SET user_password = :pwd ';
                    $query .= ' WHERE user_id = ' . intval($userId);
                    $stmt = $this->pdo->prepare($query);
                    $stmt->bindValue(':pwd', $pwdEnc, \PDO::PARAM_STR);
                    $stmt->execute();
                    $this->pdo->commit();
                    return;
                } else {
                    throw new OldPasswordIncorrectException(
                        'old password is incorrect for user ' . $userId
                    );
                }
            }
        } catch (OldPasswordIncorrectException $opie) {
            $this->pdo->rollBack();
            throw new OldPasswordIncorrectException(
                $opie->getMessage()
            );
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
        throw new PermissionException(
            'password change failed for user ' . $userId
        );
    }
}
