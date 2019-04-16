<?php

namespace Datahouse\MON\Tests\Alert\Update;

use Datahouse\MON\Alert\Update\ViewModel;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Types\Gen\Alert;
use Datahouse\MON\Types\Gen\AlertOption;
use Datahouse\MON\Types\Gen\AlertShaping;

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
    private $typeEmailId = 1;
    private $cycleId = 1;

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\Alert\Update\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('updateAlert')
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
        $viewModel->setId($this->id);
        $viewModel->setAlert($this->getAlert());

        $this->assertTrue($viewModel->getData());
        $this->assertTrue($viewModel->getStatus() == '200');

        // general exception
        $mockModel->method('updateAlert')
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
        $viewModel->setId($this->id);
        $viewModel->setAlert($this->getAlert());
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
        //permission Exception
        $mockModel->method('updateAlert')
                  ->will($this->throwException(new PermissionException()));
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
        $viewModel->setAlert($this->getAlert());
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
        $viewModel->setId($this->id);
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
