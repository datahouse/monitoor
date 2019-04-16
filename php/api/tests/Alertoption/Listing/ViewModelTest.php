<?php

namespace Datahouse\MON\Tests\Alertoption\Listing;

use Datahouse\MON\Alertoption\Listing\ViewModel;
use Datahouse\MON\Exception\PermissionException;
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

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel =
            $this->getMockBuilder('Datahouse\MON\Alertoption\Listing\Model')
                 ->disableOriginalConstructor()
                 ->getMock();
        $mockModel->method('readAlertOptionList')
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

        $option = $alertList[0];
        $this->assertTrue($option->getId() > 0);
        $this->assertNotNull($option->getTitle());

        // general exception
        $mockModel->method('readAlertOptionList')
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
        $mockModel->method('readAlertOptionList')
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
        $optionList = array();
        $option1 = new AlertOption();
        $option1->setId(1);
        $option1->setTitle('opt1');
        $optionList[] = $option1;
        $option2 = new AlertOption();
        $option2->setId(1);
        $option2->setTitle('opt2');
        $optionList[] = $option2;

        return $optionList;
    }
}
