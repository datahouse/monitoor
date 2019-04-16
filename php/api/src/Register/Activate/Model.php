<?php

namespace Datahouse\MON\Register\Activate;

use Datahouse\MON\Exception\PermissionException;

/**
 * Class Model
 *
 * @package Register
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
     * activateUser
     *
     * @param string $activationHash the activation hash
     *
     * @return int
     * @throws \Exception
     */
    public function activateUser($activationHash)
    {
        $query = '';
        try {
            $this->pdo->beginTransaction();
            $query .= 'SELECT user_id FROM user_activation WHERE ';
            $query .= 'user_activation_hash = :hash ';
            $query .= ' AND user_activation_used IS NULL AND';
            $query .= ' user_activation_created + INTERVAL \'1 DAYS\' > NOW() ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':hash', $activationHash, \PDO::PARAM_STR);
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $query = 'UPDATE mon_user SET user_activated = true ';
                $query .= ' WHERE user_id = :userId';
                $stmt = $this->pdo->prepare($query);
                $stmt->bindValue(':userId', $res['user_id'], \PDO::PARAM_INT);
                $stmt->execute();
                $query =
                    'UPDATE user_activation SET user_activation_used = NOW() ';
                $query .= ' WHERE user_id = :userId';
                $stmt = $this->pdo->prepare($query);
                $stmt->bindValue(':userId', $res['user_id'], \PDO::PARAM_INT);
                $stmt->execute();
                $this->pdo->commit();
                return $res['user_id'];
            }
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
        throw new PermissionException(
            'activation code not valid ' . $activationHash
        );
    }
}
