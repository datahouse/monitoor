<?php

namespace Datahouse\MON\Tests\User\Recover;

use Datahouse\MON\I18\I18;
use Datahouse\MON\User\Recover\ViewModel;

/**
 * Class ViewModelTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModelTest extends \PHPUnit_Framework_TestCase
{

    private $email = 'pem@datahouse.ch';

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\User\Recover\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('createPwdRecovery')
                  ->willReturn('dsfjaklfjasdafasfsafk');
        $viewModel =
            new ViewModel($mockModel, new I18());
        $viewModel->setEmail($this->email);

        $this->assertTrue($viewModel->getData());
        $this->assertTrue($viewModel->getStatus() == '200');

        // general exception
        $mockModel->method('createPwdRecovery')
                  ->will($this->throwException(new \Exception()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, new I18());
        $viewModel->setEmail($this->email);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
        //validation Exception
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, new I18());
        $viewModel->setEmail('datahouse');
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');
    }
}
