<?php

namespace Datahouse\MON\Tests\Alert\Get;

use Datahouse\MON\Alert\Get\ViewModel;
use Datahouse\MON\Exception\KeyNotFoundException;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Types\Gen\Alert;
use Datahouse\MON\Types\Gen\AlertOption;
use Datahouse\MON\Types\Gen\AlertShaping;
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

    private $id = 12;
    private $url = 'http://www.datahouse.ch/';
    private $typeEmailId = 1;
    private $cycleId = 1;

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\Alert\Get\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('readAlert')
                  ->willReturn($this->getAlert());
        $permissionMock =
            $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                 ->disableOriginalConstructor()
                 ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setId($this->id);

        $alert = $viewModel->getData();
        $this->assertEquals($this->id, $alert->getId());
        $this->assertEquals($this->url, $alert->getUrlGroup());

        $alertShaping = $alert->getAlertShapingList()[0];
        $this->assertEquals(
            $this->typeEmailId,
            $alertShaping->getAlertType()['id']
        );
        $this->assertEquals(
            $this->cycleId,
            $alertShaping->getAlertType()['cycleId']
        );
        $this->assertTrue(count($alertShaping->getKeywords()) > 0);

        $this->assertTrue($viewModel->getStatus() == '200');

        // general exception
        $mockModel->method('readAlert')
                  ->will($this->throwException(new \Exception()));
        $permissionMock =
            $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                 ->disableOriginalConstructor()
                 ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setId($this->id);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
        //permission Exception
        $permissionMock =
            $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                 ->disableOriginalConstructor()
                 ->getMock();
        $permissionMock->method('assertRole')
                       ->will($this->throwException(new PermissionException()));
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setId($this->id);
        $viewModel->getData();
        $this->assertEquals(403, $viewModel->getStatus());
        //not found Exception
        $mockModel->method('readAlert')
                  ->will($this->throwException(new KeyNotFoundException()));
        $permissionMock =
            $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                 ->disableOriginalConstructor()
                 ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setId($this->id);
        $error = $viewModel->getData();
        $this->assertTrue($error instanceof Error);
        $this->assertNotNull($error->getCode());
        $this->assertTrue(count($error->getMsg()) > 0);
        $this->assertTrue($viewModel->getStatus() == '404');
    }

    /**
     * getAlert
     *
     *
     * @return Alert
     */
    private function getAlert()
    {
        $alert = new Alert();
        $alert->setId($this->id);
        $alert->setUrlGroup($this->url);
        $alert->setAlertShapingList($this->getAlertShapingList());
        return $alert;
    }

    private function getAlertShapingList()
    {
        $alertShapingList = array();
        $alertShaping = new AlertShaping();
        $alertShaping->setAlertType(
            array('id' => $this->typeEmailId, 'cycleId' => $this->cycleId)
        );
        $alertShaping->setKeywords(array('data', 'house'));
        $alertShaping->setAlertOption($this->getAlertOption());
        $alertShapingList[] = $alertShaping;
        return $alertShapingList;
    }

    private function getAlertOption()
    {
        $alertOption = new AlertOption();
        $alertOption->setTitle("testTitle");
        $alertOption->setId(1);
        return $alertOption;
    }
}
