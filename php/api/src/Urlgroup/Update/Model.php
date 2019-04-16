<?php

namespace Datahouse\MON\Urlgroup\Update;

use Datahouse\MON\Types\Gen\UrlGroup;

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
     * updateUrlGroup
     *
     * @param UrlGroup $urlGroup the url group
     *
     * @return bool
     * @throws \Exception
     */
    public function updateUrlGroup(UrlGroup $urlGroup)
    {
        $query = '';
        try {
            $this->pdo->beginTransaction();
            $query .= 'UPDATE url_group SET url_group_title = :title ';
            $query .= ' WHERE url_group_id=:urlGroupId ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':title', $urlGroup->getTitle(), \PDO::PARAM_STR);
            $stmt->bindValue(
                ':urlGroupId',
                $urlGroup->getId(),
                \PDO::PARAM_INT
            );
            $stmt->execute();
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }
}
