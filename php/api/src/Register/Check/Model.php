<?php

namespace Datahouse\MON\Register\Check;

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
     * isUniqueEmail returns true if the email doesnt exists and fals if it exists
     *
     * @param string $email the email
     *
     * @return bool
     * @throws \Exception
     */
    public function isUniqueEmail($email)
    {
        $query = '';
        try {
            $query = 'SELECT user_id FROM mon_user ';
            $query .= 'WHERE user_email = :email ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':email', $email, \PDO::PARAM_STR);
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
