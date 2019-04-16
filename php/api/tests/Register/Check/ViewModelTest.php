<?php

namespace Datahouse\MON\Tests\Register\Check;

use Datahouse\MON\Register\Check\ViewModel;

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
        $mockModel = $this->getMockBuilder('Datahouse\MON\Register\Check\Model')
                          ->disableOriginalConstructor()
                          ->getMock();

        $mockModel->method('isUniqueEmail')
                  ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel);
        $viewModel->setEmail('data@house.ch');
        $this->assertTrue($viewModel->getData());
        $this->assertTrue($viewModel->getStatus() == '200');

        // general exception
        $mockModel->method('isUniqueEmail')
                  ->will($this->throwException(new \Exception()));
        $viewModel =
            new ViewModel($mockModel);
        $viewModel->setEmail('data@house.ch');
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
        //validation Exception
        $viewModel =
            new ViewModel($mockModel);
        $viewModel->setEmail('datahouse');
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');
    }
}
