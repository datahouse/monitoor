<?php

namespace Datahouse\MON\Tests\Change\Pin;

use Datahouse\MON\Change\Pin\Controller;

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
            'changeId' => 21313,
            'userId' => 1
        );
        $json = json_encode($arr);
        $mockToken = $this->getMockBuilder('Datahouse\MON\UserToken')
                          ->getMock();
        $mockRequest = $this->getMockBuilder('Datahouse\MON\Request')
                            ->disableOriginalConstructor()
                            ->getMock();
        $mockRequest->method('getJsonReqParams')
                    ->willReturn(json_decode($json));
        $mockModel = $this->getMockBuilder('Datahouse\MON\Change\Pin\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockViewModel =
            $this->getMockBuilder('Datahouse\MON\Change\Pin\ViewModel')
                 ->disableOriginalConstructor()
                 ->getMock();
        $controller = new Controller(
            $mockModel,
            $mockViewModel,
            $mockRequest,
            $mockToken
        );
        $controller->control();
        $this->assertTrue(true);
        $mockRequest->method('getJsonReqParams')
                    ->willReturn(json_decode($json));
        $_REQUEST['changeId'] = 2;
        $_REQUEST['rating'] = 5;
        $controller->control();
        $this->assertTrue(true);
    }
}
