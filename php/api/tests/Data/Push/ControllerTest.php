<?php

namespace Datahouse\MON\Tests\Data\Push;

use Datahouse\MON\Data\Push\Controller;

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
            'id' => 1,
            'data' => array(array('addition' => 'new', 'deletion' => 'old', 'timestamp' => 55555544)),
        );
        $json = json_encode($arr);
        $mockToken = $this->getMockBuilder('Datahouse\MON\UserToken')
                          ->getMock();
        $mockRequest = $this->getMockBuilder('Datahouse\MON\Request')
                            ->disableOriginalConstructor()
                            ->getMock();
        $mockRequest->method('getJsonReqParams')
                    ->willReturn(json_decode($json));
        $mockModel = $this->getMockBuilder('Datahouse\MON\Data\Push\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockViewModel =
            $this->getMockBuilder('Datahouse\MON\Data\Push\ViewModel')
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
