<?php

namespace Datahouse\MON\Tests\Register\Activate;

use Datahouse\MON\Register\Activate\Controller;
use Datahouse\MON\UserToken;

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
            'activationHash' => 'asdfasfdasfasfasdf'
        );
        $json = json_encode($arr);
        $mockRequest = $this->getMockBuilder('Datahouse\MON\Request')
                            ->disableOriginalConstructor()
                            ->getMock();
        $mockRequest->method('getJsonReqParams')
                    ->willReturn(json_decode($json));
        $mockModel = $this->getMockBuilder('Datahouse\MON\Register\Activate\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockViewModel =
            $this->getMockBuilder('Datahouse\MON\Register\Activate\ViewModel')
                 ->disableOriginalConstructor()
                 ->getMock();
        $controller = new Controller(
            $mockModel,
            $mockViewModel,
            $mockRequest,
            new UserToken()
        );
        $controller->control();
        $this->assertTrue(true);
        $mockRequest->method('getJsonReqParams')
                    ->willReturn(null);
        $controller = new Controller(
            $mockModel,
            $mockViewModel,
            $mockRequest,
            new UserToken()
        );
        $controller->control();
        $this->assertTrue(true);
    }
}
