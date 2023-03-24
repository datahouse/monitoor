<?php

namespace Datahouse\MON\Tests\Urlgroup\Listing;

use Datahouse\MON\Urlgroup\Listing\Controller;

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
        $mockModel = $this->getMockBuilder('Datahouse\MON\Urlgroup\Listing\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockViewModel =
            $this->getMockBuilder('Datahouse\MON\Urlgroup\Listing\ViewModel')
                 ->disableOriginalConstructor()
                 ->getMock();
        $controller = new Controller(
            $mockModel,
            $mockViewModel,
            $mockRequest,
            $mockToken
        );
        $_GET['offset'] = 0;
        $_GET['size'] = 5;
        $_GET['sort'] = '-title';
        $_GET['title'] = 'title';
        $_GET['url'] = 'url';

        $controller->control();
        $this->assertTrue(true);
    }
}
