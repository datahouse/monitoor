<?php

namespace Datahouse\MON\Tests\Register\Activate;

use Datahouse\MON\Register\Activate\ViewModel;

/**
 * Class ViewModelTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModelTest extends \PHPUnit_Framework_TestCase
{

    private $activationHash = 'pem@datahouse.ch';

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\Register\Activate\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('activateUser')
                  ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel);
        $viewModel->setActivationHash($this->activationHash);

        $token = $viewModel->getData();
        $this->assertNotNull($token);
        $this->assertNotEmpty($token['token']->getId());
        $this->assertTrue(strlen($token['token']->getId()) > 100);
        $this->assertTrue($viewModel->getStatus() == '200');

        // general exception
        $mockModel->method('activateUser')
                  ->will($this->throwException(new \Exception()));
        $viewModel =
            new ViewModel($mockModel);
        $viewModel->setActivationHash($this->activationHash);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
        //validation Exception
        $viewModel =
            new ViewModel($mockModel);
        $viewModel->setActivationHash(null);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');
    }
}
