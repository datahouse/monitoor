<?php

namespace Datahouse\MON\User\Login;

use Datahouse\MON\Exception\AccountExpiredException;
use Datahouse\MON\Exception\UnauthorizedException;
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
     * login
     *
     * @param string $email the email
     * @param string $pwd   the password
     *
     * @return int
     * @throws UnauthorizedException
     * @throws \Exception
     */
    public function login($email, $pwd)
    {
        $query = '';
        try {
            $query =
                'SELECT user_id, user_password, user_password_salt, ';
            $query .= 'user_valid_till FROM mon_user ';
            $query .= 'WHERE user_email = :email ';
            $query .= ' AND user_activated ';
            //$query .= ' AND (user_valid_till is null OR user_valid_till > NOW())';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':email', $email, \PDO::PARAM_STR);
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $pwdSalt = $res['user_password_salt'];
                $pwdHash = $res['user_password'];
                if ($this->checkPwdMatch($pwd, $pwdHash, $pwdSalt)) {
                    $now = new \DateTime();
                    $validTill = null;
                    if ($res['user_valid_till'] != null) {
                        $validTill = new \DateTime($res['user_valid_till']);
                    }
                    if ($validTill == null || $validTill > $now) {
                        // auth ok
                        $userId = $res['user_id'];
                        $this->setLastLogin($userId);
                        return $userId;
                    } else {
                        throw new AccountExpiredException();
                    }
                }
            }
        } catch (AccountExpiredException $ae) {
            throw new AccountExpiredException($ae);
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
        throw new UnauthorizedException('credentials not valid ' . $email);
    }

    /**
     * setLastLogin
     *
     * @param int $userId the userid
     *
     * @return void
     * @throws \Exception
     */
    private function setLastLogin($userId)
    {
        $query = '';
        try {
            $query .= 'UPDATE mon_user SET user_last_login = NOW() ';
            $query .= ' WHERE user_id= :userId ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->execute();
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
    }
}
