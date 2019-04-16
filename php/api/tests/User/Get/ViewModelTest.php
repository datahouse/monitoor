<?php

namespace Datahouse\MON\Tests\User\Get;

use Datahouse\MON\Types\Gen\User;
use Datahouse\MON\User\Get\ViewModel;
use Datahouse\MON\Exception\KeyNotFoundException;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\Types\Gen\Error;

/**
 * Class ViewModelTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModelTest extends \PHPUnit_Framework_TestCase
{

    private $id = 1;
    private $firstName = 'Data';
    private $lastName = 'House';
    private $mobile = '078888888';
    private $email = 'pem@datahouse.ch';
    private $company = 'datahouse';

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\User\Get\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('readUser')
                  ->willReturn($this->getUser());
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setUserId($this->id);

        $user = $viewModel->getData();
        $this->assertEquals($this->id, $user->getId());
        $this->assertEquals($this->firstName, $user->getFirstName());
        $this->assertEquals($this->lastName, $user->getLastName());
        $this->assertEquals($this->mobile, $user->getMobile());
        $this->assertEquals($this->email, $user->getEmail());
        $this->assertEquals($this->company, $user->getCompany());

        $this->assertTrue($viewModel->getStatus() == '200');

        $viewModel->setUserId(0);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');

        // general exception
        $mockModel->method('readUser')
                  ->will($this->throwException(new \Exception()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setUserId($this->id);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
        //permission Exception
        $mockModel->method('readUser')
                  ->will($this->throwException(new PermissionException()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setUserId($this->id);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '403');
        //not found Exception
        $mockModel->method('readUser')
                  ->will($this->throwException(new KeyNotFoundException()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setUserId($this->id);
        $error = $viewModel->getData();
        $this->assertTrue($error instanceof Error);
        $this->assertNotNull($error->getCode());
        $this->assertTrue(count($error->getMsg()) > 0);
        $this->assertTrue($viewModel->getStatus() == '404');
    }

    /**
     * getUser
     *
     *
     * @return User
     */
    private function getUser()
    {
        $user = new User();
        $user->setId($this->id);
        $user->setFirstName($this->firstName);
        $user->setLastName($this->lastName);
        $user->setMobile($this->mobile);
        $user->setEmail($this->email);
        $user->setCompany($this->company);
        return $user;
    }
}
