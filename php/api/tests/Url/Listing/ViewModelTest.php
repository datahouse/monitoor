<?php

namespace Datahouse\MON\Tests\Url\Listing;

use Datahouse\MON\Types\Gen\Url;
use Datahouse\MON\Types\Gen\UrlList;
use Datahouse\MON\Url\Listing\ViewModel;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Permission\PermissionHandler;

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
    private $url = 'http://www.datahouse.ch/';
    private $title = 'Datahouse';

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\Url\Listing\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('readUrlList')
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
        $viewModel->setUrl(new Url());

        $urlList = $viewModel->getData();
        $this->assertTrue(count($urlList->getUrlItems()) > 0);
        $this->assertTrue($urlList->getCount() > 0);
        $this->assertTrue($viewModel->getStatus() == '200');

        $url = $urlList->getUrlItems()[0];
        $this->assertEquals($this->id, $url->getId());
        $this->assertEquals($this->title, $url->getTitle());
        $this->assertEquals($this->url, $url->getUrl());

        // general exception
        $mockModel->method('readUrlList')
             ->will($this->throwException(new \Exception()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setUrl(new Url());
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
        //permission Exception
        $mockModel->method('readUrlList')
                  ->will($this->throwException(new PermissionException()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setUrl(new Url());
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '403');
    }

    /**
     * getList
     *
     *
     * @return UrlList
     */
    private function getList()
    {
        $urlListResult = new UrlList();
        $urlList = array();
        $url = new Url();
        $url->setId($this->id);
        $url->setUrl($this->url);
        $url->setTitle($this->title);
        $urlList[] = $url;

        $url = new Url();
        $url->setId(2);
        $url->setUrl('http://www.wuestundpartner.com/');
        $url->setTitle('Wüest & Partner');
        $urlList[] = $url;

        $urlListResult->setUrlItems($urlList);
        $urlListResult->setCount(count($urlList));
        return $urlListResult;
    }
}
