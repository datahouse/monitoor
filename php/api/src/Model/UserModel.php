<?php


namespace Datahouse\MON\Model;

use Datahouse\Framework\Model;

/**
 * Class PasswordModel
 *
 * @package Model
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
abstract class UserModel extends Model
{
    /**
     * @var \PDO the pdo
     */
    protected $pdo;

    /**
     * @param \PDO $pdo the pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * createUser
     *
     * @param string $email the email
     *
     * @return int
     * @throws \Exception
     */
    protected function createUser($email, $pricePlan)
    {
        $query = 'INSERT INTO mon_user (user_email)';
        $query .= ' VALUES (:email) RETURNING user_id';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':email', $email, \PDO::PARAM_STR);
        $stmt->execute();
        $userId = $stmt->fetchColumn();

        $query = 'INSERT INTO account (user_id, pricing_plan_id)';
        $query .= ' VALUES (:userId, :pricingPlan)';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(
            ':pricingPlan',
            $pricePlan,
            \PDO::PARAM_INT
        );
        $stmt->execute();
        return $userId;
    }

    /**
     * createActivationHash
     *
     *
     * @return string
     */
    protected function createActivationHash($userId)
    {
        $isUnique = false;
        $query = 'select user_activation_id FROM user_activation ';
        $query .= ' WHERE user_activation_hash = :hash';
        $stmt = $this->pdo->prepare($query);
        do {
            $hash = sha1(rand());
            $stmt->bindValue(':hash', $hash, \PDO::PARAM_STR);
            $stmt->execute();
            if (!$res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $res['user_unlock_id'];
                $isUnique = true;
            }
        } while (!$isUnique);

        $query = 'INSERT INTO user_activation (user_id, ';
        $query .= 'user_activation_created, user_activation_hash)';
        $query .= ' VALUES (:userId, NOW(), :hash)';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':hash', $hash, \PDO::PARAM_STR);
        $stmt->execute();
        return $hash;
    }

    /**
     * isExistingUser
     *
     * @param string $email the email
     *
     * @return int
     * @throws \Exception
     */
    protected function isExistingUser($email)
    {
        $query = 'SELECT user_id FROM mon_user WHERE ';
        $query .= ' user_email = :email ';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':email', $email, \PDO::PARAM_STR);
        $stmt->execute();
        if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $res['user_id'];
        }
        return null;
    }
}
