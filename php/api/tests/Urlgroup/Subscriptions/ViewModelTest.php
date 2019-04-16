<?php

namespace Datahouse\MON\Tests\Urlgroup\Subscriptions;

use Datahouse\MON\Types\Gen\UrlGroup;
use Datahouse\MON\Types\Gen\UrlGroupList;
use Datahouse\MON\Urlgroup\Subscriptions\ViewModel;
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

    private $urlGroupId = 11;
    private $urlGroupTitle = 'Datahouse';

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\Urlgroup\Subscriptions\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('readSubscriptionList')
                  ->willReturn($this->getUrlGroupList());

        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                  ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setUserId(1);

        $urlGroups = $viewModel->getData();
        foreach ($urlGroups->getUrlGroupItems() as $item) {
            $this->assertEquals($this->urlGroupId, $item->getId());
            $this->assertEquals($this->urlGroupTitle, $item->getTitle());
        }

        $this->assertTrue($viewModel->getStatus() == '200');

        // general exception
        $mockModel->method('readSubscriptionList')
                  ->will($this->throwException(new \Exception()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setUserId(1);
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
        $viewModel->setLang(1);
        $viewModel->setUserId(null);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '403');
    }

    /**
     * getUrlGroupList
     *
     *
     * @return UrlGroupList
     */
    private function getUrlGroupList()
    {
        $urlGroup = new UrlGroup();
        $urlGroup->setId($this->urlGroupId);
        $urlGroup->setTitle($this->urlGroupTitle);
        $urlGroup->setUrls(array());
        $urlGroups = new UrlGroupList();
        $urlGroups->setUrlGroupItems(array($urlGroup));
        return $urlGroups;
    }
}
