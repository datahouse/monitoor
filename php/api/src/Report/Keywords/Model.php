<?php

namespace Datahouse\MON\Report\Keywords;

use Datahouse\MON\Types\Gen\KeywordGraphData;

/**
 * Class Model
 *
 * @package Report
 * @author  Flavio Neuenschwander (fne) <flavio.neuenschwander@datahouse.ch>
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
     * readKeywordList
     *
     * @param int $urlGroupId the group id
     * @param int $userId     the userid
     *
     * @return array
     * @throws \Exception
     */
    public function readKeywordList($urlGroupId, $userId)
    {
        $keywordList = array();
        $query = '';
        try {
            $query .= '
                SELECT alert_keyword, count(*) AS count
                FROM notification_x_keyword nk
                JOIN alert_keyword ak
                  ON nk.alert_keyword_id = ak.alert_keyword_id
                INNER JOIN v_change AS C
                  ON c.alert_id = nk.alert_id
                  AND c.change_id = nk.change_id
                  AND c.type_x_cycle_id = nk.type_x_cycle_id
                  AND c.user_id = :userId
                  AND (c.url_group_id = :urlGrpId OR :urlGrpId IS NULL)
                  AND date(NOW() - INTERVAL \'10 weeks\') <= date(creation_ts)
                WHERE ak.alert_keyword_active
                GROUP BY alert_keyword;';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':urlGrpId', $urlGroupId, \PDO::PARAM_INT);
            $stmt->execute();

            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $res) {
                $keywordGraphDataObject = new KeywordGraphData();
                $keywordGraphDataObject->setKey($res['alert_keyword']);
                $keywordGraphDataObject->setY($res['count']);

                $keywordList[] = $keywordGraphDataObject;
            }
            return $keywordList;
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ');
        }
    }
}
