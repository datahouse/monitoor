<?php

namespace Datahouse\MON\Tests\I18\Trans;

use Datahouse\MON\I18\Trans\Controller;

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
        $mockRequest = $this->getMockBuilder('Datahouse\MON\Request')
                            ->disableOriginalConstructor()
                            ->getMock();
        $mockModel =
            $this->getMockBuilder('Datahouse\MON\I18\Trans\Model')
                 ->disableOriginalConstructor()
                 ->getMock();
        $mockViewModel =
            $this->getMockBuilder('Datahouse\MON\I18\Trans\ViewModel')
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
