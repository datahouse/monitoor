<?php

namespace Datahouse\MON\Data\Push;

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
     * insertProviderDate
     *
     * @param int $userId the user
     * @param int $urlId the url id
     * @param array $items array of ExternalData items
     *
     * @return void
     * @throws \Exception
     */
    public function insertProviderData(
        $userId,
        $urlId,
        array &$items
    ) {
        $query = '';
        try {
            $this->pdo->beginTransaction();

            $query = 'SELECT add_external_change('
                . ':ts, :delta, :userId, :urlId);';
            $stmt = $this->pdo->prepare($query);

            foreach ($items as &$item)
            {
                $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
                $stmt->bindValue(':urlId', $urlId, \PDO::PARAM_INT);
                $stmt->bindValue(':ts', $item->getTimestamp());
                $delta = array(array(
                    'add' => $item->getAddition(),
                    'del' => $item->getDeletion()
                ));
                $stmt->bindValue(':delta', json_encode($delta));
                $stmt->execute();
            }

            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }
}
