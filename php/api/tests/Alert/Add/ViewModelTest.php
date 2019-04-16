<?php

namespace Datahouse\MON\Tests\Alert\Add;

use Datahouse\MON\Alert\Add\ViewModel;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Types\Gen\Alert;
use Datahouse\MON\Types\Gen\AlertShaping;
use Datahouse\MON\Types\Gen\AlertOption;

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
    private $urlGroup = array('id' => 1);
    private $urlInvalid = array('ids' => 1);
    private $typeEmailId = 1;
    private $cycleId = 1;

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\Alert\Add\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('createAlert')
                  ->willReturn($this->id);
        $permissionMock =
            $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                 ->disableOriginalConstructor()
                 ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setAlert($this->getAlert());

        $alertId = $viewModel->getData();
        $this->assertNotNull($alertId > 0);
        $this->assertTrue($viewModel->getStatus() == '200');

        // general exception
        $mockModel->method('createAlert')
                  ->will($this->throwException(new \Exception()));
        $permissionMock =
            $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                 ->disableOriginalConstructor()
                 ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setAlert($this->getAlert());
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
        //permission Exception
        $mockModel->method('createAlert')
                  ->will($this->throwException(new PermissionException()));
        $permissionMock =
            $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                 ->disableOriginalConstructor()
                 ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setAlert($this->getAlert());
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '403');
        //validation Exception
        $mockModel->method('createAlert')
                  ->willReturn(1);
        $permissionMock =
            $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                 ->disableOriginalConstructor()
                 ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setAlert($this->getAlert(false));
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');
    }

    /**
     * getAlert
     *
     * @param bool $valid the valid flag
     *
     * @return Alert
     */
    private function getAlert($valid = true)
    {
        $alert = new Alert();
        $alert->setId($this->id);
        if ($valid) {
            $alert->setUrlGroup($this->urlGroup);
        } else {
            $alert->setUrlGroup($this->urlInvalid);
        }
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
