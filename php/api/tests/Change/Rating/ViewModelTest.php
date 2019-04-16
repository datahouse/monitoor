<?php

namespace Datahouse\MON\Tests\Change\Rating;

use Datahouse\MON\Change\Rating\ViewModel;
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
        $mockModel = $this->getMockBuilder('Datahouse\MON\Change\Rating\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('insertRating')
                  ->willReturn(true);
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setAlertId(1);
        $viewModel->setChangeId(1);
        $viewModel->setRating(1);
        $viewModel->setUserId(1);
        $this->assertTrue($viewModel->getData());

        // validation exception
        $viewModel->setAlertId(null);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');

        // general exception
        $mockModel->method('insertRating')
             ->will($this->throwException(new \Exception()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setAlertId(1);
        $viewModel->setChangeId(1);
        $viewModel->setRating(1);
        $viewModel->setUserId(1);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');

        //permission Exception
        $mockModel->method('insertRating')
                  ->willReturn(true);
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
            ->will($this->throwException(new PermissionException()));
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setAlertId(1);
        $viewModel->setChangeId(1);
        $viewModel->setRating(1);
        $viewModel->setUserId(1);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '403');
    }
}
