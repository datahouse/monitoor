<?php

namespace Datahouse\MON\Tests\Urlgroup\Listing;

use Datahouse\MON\Types\Gen\UrlGroup;
use Datahouse\MON\Types\Gen\UrlGroupList;
use Datahouse\MON\Urlgroup\Listing\ViewModel;
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

    private $id = 1;
    private $title = 'Datahouse';

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\Urlgroup\Listing\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('readUrlGroupList')
                  ->willReturn($this->getList());
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setPagingAndSorting(1, 5, '-title');
        $viewModel->setUserId(1);
        $viewModel->setUrlGroup(new UrlGroup());

        $urlList = $viewModel->getData();
        $this->assertTrue(count($urlList->getUrlGroupItems()) > 0);
        $this->assertTrue($urlList->getCount() > 0);
        $this->assertTrue($viewModel->getStatus() == '200');

        $url = $urlList->getUrlGroupItems()[0];
        $this->assertEquals($this->id, $url->getId());
        $this->assertEquals($this->title, $url->getTitle());

        // general exception
        $mockModel->method('readUrlGroupList')
             ->will($this->throwException(new \Exception()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setUrlGroup(new UrlGroup());
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
        //permission Exception
        $mockModel->method('readUrlGroupList')
                  ->will($this->throwException(new PermissionException()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setUrlGroup(new UrlGroup());
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '403');
    }

    /**
     * getList
     *
     *
     * @return UrlGroupList
     */
    private function getList()
    {
        $urlListResult = new UrlGroupList();
        $urlList = array();
        $url = new UrlGroup();
        $url->setId($this->id);
        $url->setTitle($this->title);
        $urlList[] = $url;

        $url = new UrlGroup();
        $url->setId(2);
        $url->setTitle('Wüest & Partner');
        $urlList[] = $url;

        $urlListResult->setUrlGroupItems($urlList);
        $urlListResult->setCount(count($urlList));
        return $urlListResult;
    }
}
