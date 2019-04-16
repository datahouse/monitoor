<?php

namespace Datahouse\MON\Tests\User\Password;

use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\User\Password\Model;

/**
 * Class ModelTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ModelTest extends AbstractModel
{

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $model = new Model($this->getPDO());
        $model->changePwd('datahouse', 'datahouse', 1);
        $this->assertTrue(true);

        $this->setExpectedException(
            'Datahouse\MON\Exception\OldPasswordIncorrectException'
        );
        $model->changePwd('datahouse', 'wrong_password', 1);
        $this->assertTrue(true);
    }
}
