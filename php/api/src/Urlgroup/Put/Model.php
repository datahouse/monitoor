<?php

namespace Datahouse\MON\Urlgroup\Put;

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
     * @param int   $newGroupId the new url group id null
     * @param array $urlIds     the url id
     *
     * @return bool
     * @throws \Exception
     */
    public function putUrlIntoGroup($newGroupId, $urlIds)
    {
        $query = '';
        try {
            $this->pdo->beginTransaction();

            $query = 'UPDATE url SET url_group_id = :urlGroupId ';
            $query .= ' WHERE url_id = :urlId ';
            $stmt = $this->pdo->prepare($query);
            foreach ($urlIds as $urlId) {
                $stmt->bindValue(
                    ':urlGroupId',
                    $newGroupId,
                    \PDO::PARAM_INT
                );
                $stmt->bindValue(':urlId', $urlId, \PDO::PARAM_INT);
                $stmt->execute();
            }
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }
}
