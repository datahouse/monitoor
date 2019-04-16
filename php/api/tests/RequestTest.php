<?php


namespace Datahouse\MON\Tests;

use Datahouse\MON\Request;

/**
 * Class RequestTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $id = 1;
        $requestMethod = 'GET';
        $_SERVER['REQUEST_METHOD'] = $requestMethod;
        $arr = array('v1', 'alert', 'get', $id, 'de');
        $req = new Request($arr);
        $this->assertEquals($req->getId(), $id);
        $this->assertEquals($req->getLang(), 1);
        $this->assertEquals($req->getRequestMethod(), $requestMethod);
        $jsonParams = $req->getJsonReqParams();
        $this->assertFalse(isset($jsonParams));

        $arr = array('v1', 'alert', 'listing', 'de');
        $req = new Request($arr);
        $this->assertEquals($req->getLang(), 1);
        try {
            $req->getId();
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        try {
            $req->getName();
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        $arr = array('v1', 'i18', 'trans', 'test', 'en');
        $req = new Request($arr);
        $this->assertEquals($req->getLang(), 2);
        $this->assertEquals($req->getName(), 'test');

    }
}
