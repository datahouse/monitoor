<?php

namespace Datahouse\MON\Tests\User\Login;

use Datahouse\MON\Exception\UnauthorizedException;
use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\User\Login\Model;

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
        $userId = $model->login('peter.mueller@datahouse.ch', 'datahouse');
        $this->assertTrue(is_numeric($userId) && intval($userId) > 0);
        try {
            $model->login('email', 'datahouse');
        } catch (UnauthorizedException $pe) {
            $this->assertTrue(true);
        }
    }
}
