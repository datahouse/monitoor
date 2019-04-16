<?php

namespace Datahouse\MON\Tests\Urlgroup\Add;

use Datahouse\MON\Urlgroup\Add\ViewModel;
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
        $mockModel = $this->getMockBuilder('Datahouse\MON\Urlgroup\Add\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('createUrlGroup')
                  ->willReturn($this->id);
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setUrlGroup($this->getUrlGroup());

        $urlId = $viewModel->getData();
        $this->assertNotNull($urlId > 0);
        $this->assertTrue($viewModel->getStatus() == '200');

        // general exception
        $mockModel->method('createUrlGroup')
                  ->will($this->throwException(new \Exception()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setUrlGroup($this->getUrlGroup());
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
        //permission Exception
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
            ->will($this->throwException(new PermissionException()));
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setUrlGroup($this->getUrlGroup());
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '403');
        //validation Exception
        $mockModel->method('createUrlGroup')
                  ->willReturn(1);
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setUrlGroup($this->getUrlGroup(false));
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');
    }

    /**
     * getUrlGroup
     *
     * @param bool $valid the valid flag
     *
     * @return UrlGroup
     */
    private function getUrlGroup($valid = true)
    {
        $urlGroup = new UrlGroup();
        $urlGroup->setId($this->id);
        if ($valid) {
            $urlGroup->setTitle($this->title);
        }
        return $urlGroup;
    }
}
