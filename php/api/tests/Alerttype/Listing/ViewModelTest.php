<?php

namespace Datahouse\MON\Tests\Alerttype\Listing;

use Datahouse\MON\Alerttype\Listing\ViewModel;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\Types\Gen\AlertType;

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
        $mockModel =
            $this->getMockBuilder('Datahouse\MON\Alerttype\Listing\Model')
                 ->disableOriginalConstructor()
                 ->getMock();
        $mockModel->method('readAlertTypeList')
                  ->willReturn($this->getList());
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);

        $alertList = $viewModel->getData();
        $this->assertTrue(count($alertList) > 0);
        $this->assertTrue($viewModel->getStatus() == '200');

        $alertType = $alertList[0];
        $this->assertTrue($alertType->getId() > 0);
        $this->assertNotNull($alertType->getTitle());
        $this->assertTrue(count($alertType->getCycle()) > 0);
        $this->assertTrue($alertType->getCycle()[0]['id'] > 0);
        $this->assertEquals('immediate', $alertType->getCycle()[0]['title']);

        // general exception
        $mockModel->method('readAlertTypeList')
                  ->will($this->throwException(new \Exception()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
        //permission Exception
        $mockModel->method('readAlertTypeList')
                  ->will($this->throwException(new PermissionException()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '403');
    }

    /**
     * getList
     *
     *
     * @return array
     */
    private function getList()
    {
        $alertTypeList = array();

        $cycle1 = array('id' => 1, 'title' => 'immediate');
        $cycle2 = array('id' => 2, 'title' => 'daily');

        $alertType = new AlertType();
        $alertType->setId(1);
        $alertType->setTitle('sms');
        $alertType->setCycle(array($cycle1));
        $alertTypeList[] = $alertType;

        $alertType = new AlertType();
        $alertType->setId(2);
        $alertType->setTitle('email');
        $alertType->setCycle(array($cycle1, $cycle2));
        $alertTypeList[] = $alertType;

        return $alertTypeList;
    }
}
