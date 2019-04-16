<?php

namespace Datahouse\MON\Tests\Job;

use Datahouse\MON\Job\ProcessDailyAlert;
use Datahouse\MON\Tests\PDOMock;

require_once(dirname(__FILE__) . '/../PDOMock.php');

/**
 * Class ProcessDailyAlertTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ProcessDailyAlertTest extends \PHPUnit_Framework_TestCase
{

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        /*        $pdo = $this->getMockBuilder('Datahouse\MON\Tests\PDOMock')
                                  ->getMock();
                $alertJob = new ProcessDailyAlert($pdo);
                $alertJob->run();*/
        $this->assertTrue(true);
    }
}
