<?php

namespace Datahouse\MON\Tests\Widget\Request;

use Datahouse\MON\Widget\Request\Controller;

/**
 * Class ControllerTest
 *
 * @package     Test
 * @author      Peter Müller (pem) <peter.mueller@datahouse.ch>
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
            'email' => 'pem@datahouse.ch',
            'id' => '1'
        );
        $json = json_encode($arr);
        $mockRequest = $this->getMockBuilder('Datahouse\MON\Request')
                            ->disableOriginalConstructor()
                            ->getMock();
        $mockRequest->method('getJsonReqParams')
                    ->willReturn(json_decode($json));
        $mockToken = $this->getMockBuilder('Datahouse\MON\UserToken')
                          ->getMock();
        $mockModel = $this->getMockBuilder('Datahouse\MON\Widget\Request\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockViewModel =
            $this->getMockBuilder('Datahouse\MON\Widget\Request\ViewModel')
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
    }
}
