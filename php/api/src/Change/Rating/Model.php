<?php

namespace Datahouse\MON\Change\Rating;

use Datahouse\MON\Exception\PermissionException;

/**
 * Class Model
 *
 * @package Change
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
     * insertRating
     *
     * @param int $alertId the alert id
     * @param int $changeId   the change id
     * @param int $userId  the user id
     * @param int $rating  the rating
     *
     * @return bool
     * @throws \Exception
     */
    public function insertRating($changeId, $userId, $rating)
    {
        $query = '';
        try {
            $this->pdo->beginTransaction();
            $query =
                'UPDATE rating set rating_value_id = :rating WHERE change_id = :changeId';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':changeId', $changeId, \PDO::PARAM_INT);
            $stmt->bindValue(':rating', $rating, \PDO::PARAM_INT);
            $stmt->execute();
            $query =
                'INSERT INTO rating(change_id, user_id, rating_value_id) ';
            $query .= 'SELECT :changeId, :userId, :rating WHERE NOT EXISTS ';
            $query .= ' (SELECT change_id FROM rating WHERE change_id=:changeId) ';
            //$query .= 'VALUES (:changeId, :userId, :rating) WHERE';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':changeId', $changeId, \PDO::PARAM_INT);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':rating', $rating, \PDO::PARAM_INT);
            $stmt->execute();
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }
}
