<?php

namespace Datahouse\MON\Tests\Url\Free;

use Datahouse\MON\Url\Free\Controller;

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
            'email' => 'pem@datahouse.ch',
            'url' => 'http://www.datahouse.ch/technologie'
        );
        $json = json_encode($arr);
        $mockRequest = $this->getMockBuilder('Datahouse\MON\Request')
                            ->disableOriginalConstructor()
                            ->getMock();
        $mockRequest->method('getJsonReqParams')
                  ->willReturn(json_decode($json));
        $mockModel = $this->getMockBuilder('Datahouse\MON\Url\Free\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockViewModel =
            $this->getMockBuilder('Datahouse\MON\Url\Free\ViewModel')
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
