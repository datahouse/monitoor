<?php

namespace Datahouse\MON\Urlgroup\Delete;

/**
 * Class Model
 *
 * @package Url
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Model extends \Datahouse\Framework\Model
{

    private $pdo;

    /**
     * @param \PDO $pdo the pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * deleteUrlGroup
     *
     * @param int $urlGroupId the url group id
     * @param int $userId     the userid
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteUrlGroup($urlGroupId, $userId)
    {

        $query = '';
        try {
            $this->pdo->beginTransaction();
            $query = 'UPDATE alert set alert_active = false WHERE user_id = :userId';
            $query .= ' AND alert_id IN (SELECT alert_id FROM alert_x_url_group ';
            $query .= ' WHERE url_group_id = :urlGroupId)';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':urlGroupId', $urlGroupId, \PDO::PARAM_INT);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            $query = 'DELETE FROM url_group WHERE url_group_id = :urlGroupId';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':urlGroupId', $urlGroupId, \PDO::PARAM_INT);
            $stmt->execute();
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }

    /**
     * countUrlsInGroup
     *
     * @param int $urlGroupId the url group id
     *
     * @return int
     * @throws \Exception
     */
    public function countUrlsInGroup($urlGroupId)
    {
        $query = '';
        try {
            $query = 'SELECT count(*) AS count FROM url ';
            $query .= 'WHERE url_group_id = :urlGroupId';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':urlGroupId', $urlGroupId, \PDO::PARAM_INT);
            $stmt->execute();
            $count = 0;
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $count = $res['count'];
            }
            return $count;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }
}
