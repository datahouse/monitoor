<?php

namespace Datahouse\MON\Tests\Url\Get;

use Datahouse\MON\Types\Gen\Frequency;
use Datahouse\MON\Url\Get\ViewModel;
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

    private $id = 12;
    private $url = 'http://www.datahouse.ch/';
    private $title = 'Datahouse';
    private $frq = 2;

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\Url\Get\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('readUrl')
                  ->willReturn($this->getUrl());

        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                  ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setId($this->id);

        $url = $viewModel->getData();
        $this->assertEquals($this->id, $url->getId());
        $this->assertEquals($this->title, $url->getTitle());
        $this->assertEquals($this->url, $url->getUrl());
        $this->assertEquals($this->frq, $url->getFrequency());
        $this->assertTrue(count($url->getFrequencyOptions()) > 0);

        $this->assertTrue($viewModel->getStatus() == '200');

        // general exception
        $mockModel->method('readUrl')
                  ->will($this->throwException(new \Exception()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('hasUrlReadAccess')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setId($this->id);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
        //permission Exception
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('hasUrlReadAccess')
                       ->will($this->throwException(new PermissionException()));
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setId($this->id);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '403');
        //not found Exception
        $mockModel->method('readUrl')
                  ->will($this->throwException(new KeyNotFoundException()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('hasUrlReadAccess')
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
     * getUrl
     *
     *
     * @return Url
     */
    private function getUrl()
    {
        $url = new Url();
        $url->setId($this->id);
        $url->setUrl($this->url);
        $url->setTitle($this->title);
        $url->setFrequency($this->frq);
        $freq = new Frequency();
        $freq->setId($this->id);
        $freq->setTitle($this->title);
        $url->setFrequencyOptions(array($freq));
        return $url;
    }
}
