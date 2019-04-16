<?php

namespace Datahouse\MON\User\Get;

use Datahouse\MON\Exception\KeyNotFoundException;
use Datahouse\MON\Types\Gen\User;

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
     * readUser
     *
     * @param int $userId the user id
     *
     * @return User
     * @throws KeyNotFoundException
     * @throws \Exception
     */
    public function readUser($userId)
    {
        $user = new User();

        $query = '';
        try {
            $query .= 'SELECT u.user_id, u.user_email, a.account_id, ';
            $query .= ' a.account_name_first, a.account_name_last, a.account_company, ';
            $query .= ' a.account_mobile FROM mon_user u LEFT JOIN account a ';
            $query .= ' ON (u.user_id = a.user_id) WHERE u.user_id = :userId';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $user->setId($res['user_id']);
                $user->setEmail($res['user_email']);
                $user->setFirstName($res['account_name_first']);
                $user->setLastName($res['account_name_last']);
                $user->setMobile($res['account_mobile']);
                $user->setCompany($res['account_company']);
                return $user;
            }
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
        throw new KeyNotFoundException('no user with id ' . $userId);
    }
}
