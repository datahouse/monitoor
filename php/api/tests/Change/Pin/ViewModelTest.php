<?php

namespace Datahouse\MON\Tests\Change\Pin;

use Datahouse\MON\Change\Pin\ViewModel;
use Datahouse\MON\Exception\PermissionException;

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
        $mockModel = $this->getMockBuilder('Datahouse\MON\Change\Pin\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('addFavorite')
                  ->willReturn(true);
        $mockModel->method('checkUserChange')
                  ->willReturn(true);
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setChangeId(1);
        $viewModel->setUserId(1);
        $this->assertTrue($viewModel->getData());

        // validation exception
        $viewModel->setChangeId(null);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');

        // general exception
        $mockModel->method('addFavorite')
             ->will($this->throwException(new \Exception()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setChangeId(1);
        $viewModel->setUserId(1);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');

        //permission Exception
        $mockModel->method('addFavorite')
                  ->willReturn(true);
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
            ->will($this->throwException(new PermissionException()));
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setChangeId(1);
        $viewModel->setUserId(1);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '403');
    }
}
