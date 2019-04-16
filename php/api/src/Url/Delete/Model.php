<?php

namespace Datahouse\MON\Url\Delete;

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
     * deleteUrl
     *
     * @param int $urlId the alert id
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteUrl($urlId)
    {

        $query = '';
        try {
            $this->pdo->beginTransaction();
            $query = 'UPDATE url SET url_active = FALSE, url_group_id = NULL ';
            $query.= ' WHERE url_id = :urlId';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':urlId', $urlId, \PDO::PARAM_INT);
            $stmt->execute();

            $query = 'DELETE FROM access_control WHERE url_id = :urlId';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':urlId', $urlId, \PDO::PARAM_INT);
            $stmt->execute();

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }
}
