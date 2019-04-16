<?php

namespace Datahouse\MON\Tests\User\Login;

use Datahouse\MON\Exception\UnauthorizedException;
use Datahouse\MON\User\Login\ViewModel;

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
        $mockModel = $this->getMockBuilder('Datahouse\MON\User\Login\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $i18 = $this->getMockBuilder('Datahouse\MON\I18\I18')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('login')
                  ->willReturn(true);

        $viewModel =
            new ViewModel($mockModel, $i18);
        $viewModel->setLang(1);
        $viewModel->setKeepLogin(false);
        $viewModel->setEmailAndPwd('email', 'pwd');

        $token = $viewModel->getData();
        $this->assertNotNull($token);
        $this->assertNotEmpty($token['token']->getId());
        $this->assertTrue(strlen($token['token']->getId()) > 100);
        $this->assertTrue($viewModel->getStatus() == '200');

        //validation Exception
        $viewModel->setEmailAndPwd(null, 'pwd');
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');

        $viewModel->setEmailAndPwd('email', '');
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');


        //permission Exception
        $mockModel->method('login')
                  ->will($this->throwException(new UnauthorizedException()));
        $viewModel =
            new ViewModel($mockModel,$i18);
        $viewModel->setEmailAndPwd('email', 'pwd');
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '401');
        // general exception
        $mockModel->method('login')
                  ->will($this->throwException(new \Exception()));
        $viewModel =
            new ViewModel($mockModel, $i18);
        $viewModel->setEmailAndPwd('email', 'pwd');
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
    }
}
