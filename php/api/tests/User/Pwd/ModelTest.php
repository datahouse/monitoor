<?php

namespace Datahouse\MON\Tests\User\Pwd;

use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\User\Pwd\Model;

/**
 * Class ModelTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ModelTest extends AbstractModel
{

    private $email = 'peter.mueller@datahouse.ch';


    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $model = new Model($this->getPDO());
        $modelHash = new \Datahouse\MON\User\Recover\Model($this->getPDO());
        $hash = $modelHash->createPwdRecovery($this->email);
        $this->assertNotNull($hash);
        $id = $model->changePwd('datahouse', $hash);
        $this->assertTrue($id == 1);
    }
}
