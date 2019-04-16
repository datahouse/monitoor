<?php

namespace Datahouse\MON\Tests\Change\Listing;

use Datahouse\MON\Change\Listing\ViewModel;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Types\ChangeFilter;
use Datahouse\MON\Types\Gen\Change;
use Datahouse\MON\Types\Gen\ChangeItem;
use Datahouse\MON\Types\Gen\ChangeList;

/**
 * Class ViewModelTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModelTest extends \PHPUnit_Framework_TestCase
{

    private $urlId = 1;
    private $urlTitle = 'Datahouse';
    private $url = 'www';
    private $groupId = 2;
    private $groupTitle = 'Group';
    private $alertId = 3;
    private $alertTitle = 'Alert';

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\Change\Listing\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('readChangeList')
                  ->willReturn($this->getList());
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setPagingAndSorting(0, null, '-title');
        $viewModel->setUserId(1);
        $changeFilter = new ChangeFilter();
        $changeFilter->setUrlId(1);
        $viewModel->setChangeFilter($changeFilter);
        $changeList = $viewModel->getData();
        $this->assertTrue(count($changeList->getChangeItems()) > 0);
        $this->assertTrue($changeList->getCount() > 0);
        $this->assertTrue($viewModel->getStatus() == '200');
        $this->assertEquals($this->getList(), $changeList);

        // validation exception
        $changeFilter->setUrlId(null);
        $viewModel->setChangeFilter($changeFilter);
        $viewModel->setPagingAndSorting(0, null, '-title');
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');

        // general exception
        $mockModel->method('readChangeList')
             ->will($this->throwException(new \Exception()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $changeFilter = new ChangeFilter();
        $changeFilter->setUrlId(1);
        $viewModel->setChangeFilter($changeFilter);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');

        //permission Exception
        $mockModel->method('readChangeList')
                  ->willReturn($this->getList());
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('hasUrlGroupReadAccess')
            ->will($this->throwException(new PermissionException()));
        $permissionMock->method('hasUrlReadAccess')
                       ->will($this->throwException(new PermissionException()));
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $changeFilter = new ChangeFilter();
        $changeFilter->setUrlId(1);
        $viewModel->setChangeFilter($changeFilter);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '403');
    }

    /**
     * getList
     *
     *
     * @return ChangeList
     */
    private function getList()
    {
        $changeList = new ChangeList();
        $changeList->setCount(1);
        $changeItem = new ChangeItem();
        $change = new Change();
        $change->setNewDoc(array('id' => 2, 'content' => 'new'));
        $change->setOldDoc(array('id' => 1, 'content' => 'old'));
        $change->setChangeDate('timestamp');
        $change->setDiff('diff');
        $changeItem->setChange($change);
        $changeItem->setAlert(array(
            array('id' => $this->alertId, 'title' => $this->alertTitle)
        ));
        $changeItem->setUrl(array('id' => $this->urlId, 'title' => $this->urlTitle, 'url' => $this->url));
        $changeItem->setUrlGroup(array('id' => $this->groupId, 'title' => $this->groupTitle));
        $changeList->setChangeItems(array($changeItem));
        return $changeList;
    }
}
