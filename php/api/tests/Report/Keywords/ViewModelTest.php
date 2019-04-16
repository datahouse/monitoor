<?php

namespace Datahouse\MON\Tests\Report\Keywords;

use Datahouse\MON\Report\Keywords\ViewModel;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Types\Gen\KeywordGraphData;

/**
 * Class ViewModelTest
 *
 * @package Test
 * @author  Flavio Neuenschwander (fne) <flavio.neuenschwander@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModelTest extends \PHPUnit_Framework_TestCase
{

    private $baseY = 50;
    private $baseKey = 'test';

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel =
            $this->getMockBuilder('Datahouse\MON\Report\Keywords\Model')
                 ->disableOriginalConstructor()
                 ->getMock();
        $mockModel->method('readKeywordList')
                  ->willReturn($this->getList());
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setUserId(1);
        $viewModel->setLang(1);
        $viewModel->setUrlGroupId(108);

        $keywordList = $viewModel->getData();
        $this->assertTrue(count($keywordList) > 0);
        $this->assertTrue($viewModel->getStatus() == '200');

        $keyword = $keywordList[0];
        $this->assertTrue($keyword->getY() == $this->baseY);
        $this->assertTrue($keyword->getKey() == $this->baseKey . '1');

        // general exception
        $mockModel->method('readKeywordList')
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
        $mockModel->method('readKeywordList')
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
        $list = array();
        for ($i = 1; $i < 6; $i++) {
            $kgd = new KeywordGraphData();
            $kgd->setKey($this->baseKey . $i);
            $kgd->setY($this->baseY * $i);
            $list[] = $kgd;
        }
        return $list;
    }
}
