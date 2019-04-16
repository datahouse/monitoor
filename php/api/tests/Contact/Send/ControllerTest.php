<?php

namespace Datahouse\MON\Tests\Contact\Send;

use Datahouse\MON\Contact\Send\Controller;

/**
 * Class ControllerTest
 *
 * @package Test
 * @author  Flavio Neuenschwnader (fne) <flavio.neuenschwander@datahouse.ch>
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
            'name' => 'name',
            'email' => 'email@email.email',
            'message' => 'message'
        );
        $json = json_encode($arr);
        $mockRequest = $this->getMockBuilder('Datahouse\MON\Request')
                            ->disableOriginalConstructor()
                            ->getMock();
        $mockRequest->method('getJsonReqParams')
                    ->willReturn(json_decode($json));

        $mockToken = $this->getMockBuilder('Datahouse\MON\UserToken')
                          ->getMock();
        $mockViewModel =
            $this->getMockBuilder('Datahouse\MON\Contact\Send\ViewModel')
                 ->disableOriginalConstructor()
                 ->getMock();
        $controller = new Controller(
            $mockViewModel,
            $mockRequest,
            $mockToken
        );
        $controller->control();
        $this->assertTrue(true);
    }
}
