<?php

namespace Datahouse\MON\Tests\Change\Get;

use Datahouse\MON\Change\Get\ViewModel;
use Datahouse\MON\Exception\KeyNotFoundException;
use Datahouse\MON\Types\Gen\ChangeItem;

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
        $mockModel = $this->getMockBuilder('Datahouse\MON\Change\Get\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('getChange')
                  ->willReturn(new ChangeItem());
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setChangeHash('1111111111');
        $res = $viewModel->getData();
        $this->assertNotNull($res);

        // validation exception
        $viewModel->setChangeHash(null);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');

        // general exception
        $mockModel->method('getChange')
             ->will($this->throwException(new \Exception()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setChangeHash('1111111111');
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');

        //permission Exception
        $mockModel = $this->getMockBuilder('Datahouse\MON\Change\Get\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('getChange')
                  ->will($this->throwException(new KeyNotFoundException()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setChangeHash('1111111111');
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '404');
    }
}
