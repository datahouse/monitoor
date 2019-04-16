<?php

namespace Datahouse\MON\Register\Add;

use Acme\UserBundle\Entity\User;
use Datahouse\MON\Exception\VoucherInvalidException;
use Datahouse\MON\Model\PasswordModel;
use Datahouse\MON\Types\UserHash;

/**
 * Class Model
 *
 * @package Register
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
     * create user
     *
     * @param string $email         the email
     * @param string $pwd           the password
     * @param string $firstname     the firstname
     * @param string $lastname      the lastname
     * @param string $company       the company name
     * @param int    $pricingPlanId the pricing plan id
     * @param string $voucherCode   the voucher
     *
     * @return UserHash
     * @throws \Exception
     */
    public function createUser($email, $pwd, $firstname, $lastname, $company, $pricingPlanId, $voucherCode)
    {
        $query = '';
        try {
            $this->pdo->beginTransaction();
            $query = 'INSERT INTO mon_user (user_email, user_password,';
            $query .= ' user_password_salt)';
            $query .= ' VALUES (:email, :pwd, :salt) RETURNING user_id';
            $stmt = $this->pdo->prepare($query);
            $pwdSalt = $this->createPwdSalt();
            $pwdEnc = $this->encrypt($pwd, $pwdSalt);
            $stmt->bindValue(':email', $email, \PDO::PARAM_STR);
            $stmt->bindValue(':pwd', $pwdEnc, \PDO::PARAM_STR);
            $stmt->bindValue(':salt', $pwdSalt, \PDO::PARAM_STR);
            $stmt->execute();

            $userId = $stmt->fetchColumn();

            $hash = $this->createActivationHash();
            $query = 'INSERT INTO user_activation (user_id, ';
            $query .= 'user_activation_created, user_activation_hash)';
            $query .= ' VALUES (:userId, NOW(), :hash)';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':hash', $hash, \PDO::PARAM_STR);
            $stmt->execute();

            $query = 'INSERT INTO account (user_id, ';
            $query .= 'account_name_first, account_name_last, account_company, pricing_plan_id)';
            $query .= ' VALUES (:userId, :firstname, :lastname, :company, :pricingPlan)';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':firstname', $firstname, \PDO::PARAM_STR);
            $stmt->bindValue(':lastname', $lastname, \PDO::PARAM_STR);
            $stmt->bindValue(':company', $company, \PDO::PARAM_STR);
            $stmt->bindValue(':pricingPlan', $pricingPlanId, \PDO::PARAM_INT);
            $stmt->execute();

            if ($voucherCode != null) {
                $query = 'UPDATE voucher SET voucher_used = NOW() WHERE voucher_code = :voucher';
                $stmt = $this->pdo->prepare($query);
                $stmt->bindValue(':voucher', $voucherCode, \PDO::PARAM_STR);
                $stmt->execute();
                $query =
                    'UPDATE account SET voucher_id = (SELECT voucher_id FROM voucher ';
                $query .= ' WHERE voucher_code = :voucher) WHERE user_id = :userid ';
                $stmt = $this->pdo->prepare($query);
                $stmt->bindValue(':userid', $userId, \PDO::PARAM_INT);
                $stmt->bindValue(':voucher', $voucherCode, \PDO::PARAM_STR);
                $stmt->execute();
            }
            $userHash = new UserHash();
            $userHash->setHash($hash);
            $userHash->setUserId($userId);
            $this->pdo->commit();
            return $userHash;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }

    /**
     * createActivationHash
     *
     *
     * @return string
     */
    private function createActivationHash()
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
        return $hash;
    }

    /**
     * isEmailUnique
     *
     * @param string $email the email
     *
     * @return bool
     * @throws \Exception
     */
    public function isEmailUnique($email)
    {
        $query = '';
        try {
            $query = 'SELECT user_id FROM mon_user WHERE ';
            $query .= ' user_email = :email ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':email', $email, \PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
                return false;
            }
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
        return true;
    }

    /**
     * getVoucher
     *
     * @param $code
     *
     * @return \DateTime
     * @throws VoucherInvalidException
     * @throws \Exception
     */
    public function getVoucher($code)
    {
        $query = '';
        try {
            $query = 'SELECT voucher_code, voucher_used FROM voucher ';
            $query .= ' WHERE voucher_code = :voucher';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':voucher', $code, \PDO::PARAM_STR);
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                return $res['voucher_used'];
            }
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
        throw new VoucherInvalidException();
    }
}
