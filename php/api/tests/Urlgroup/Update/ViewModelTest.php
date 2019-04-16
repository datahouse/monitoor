<?php

namespace Datahouse\MON\Tests\Urlgroup\Update;

use Datahouse\MON\Urlgroup\Update\ViewModel;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Types\Gen\UrlGroup;

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
    private $title = 'Datahouse';

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\Urlgroup\Update\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('updateUrlGroup')
                  ->willReturn(true);
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setId($this->id);
        $viewModel->setUrlGroup($this->getUrlGroup());

        $this->assertTrue($viewModel->getData());
        $this->assertTrue($viewModel->getStatus() == '200');

        // general exception
        $mockModel->method('updateUrlGroup')
                  ->will($this->throwException(new \Exception()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('hasUrlGroupWriteAccess')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setId($this->id);
        $viewModel->setUrlGroup($this->getUrlGroup());
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
        //permission Exception
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('hasUrlGroupWriteAccess')
            ->will($this->throwException(new PermissionException()));
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setId($this->id);
        $viewModel->setUrlGroup($this->getUrlGroup());
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '403');
        //validation Exception
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('hasUrlWriteAccess')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setId($this->id);
        $viewModel->setUrlGroup($this->getUrlGroup(false));
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');
    }

    /**
     * getUrl
     *
     * @param bool $valid the valid flag
     *
     * @return UrlGroup
     */
    private function getUrlGroup($valid = true)
    {
        $url = new UrlGroup();
        $url->setId($this->id);
        if ($valid) {
            $url->setTitle($this->title);
        }
        return $url;
    }
}
