<?php

namespace Datahouse\MON\Change\Unpin;


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


    public function deleteFavorite($changeId, $userId)
    {
        $query = '';
        try {
            $this->pdo->beginTransaction();
            $query = 'UPDATE notification SET favorite = false WHERE ';
            $query .= ' change_id = :change AND alert_id IN ';
            $query .= ' (SELECT alert_id FROM alert WHERE user_id = :user ) ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':change', $changeId, \PDO::PARAM_INT);
            $stmt->bindValue(':user', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }
}
