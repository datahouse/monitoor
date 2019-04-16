<?php

namespace Datahouse\MON\Change\Get;

use Datahouse\MON\Exception\KeyNotFoundException;
use Datahouse\MON\Model\ChangeModel;
use Datahouse\MON\Types\Gen\ChangeItem;

/**
 * Class Model
 *
 * @package     Change
 * @author      Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Model extends ChangeModel
{
    /**
     * getChange
     *
     * @param $hashValue
     *
     * @return ChangeItem
     * @throws KeyNotFoundException
     * @throws \Exception
     */
    public function getChange($hashValue)
    {
        $query = '';
        try {
            $query =
                'SELECT change_share_id, user_id, change_id FROM change_share WHERE share_hash = :hash ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':hash', $hashValue, \PDO::PARAM_STR);
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $shareId = $res['change_share_id'];
                $changeId = $res['change_id'];
                $userId = $res['user_id'];
                $fields =
                    'c.alert_id, c.new_doc_id, c.old_doc_id, c.change_id, c.delta, c.creation_ts, ';
                $fields .= 'c.url_id, c.url,c.url_title, c.url_group_id, c.url_group_title, c.favorite ';
                $query = 'SELECT ' . $fields .
                    ' , NULL AS rating_value_id, json_agg(kw.alert_keyword) AS keywords ';
                $query .= 'FROM v_change c LEFT JOIN notification_keyword kw ';
                $query .= 'ON kw.alert_id = c.alert_id AND kw.change_id = c.change_id ';
                $query .= 'AND kw.type_x_cycle_id = c.type_x_cycle_id ';
                $query .= 'WHERE c.change_id = :changeId AND c.user_id = :userId ';
                $query .= 'GROUP BY ' . $fields . ', rating_value_id ';
                $stmt = $this->pdo->prepare($query);
                $stmt->bindValue(':changeId', $changeId, \PDO::PARAM_INT);
                $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
                $stmt->execute();
                if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $query =
                        'UPDATE  change_share SET last_used = NOW() WHERE change_share_id = :shareId';
                    $stmt = $this->pdo->prepare($query);
                    $stmt->bindValue(':shareId', $shareId, \PDO::PARAM_INT);
                    $stmt->execute();
                    $changeItem = $this->createChangeItem($res);
                    return $changeItem;
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
        throw new KeyNotFoundException('change not found');
    }
}
