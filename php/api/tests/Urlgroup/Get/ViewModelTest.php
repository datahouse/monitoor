<?php

namespace Datahouse\MON\Tests\Urlgroup\Get;

use Datahouse\MON\Types\Gen\UrlGroup;
use Datahouse\MON\Urlgroup\Get\ViewModel;
use Datahouse\MON\Exception\KeyNotFoundException;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\Types\Gen\Error;
use Datahouse\MON\Types\Gen\Url;

/**
 * Class ViewModelTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModelTest extends \PHPUnit_Framework_TestCase
{

    private $urlId = 12;
    private $url = 'http://www.datahouse.ch/';
    private $urlTitle = 'Datahouse';
    private $urlGroupId = 11;
    private $urlGroupTitle = 'Datahouse';

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\Urlgroup\Get\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('readUrlGroup')
                  ->willReturn($this->getUrlGroup());

        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                  ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setId($this->urlGroupId);

        $urlGroup = $viewModel->getData();
        $this->assertEquals($this->urlGroupId, $urlGroup->getId());
        $this->assertEquals($this->urlGroupTitle, $urlGroup->getTitle());
        foreach ($urlGroup->getUrls() as $url) {
            $this->assertEquals($this->url, $url->getUrl());
            $this->assertEquals($this->urlId, $url->getId());
            $this->assertEquals($this->urlTitle, $url->getTitle());
        }

        $this->assertTrue($viewModel->getStatus() == '200');

        // general exception
        $mockModel->method('readUrlGroup')
                  ->will($this->throwException(new \Exception()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('hasUrlGroupReadAccess')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setId($this->urlGroupId);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
        //permission Exception
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('hasUrlGroupReadAccess')
                       ->will($this->throwException(new PermissionException()));
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setId($this->urlGroupId);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '403');
        //not found Exception
        $mockModel->method('readUrlGroup')
                  ->will($this->throwException(new KeyNotFoundException()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('hasUrlGroupReadAccess')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setId($this->urlGroupId);
        $error = $viewModel->getData();
        $this->assertTrue($error instanceof Error);
        $this->assertNotNull($error->getCode());
        $this->assertTrue(count($error->getMsg()) > 0);
        $this->assertTrue($viewModel->getStatus() == '404');
    }

    /**
     * getUrlGroup
     *
     *
     * @return UrlGroup
     */
    private function getUrlGroup()
    {
        $urlGroup = new UrlGroup();
        $urlGroup->setId($this->urlGroupId);
        $urlGroup->setTitle($this->urlGroupTitle);
        $url = new Url();
        $url->setTitle($this->urlTitle);
        $url->setId($this->urlId);
        $url->setUrl($this->url);
        $urlGroup->setUrls(array($url));
        return $urlGroup;
    }
}
