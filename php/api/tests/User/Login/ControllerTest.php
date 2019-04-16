<?php

namespace Datahouse\MON\Tests\User\Login;

use Datahouse\MON\User\Login\Controller;

/**
 * Class ControllerTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ControllerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $arr = array(
            'username' => 'email',
            'password' => 'password',
            'stayLoggedIn' => 'true'
        );
        $json = json_encode($arr);
        $mockRequest = $this->getMockBuilder('Datahouse\MON\Request')
                            ->disableOriginalConstructor()
                            ->getMock();
        $mockRequest->method('getJsonReqParams')
                    ->willReturn(json_decode($json));
        $mockModel = $this->getMockBuilder('Datahouse\MON\User\Login\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockViewModel =
            $this->getMockBuilder('Datahouse\MON\User\Login\ViewModel')
                 ->disableOriginalConstructor()
                 ->getMock();
        $controller = new Controller(
            $mockModel,
            $mockViewModel,
            $mockRequest
        );
        $controller->control();
        $this->assertTrue(true);
    }
}
