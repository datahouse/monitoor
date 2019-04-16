<?php

namespace Datahouse\MON\Tests\Url\Add;

use Datahouse\MON\I18\I18;
use Datahouse\MON\Types\Gen\UrlGroup;
use Datahouse\MON\Url\Add\ViewModel;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Permission\PermissionHandler;
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
    private $frequency = 1;

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\Url\Add\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('createUrl')
                  ->willReturn($this->id);
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock, new I18());
        $viewModel->setLang(1);
        $viewModel->setUrls(array($this->getUrl()));

        $urlId = $viewModel->getData();
        $this->assertNotNull($urlId > 0);
        $this->assertTrue($viewModel->getStatus() == '200');

        // general exception
        $mockModel->method('createUrl')
                  ->will($this->throwException(new \Exception()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock, new I18());
        $viewModel->setUrls(array($this->getUrl()));
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
        //permission Exception
        $mockModel->method('createUrl')
                  ->will($this->throwException(new PermissionException()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock, new I18());
        $viewModel->setUrls(array($this->getUrl()));
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '403');
        //validation Exception
        $mockModel->method('createUrl')
                  ->willReturn(1);
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock, new I18());
        $viewModel->setUrls(array($this->getUrl(false)));
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');
    }

    /**
     * getAlert
     *
     * @param bool $valid the valid flag
     *
     * @return Url
     */
    private function getUrl($valid = true)
    {
        $url = new Url();
        $url->setId($this->id);
        if ($valid) {
            $url->setUrl($this->url);
        }
        $url->setUrlGroupId(1);
        $url->setTitle($this->title);
        $url->setFrequency($this->frequency);
        return $url;
    }
}
