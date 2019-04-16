<?php

namespace Datahouse\MON\Tests\User\Pushtoken;

use Datahouse\MON\Types\PushToken;
use Datahouse\MON\User\Pushtoken\ViewModel;
use Datahouse\MON\Exception\PermissionException;

/**
 * Class ViewModelTest
 *
 * @package     Test
 * @author      Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModelTest extends \PHPUnit_Framework_TestCase
{

    private $token = 'dklsjfklasjfkljasf';
    private $platform = 0;
    private $userId = 1;

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\User\Pushtoken\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('handlePushToken')
                  ->willReturn(true);
        $permissionMock =
            $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                 ->disableOriginalConstructor()
                 ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setUserId($this->userId);
        $viewModel->setPushToken($this->getPushToken());

        $this->assertTrue($viewModel->getData());
        $this->assertTrue($viewModel->getStatus() == '200');

        // general exception
        $mockModel->method('handlePushToken')
                  ->will($this->throwException(new \Exception()));
        $permissionMock =
            $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                 ->disableOriginalConstructor()
                 ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setUserId($this->userId);
        $viewModel->setPushToken($this->getPushToken());
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
        //permission Exception
        $mockModel->method('handlePushToken')
                  ->willReturn(true);
        $permissionMock =
            $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                 ->disableOriginalConstructor()
                 ->getMock();
        $permissionMock->method('assertRole')
                       ->will($this->throwException(new PermissionException()));
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setUserId($this->userId);
        $viewModel->setPushToken($this->getPushToken());
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '403');
        //validation Exception
        $permissionMock =
            $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                 ->disableOriginalConstructor()
                 ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setUserId($this->userId);
        $viewModel->setPushToken($this->getPushToken(false));
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');
    }

    /**
     * getPushToken
     *
     * @param bool $valid the valid flag
     *
     * @return PushToken
     */
    private function getPushToken($valid = true)
    {
        $pushtoken = new PushToken();
        $pushtoken->setUserId($this->userId);
        $pushtoken->setDenied(true);
        $pushtoken->setPlatform($this->platform);
        if ($valid) {
            $pushtoken->setToken($this->token);
        }
        return $pushtoken;
    }
}
