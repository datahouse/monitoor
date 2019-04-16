<?php

namespace Datahouse\MON\User\Update;

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
     * updateUser
     *
     * @param User $user the user
     *
     * @return bool
     * @throws \Exception
     */
    public function updateUser(User $user)
    {
        $query = '';
        try {
            $this->pdo->beginTransaction();
            $query .= 'UPDATE account SET account_name_first = :first,';
            $query .= ' account_name_last = :last, ';
            $query .= ' account_mobile = :mobile, ';
            $query .= ' account_company = :company ';
            $query .= ' WHERE user_id=:userId ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':first', $user->getFirstName(), \PDO::PARAM_STR);
            $stmt->bindValue(':last', $user->getLastName(), \PDO::PARAM_STR);
            $stmt->bindValue(':mobile', $user->getMobile(), \PDO::PARAM_STR);
            $stmt->bindValue(':userId', $user->getId(), \PDO::PARAM_INT);
            $stmt->bindValue(':company', $user->getCompany(), \PDO::PARAM_STR);
            $stmt->execute();
            $query = 'INSERT INTO account (user_id, account_name_first, ';
            $query .= ' account_name_last, account_mobile, account_company) SELECT ';
            $query .= '  :userId, :first, :last, :mobile, :company WHERE NOT EXISTS  ';
            $query .= ' (SELECT 1 FROM account WHERE user_id=:userId )';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':first', $user->getFirstName(), \PDO::PARAM_STR);
            $stmt->bindValue(':last', $user->getLastName(), \PDO::PARAM_STR);
            $stmt->bindValue(':mobile', $user->getMobile(), \PDO::PARAM_STR);
            $stmt->bindValue(':userId', $user->getId(), \PDO::PARAM_INT);
            $stmt->bindValue(':company', $user->getCompany(), \PDO::PARAM_STR);
            $stmt->execute();
            $query = 'UPDATE mon_user SET user_email = :email ';
            $query .= ' WHERE user_id=:userId ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':email', $user->getEmail(), \PDO::PARAM_STR);
            $stmt->bindValue(':userId', $user->getId(), \PDO::PARAM_INT);
            $stmt->execute();
            //$stmt->debugDumpParams();
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }

    /**
     * isUniqueEmail
     *
     * @param string $email  the email
     * @param int    $userId the userId
     *
     * @return bool
     * @throws \Exception
     */
    public function isUniqueEmail($email, $userId)
    {
        $query = '';
        try {
            $query = 'SELECT user_id FROM mon_user ';
            $query .= 'WHERE user_email = :email AND';
            $query .= ' user_id <> :userId';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':email', $email, \PDO::PARAM_STR);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $res['user_id'];
                return false;
            }
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
        return true;
    }
}
