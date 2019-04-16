<?php

namespace Datahouse\MON\Tests\Report\Keywords;

use Datahouse\MON\Report\Keywords\Controller;

/**
 * Class ControllerTest
 *
 * @package Test
 * @author  Flavio Neuenschwander (fne) <flavio.neuenschwander@datahouse.ch>
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
        $mockModel =
            $this->getMockBuilder('Datahouse\MON\Report\Keywords\Model')
                 ->disableOriginalConstructor()
                 ->getMock();
        $mockViewModel =
            $this->getMockBuilder('Datahouse\MON\Report\Keywords\ViewModel')
                 ->disableOriginalConstructor()
                 ->getMock();
        $_GET['urlGroupId'] = 0;
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
