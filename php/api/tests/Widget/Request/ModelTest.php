<?php

namespace Datahouse\MON\Tests\Widget\Request;

use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Widget\Request\Model;

/**
 * Class ModelTest
 *
 * @package     Test
 * @author      Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ModelTest extends AbstractModel
{

    private $email = 'pem@datahouse.ch';
    private $groupId = 4;

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $model = new Model($this->getPDO());
        $this->assertTrue($model->isAllowedUrlGroup($this->groupId));
        $query = 'delete from mon_user ';
        $query .= ' where user_email = :email ';
        $stmt = $this->getPDO()->prepare($query);
        $stmt->bindValue(':email', $this->email, \PDO::PARAM_STR);
        $stmt->execute();
        $hash = $model->addWidgetToUser($this->groupId, $this->email);
        $this->assertNotNull($hash);
    }
}
