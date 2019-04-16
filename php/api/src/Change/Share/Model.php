<?php

namespace Datahouse\MON\Change\Share;

use Datahouse\MON\Model\ChangeModel;

/**
 * Class Model
 *
 * @package Change
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Model extends ChangeModel
{

    public function shareChange($changeId, $userId)
    {
        try {
            $this->pdo->beginTransaction();
            $query = 'SELECT share_hash FROM change_share WHERE change_id=:changeId AND user_id=:userId LIMIT 1';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':changeId', $changeId, \PDO::PARAM_INT);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            $this->pdo->commit();
            $res = $stmt->fetch();
            if ($stmt->rowCount() > 0) {
                return $res['share_hash'];
            }
        } catch (\Exception $e) {
            $this->pdo->rollBack();
        }

        $query = '';
        try {
            $this->pdo->beginTransaction();
            $hash = $this->createChangeHash();
            $query = 'INSERT INTO change_share (change_id, user_id, share_hash) ';
            $query .= ' VALUES (:changeId, :userId, :hash) ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':changeId', $changeId, \PDO::PARAM_INT);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':hash', $hash, \PDO::PARAM_STR);
            $stmt->execute();
            $this->pdo->commit();
            return $hash;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }

    /**
     * createRecoveryHash
     *
     *
     * @return string
     */
    private function createChangeHash()
    {
        $seed = '8r5hj5G2gMHmbhFBWgsF';
        $isUnique = false;
        $query = 'select change_share_id FROM change_share ';
        $query .= ' WHERE share_hash = :hash';
        $stmt = $this->pdo->prepare($query);
        do {

            $hash = sha1(uniqid($seed . mt_rand(), true));
            $hash = substr($hash, 0, 10);
            $stmt->bindValue(':hash', $hash, \PDO::PARAM_STR);
            $stmt->execute();
            if (!$res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $res['change_share_id'];
                $isUnique = true;
            }
        } while (!$isUnique);
        return $hash;
    }
}
