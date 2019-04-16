<?php


namespace Datahouse\MON\Model;

use Datahouse\Framework\Model;
use Datahouse\MON\Exception\ValidationException;

/**
 * Class PasswordModel
 *
 * @package Model
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
abstract class SubscriptionModel extends Model
{
    /**
     * @var \PDO the pdo
     */
    protected $pdo;

    /**
     * @param \PDO $pdo the pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * unsubscribeUrlGroup
     *
     * @param int $urlGroupId the url group id
     * @param int $userId     the userid
     *
     * @return bool
     * @throws \Exception
     */
    public function subscribeUrlGroup($urlGroupId, $userId, $urlId, $on)
    {

        $query = '';
        try {
            $this->pdo->beginTransaction();
            if ($this->isSubscription($urlGroupId, $urlId)) {
                if ($urlId == null) {
                    $urlId = 'NULL';
                }
                if ($on) {
                    $query .= 'SELECT subscribe(' . $urlGroupId . ',' . $userId .
                        ',4,' . $urlId . ')';

                } else {
                    $query .= 'SELECT unsubscribe(' . $urlGroupId . ',' .
                        $userId . ',' . $urlId . ')';
                }
                $stmt = $this->pdo->prepare($query);
                $stmt->execute();
            }
            $this->pdo->commit();
            return true;
        } catch (ValidationException $ve) {
            $this->pdo->rollBack();
            throw new ValidationException($ve);
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }

    /**
     * isSubscription
     *
     * @param $urlGroupId
     *
     * @return bool
     * @throws \Exception
     */
    public function isSubscription($urlGroupId, $urlId)
    {
        $query = '';
        try {
            $query = 'SELECT url_group_id FROM url_group ';
            $query .= ' WHERE is_subscription AND url_group_id = :urlGroupId ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':urlGroupId', $urlGroupId, \PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
                throw new ValidationException('url group cannot be subscribed');
            }
            if ($urlId != null) {
                $query =
                    'SELECT url_group_id FROM url_group WHERE url_group_id = ';
                $query .= '(SELECT url_group_id FROM url where url_id = :urlid) AND is_subscription ';
                $stmt = $this->pdo->prepare($query);
                $stmt->bindValue(':urlid', $urlId, \PDO::PARAM_INT);
                $stmt->execute();
                if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
                    return true;
                }
            } else {
                return true;
            }
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
        throw new ValidationException('url group cannot be subscribed');
    }

}
