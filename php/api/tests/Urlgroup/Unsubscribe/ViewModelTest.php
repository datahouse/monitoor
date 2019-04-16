<?php

namespace Datahouse\MON\Tests\Urlgroup\Unsubscribe;

use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Urlgroup\Unsubscribe\ViewModel;

/**
 * Class ViewModelTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModelTest extends \PHPUnit_Framework_TestCase
{

    private $id = 12;
    private $invalidId = 'id';


    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\Urlgroup\Unsubscribe\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('hasUrlGroupReadAccess')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setId($this->id);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '200');

        // validation exception
        $viewModel->setId($this->invalidId);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');

        // validation exception
        $mockModel = $this->getMockBuilder('Datahouse\MON\Urlgroup\Unsubscribe\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('isSubscription')
                  ->will($this->throwException(new ValidationException()));
        $viewModel->setId(1);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');
        //permission Exception
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('hasUrlGroupReadAccess')
                  ->will($this->throwException(new PermissionException()));
        $mockModel = $this->getMockBuilder('Datahouse\MON\Urlgroup\Unsubscribe\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setId($this->id);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '403');
        //general Exception
        $mockModel->method('subscribeUrlGroup')
                  ->will($this->throwException(new \Exception()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('hasUrlGroupReadAccess')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setId($this->id);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
    }
}
