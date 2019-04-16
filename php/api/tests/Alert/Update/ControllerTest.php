<?php

namespace Datahouse\MON\Tests\Alert\Update;

use Datahouse\MON\Alert\Update\Controller;

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
            'id' => '1',
            'urlGroup' => array('id' => 1, 'type' => 'group'),
            'alertShapingList' => $this->getAlertShapingList()
        );
        $json = json_encode($arr);
        $mockToken = $this->getMockBuilder('Datahouse\MON\UserToken')
                          ->getMock();
        $mockRequest = $this->getMockBuilder('Datahouse\MON\Request')
                            ->disableOriginalConstructor()
                            ->getMock();
        $mockRequest->method('getJsonReqParams')
                    ->willReturn(json_decode($json));
        $mockModel = $this->getMockBuilder('Datahouse\MON\Alert\Update\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockViewModel =
            $this->getMockBuilder('Datahouse\MON\Alert\Update\ViewModel')
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

    private function getAlertShapingList()
    {
        $alertType = new \stdClass();
        $alertType->cycleId = 1;
        $alertType->id = 2;

        $alertOption = new \stdClass();
        $alertOption->title = 'Test';
        $alertOption->id = 2;

        $alertShaping = new \stdClass();
        $alertShaping->alertType = $alertType;
        $alertShaping->keywords = array('data', 'house');
        $alertShaping->alertOption = $alertOption;

        return array($alertShaping);
    }
}
