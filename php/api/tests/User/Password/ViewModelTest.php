<?php

namespace Datahouse\MON\Tests\User\Password;

use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Types\Gen\Token;
use Datahouse\MON\Types\PwdChange;
use Datahouse\MON\User\Password\ViewModel;
use Datahouse\MON\Permission\PermissionHandler;

/**
 * Class ViewModelTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModelTest extends \PHPUnit_Framework_TestCase
{

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $token = new Token();
        $token->setId('sfasfasfaskfoajkfiaweojfklsdmfalösdfkpasfäko');
        $mockModel = $this->getMockBuilder('Datahouse\MON\User\Password\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('changePwd')
                  ->willReturn(array('token' => $token));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $pwd = new PwdChange();
        $pwd->setOldPwd('QQasfasdf');
        $pwd->setPwd1('testestT1');
        $pwd->setPwd2('testestT1');
        $viewModel->setPwdChange($pwd);
        $token = $viewModel->getData();
        $this->assertNotNull($token);
        $this->assertNotEmpty($token['token']->getId());
        $this->assertTrue(strlen($token['token']->getId()) > 100);


        // general exception
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $mockModel->method('changePwd')
                  ->will($this->throwException(new \Exception()));
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setPwdChange($pwd);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');

        //validation Exception
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $pwd->setPwd1('test');
        $viewModel->setPwdChange($pwd);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');

        //permission
        $mockModel->method('changePwd')
                  ->will($this->throwException(new PermissionException()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $pwd->setPwd1('testestT1');
        $viewModel->setPwdChange($pwd);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '403');

        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $pwd->setPwd1('testtesttestT');
        $viewModel->setPwdChange($pwd);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');
    }
}
