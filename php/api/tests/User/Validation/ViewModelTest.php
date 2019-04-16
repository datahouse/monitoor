<?php

namespace Datahouse\MON\Tests\User\Validation;

use Datahouse\MON\User\Validation\ViewModel;

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
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $viewModel = new ViewModel($permissionMock);
        $viewModel->setLang(1);
        $viewModel->setIsValidToken(true);
        $viewModel->setKeepLogin(false);
        $viewModel->setPage('page');
        $token = $viewModel->getData();
        $this->assertNotEmpty($token['token']->getId());
        $this->assertTrue(strlen($token['token']->getId()) > 100);
        $this->assertTrue($viewModel->getStatus() == '200');

        // token not valid
        $viewModel->setIsValidToken(false);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '401');
    }
}
