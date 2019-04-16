<?php

namespace Datahouse\MON\Tests\Frequency\Listing;

use Datahouse\MON\Frequency\Listing\ViewModel;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Types\Gen\Frequency;

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
            $this->getMockBuilder('Datahouse\MON\Frequency\Listing\Model')
                 ->disableOriginalConstructor()
                 ->getMock();
        $mockModel->method('readFrequencyList')
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

        $frequency = $alertList[0];
        $this->assertTrue($frequency->getId() > 0);
        $this->assertNotNull($frequency->getTitle());

        // general exception
        $mockModel->method('readFrequencyList')
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
        $mockModel->method('readFrequencyList')
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
        $frequencyList = array();
        $frequency1 = new Frequency();
        $frequency1->setId(1);
        $frequency1->setTitle('freq1');
        $frequencyList[] = $frequency1;
        $frequency2 = new Frequency();
        $frequency2->setId(1);
        $frequency2->setTitle('freq1');
        $frequencyList[] = $frequency2;

        return $frequencyList;
    }
}
