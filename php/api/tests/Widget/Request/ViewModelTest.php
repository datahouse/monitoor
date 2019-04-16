<?php

namespace Datahouse\MON\TestsWidget\Request;

use Datahouse\MON\I18\I18;
use Datahouse\MON\Types\UserHash;
use Datahouse\MON\Widget\Request\ViewModel;

/**
 * Class ViewModelTest
 *
 * @package     Test
 * @author      Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModelTest extends \PHPUnit_Framework_TestCase
{

    private $email = 'pem@datahouse.ch';
    private $id = 1;

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\Widget\Request\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('isAllowedUrlGroup')
                  ->willReturn('true');
        $userHash = new UserHash();
        $userHash->setUserId(1);
        $userHash->setHash('dasfasf');
        $mockModel->method('createUser')
                  ->willReturn($userHash);
        $viewModel =
            new ViewModel($mockModel, new I18());
        $viewModel->setEmail($this->email);
        $viewModel->setUrlGroupId($this->id);
        $this->assertTrue($viewModel->getData());
        $this->assertTrue($viewModel->getStatus() == '200');

        // general exception
        $mockModel->method('addWidgetToUser')
                  ->will($this->throwException(new \Exception()));
        $viewModel =
            new ViewModel($mockModel, new I18());
        $viewModel->setEmail($this->email);
        $viewModel->setUrlGroupId($this->id);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
        //validation Exception
        $mockModel->method('addWidgetToUser')
                  ->willReturn('true');
        $viewModel =
            new ViewModel($mockModel, new I18());
        $viewModel->setEmail('');
        $viewModel->setUrlGroupId($this->id);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');
        //voucher validation Exception
        $viewModel =
            new ViewModel($mockModel, new I18());
        $viewModel->setEmail($this->email);
        $viewModel->setUrlGroupId('test');
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');
    }
}
