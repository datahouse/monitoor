<?php

namespace Datahouse\MON\Tests\Report\Change;

use Datahouse\MON\Report\Change\ViewModel;
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

    private $id = 33;
    private $name = 'test';

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel =
            $this->getMockBuilder('Datahouse\MON\Report\Change\Model')
                 ->disableOriginalConstructor()
                 ->getMock();
        $mockModel->method('readReportList')
                  ->willReturn($this->getList());
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setUrlGroupId(1);

        $reportList = $viewModel->getData();
        $this->assertTrue(count($reportList) > 0);
        $this->assertTrue($viewModel->getStatus() == '200');

        $report = $reportList[0];
        $this->assertTrue($report['id'] == $this->id);
        $this->assertTrue($report['title'] == $this->name);
        $this->assertTrue(count($report['values']) > 0);

        // general exception
        $mockModel->method('readReportList')
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
        $mockModel->method('readReportList')
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
        $list = array();
        $list[] = array(
            'id' => $this->id,
            'title' => $this->name,
            'values' => array(
                array('date' => '2015-07-07', 'count' => 200),
                array('date' => '2015-06-30', 'count' => 122),
                array('date' => '2015-06-23', 'count' => 120),
                array('date' => '2015-06-16', 'count' => 100),
                array('date' => '2015-06-09', 'count' => 50),
                array('date' => '2015-06-02', 'count' => 61)
            )
        );
        return $list;
    }
}
