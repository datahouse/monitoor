<?php

namespace Datahouse\MON\Tests\Data\Push;

use Datahouse\MON\Data\Push\ViewModel;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Types\Gen\ExternalData;

/**
 * Class ViewModelTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\Data\Push\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('insertProviderData')
                  ->willReturn(true);
        $permissionMock =
            $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                 ->disableOriginalConstructor()
                 ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $viewModel->setData($this->getExternalData());
        $viewModel->setUrlId(1);

        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '200');

        // general exception
        $mockModel->method('insertProviderData')
                  ->will($this->throwException(new \Exception()));
        $permissionMock =
            $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                 ->disableOriginalConstructor()
                 ->getMock();
        $permissionMock->method('hasUrlWriteAccess')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setData($this->getExternalData());
        $viewModel->setUrlId(1);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
        //permission Exception
        $permissionMock =
            $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                 ->disableOriginalConstructor()
                 ->getMock();
        $permissionMock->method('hasUrlWriteAccess')
                       ->will($this->throwException(new PermissionException()));
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setData($this->getExternalData());
        $viewModel->setUrlId(1);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '403');
        //validation Exception
        $mockModel->method('insertProviderData')
                  ->willReturn(true);
        $permissionMock =
            $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                 ->disableOriginalConstructor()
                 ->getMock();
        $permissionMock->method('hasUrlWriteAccess')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setData($this->getExternalData(false));
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');
    }

    /**
     * getExternalData
     *
     *
     * @return array
     */
    private function getExternalData($valid = true) {
        $data = new ExternalData();
        if ($valid) {
            $data->setTimestamp(55555544);
        }
        $data->setDeletion('old content');
        $data->setAddition('add content');
        return array($data);
    }
}
