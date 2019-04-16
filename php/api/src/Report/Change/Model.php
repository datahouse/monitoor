<?php

namespace Datahouse\MON\Report\Change;

/**
 * Class Model
 *
 * @package Report
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
     * readReportList
     *
     * @param int  $urlGroupId the group id
     * @param int  $userId     the userid
     * @param bool $demo       the demo flag
     *
     * @return array
     * @throws \Exception
     */
    public function readReportList($urlGroupId, $userId, $demo = false)
    {
        $reportList = array();
        try {
            // FIXME: move to a custom SQL function, rather than inlining such
            // a huge query.
            $query = "
SELECT
  weekly_counts.url_group_id,
  url_group.url_group_title,
  date(trim_to_notification_period(now()) - t_diff_weeks * '1 week'::interval) AS time,
  SUM(weekly_counts.count)::BIGINT AS count
FROM
  (
    SELECT
      url_group_id,
      count,
      floor(extract(
        'days'
        FROM trim_to_notification_period(now()) - period_start
      ) / 7) AS t_diff_weeks
    FROM
      notification_counter
    WHERE
    ((user_id = :userId AND NOT :isDemo) OR (:isDemo)) AND
      (
        (NOT :isDemo AND
          (notification_counter.url_group_id = :urlGroupId OR :urlGroupId IS NULL)
        )
        OR (:isDemo AND
          -- FIXME: extract a get_url_groups_for_user function
          (url_group_id IN
            (
              -- FIXME: why only the first url_group where is_demo
              select url_group_id FROM url_group where url_group_id = (SELECT MIN(url_group_id) FROM url_group WHERE is_demo)
            )
          )
        )
      )
      AND
        period_start
          BETWEEN
            trim_to_notification_period(now()) - '10 weeks -1second'::interval
          AND
            trim_to_notification_period(now())
  ) AS weekly_counts
LEFT JOIN url_group
  ON url_group.url_group_id = weekly_counts.url_group_id
GROUP BY weekly_counts.url_group_id, url_group.url_group_title, time
ORDER BY weekly_counts.url_group_id, time;";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':urlGroupId', $urlGroupId, \PDO::PARAM_INT);
            $stmt->bindValue(':isDemo', $demo, \PDO::PARAM_INT);

            $stmt->execute();
            $dates = $this->getDates();
            $groupId = null;
            $groupTitle = null;
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $res) {
                if ($groupId != $res['url_group_id'] && $groupId != null) {
                    $reportItem['id'] = $groupId;
                    $reportItem['title'] = $groupTitle;
                    $reportItem['values'] = array_values($dates);
                    $reportList[] = $reportItem;
                    $reportItem = array();
                    $dates = $this->getDates();
                }
                $dates[$res['time']] =
                    ['date' => $res['time'], 'count' => $res['count']];
                $groupId = $res['url_group_id'];
                $groupTitle = $res['url_group_title'];
            }
            if ($groupId != null) {
                $reportItem['id'] = $groupId;
                $reportItem['title'] = $groupTitle;
                $reportItem['values'] = array_values($dates);
                $reportList[] = $reportItem;
            }
            if ($urlGroupId != null && count($reportList) > 0) {
                return $reportList[0];
            }
            return $reportList;
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ');
        }
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
            // FIXME: optimize this query to not use a subquery, but direct
            // joins.
            $query .= '
                SELECT alert_keyword, count(*) AS count
                FROM notification_keyword nk
                JOIN alert_keyword ak
                  ON nk.alert_keyword_id = ak.alert_keyword_id
                  AND alert_keyword_active
                WHERE EXISTS (
                    SELECT 1 FROM v_change c
                    WHERE c.alert_id = nk.alert_id
                      AND c.change_id = nk.change_id
                      AND c.type_x_cycle_id = nk.type_x_cycle_id
                      AND user_id = :userId
                      AND url_group_id = :urlGrpId
                )
                GROUP BY alert_keyword;';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':urlGrpId', $urlGroupId, \PDO::PARAM_INT);
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $res) {
                $keywordList[] = array(
                    'keyword' => $res['alert_keyword'],
                    'count' => $res['count']
                );
            }
            return $keywordList;
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ');
        }
    }

    /**
     * getDates
     *
     * @return array
     */
    private function getDates()
    {
        $dates = array();
        for ($i = 0; $i < 11; $i++) {
            $back = '-' . $i . ' weeks';
            $date = date("Y-m-d", strtotime($back));
            $dates[$date] = ['date' => $date, 'count' => 0];
        }
        return $dates;
    }
}
