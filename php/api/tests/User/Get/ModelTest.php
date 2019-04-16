<?php

namespace Datahouse\MON\Tests\User\Get;

use Datahouse\MON\Exception\KeyNotFoundException;
use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Types\Gen\User;
use Datahouse\MON\User\Get\Model;

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
        $id = 1;
        $user = $model->readUser(1);
        $this->assertTrue($user instanceof User);
        $this->assertTrue(is_numeric($user->getId()));
        $this->assertTrue(intval($user->getId()) == $id);
        $this->assertNotNull($user->getEmail());
        $this->assertNotNull($user->getFirstName());
        $this->assertNotNull($user->getLastName());
        $this->assertNotNull($user->getMobile());
        $this->assertNotNull($user->getCompany());

        try {
            $model->readUser(0);
        } catch (KeyNotFoundException $ke) {
            $this->assertTrue(true);
        }
    }
}
