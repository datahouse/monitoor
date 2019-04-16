<?php

namespace Datahouse\MON\Tests\User\Update;

use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\User\Update\Model;
use Datahouse\MON\Types\Gen\User;

/**
 * Class ModelTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ModelTest extends AbstractModel
{

    private $firstName = 'Peter';
    private $lastName = 'Müller';
    private $email = 'peter.mueller@datahouse.ch';
    private $mobile = '0788888888';
    private $company = 'datahouse';

    private $firstName1 = 'firstname';
    private $lastName1 = 'lastname';
    private $email1 = 'peter@datahouse.ch';
    private $mobile1 = 'mobilee';
    private $company1 = 'datahouse';

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $model = new Model($this->getPDO());
        $user = new User();
        $user->setMobile($this->mobile1);
        $user->setEmail($this->email1);
        $user->setFirstName($this->firstName1);
        $user->setLastName($this->lastName1);
        $user->setCompany($this->company1);
        $user->setId(1);
        $this->assertTrue($model->updateUser($user));

        $modelGet = new \Datahouse\MON\User\Get\Model($this->getPDO());
        $user1 = $modelGet->readUser(1);

        $this->assertEquals($this->firstName1, $user1->getFirstName());
        $this->assertEquals($this->lastName1, $user1->getLastName());
        $this->assertEquals($this->email1, $user1->getEmail());
        $this->assertEquals($this->mobile1, $user1->getMobile());
        $this->assertEquals($this->company1, $user1->getCompany());

        $user->setMobile($this->mobile);
        $user->setEmail($this->email);
        $user->setFirstName($this->firstName);
        $user->setLastName($this->lastName);
        $user->setCompany($this->company);
        $this->assertTrue($model->updateUser($user));

        $user1 = $modelGet->readUser(1);
        $this->assertEquals($this->firstName, $user1->getFirstName());
        $this->assertEquals($this->lastName, $user1->getLastName());
        $this->assertEquals($this->email, $user1->getEmail());
        $this->assertEquals($this->mobile, $user1->getMobile());
        $this->assertEquals($this->company, $user1->getCompany());

        $this->assertFalse($model->isUniqueEmail('peter.mueller@datahouse.ch', 11));
        $this->assertTrue($model->isUniqueEmail('peter.mueller@datahouse.ch', 1));
    }
}
