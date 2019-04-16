<?php

namespace Datahouse\MON\Tests\Register\Add;

use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Register\Add\Model;

/**
 * Class ModelTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ModelTest extends AbstractModel
{

    private $email = 'peter@datahouse.ch';
    private $pwd = 'mypwd124';


    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $model = new Model($this->getPDO());
        $hash = $model->createUser($this->email, $this->pwd, 'test', 'user', 'datahouse', 1, null);
        $this->assertNotNull($hash);

        $query = 'select user_id from  user_activation ';
        $query .= ' where user_activation_hash = :hash ';
        $stmt = $this->getPDO()->prepare($query);
        $stmt->bindValue(':hash', $hash->getHash(), \PDO::PARAM_STR);
        $stmt->execute();
        if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $userId = $res['user_id'];

            $query = 'select account_id from  account ';
            $query .= ' where user_id = :userId ';
            $stmt = $this->getPDO()->prepare($query);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_STR);
            $stmt->execute();
            if ($res = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $this->assertTrue(true);
            } else {
                $this->assertTrue(false);
            }
            $query = 'delete from mon_user ';
            $query .= ' where user_id = :userId ';
            $stmt = $this->getPDO()->prepare($query);
            $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }

    }
}
