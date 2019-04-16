<?php

namespace Datahouse\MON\Tests\Alert\Delete;

use Datahouse\MON\Alert\Delete\Controller;
use Datahouse\MON\Exception\MethodNotAllowedException;

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

        $mockToken = $this->getMockBuilder('Datahouse\MON\UserToken')
                          ->getMock();
        $mockRequest = $this->getMockBuilder('Datahouse\MON\Request')
                            ->disableOriginalConstructor()
                            ->getMock();
        $mockRequest->method('getRequestMethod')
                    ->willReturn('DELETE');
        $mockModel = $this->getMockBuilder('Datahouse\MON\Alert\Delete\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockViewModel =
            $this->getMockBuilder('Datahouse\MON\Alert\Delete\ViewModel')
                 ->disableOriginalConstructor()
                 ->getMock();
        $controller = new Controller(
            $mockModel,
            $mockViewModel,
            $mockRequest,
            $mockToken
        );
        $controller->checkRequestMethod();
        $controller->control();
        $this->assertTrue(true);

        $mockRequest2 = $this->getMockBuilder('Datahouse\MON\Request')
                             ->disableOriginalConstructor()
                             ->getMock();
        $mockRequest2->method('getRequestMethod')
                     ->willReturn('POST');
        $controller2 = new Controller(
            $mockModel,
            $mockViewModel,
            $mockRequest2,
            $mockToken
        );
        $this->setExpectedException(
            '\Datahouse\MON\Exception\MethodNotAllowedException'
        );
        $controller2->checkRequestMethod();
    }
}
