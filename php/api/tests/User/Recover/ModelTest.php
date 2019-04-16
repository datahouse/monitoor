<?php

namespace Datahouse\MON\Tests\User\Recover;

use Datahouse\MON\Exception\KeyNotFoundException;
use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\User\Recover\Model;

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
        $hash = $model->createPwdRecovery($this->email);
        $this->assertNotNull($hash);
    }
}
