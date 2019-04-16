<?php

namespace Datahouse\MON\Tests;

use Datahouse\Framework\Router\Route;
use Datahouse\MON\Router\Rule;
use Dice\Dice;

/**
 * Class RuleTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class RuleTest extends \PHPUnit_Framework_TestCase
{

    /**
     * test
     *
     *
     * @return void
     */
    public function test()
    {
        $route = array('v1', 'url', 'listing');
        $dice = new Dice();

        $rule = new \Dice\Rule();
        $rule->shared = true;
        $db = 'pgsql:host=pgsql.datalan.ch;port=5432;dbname=project_mon;' .
            'user=project_mon;password=<redacted>';
        $rule->constructParams = array($db);
        $dice->addRule('PDO', $rule);

        $rule = new \Dice\Rule();
        $rule->constructParams[] = $route;
        $dice->addRule('Datahouse\\MON\\Request', $rule);
        $rule = new Rule($dice);
        $res = $rule->find($route);
        $this->assertTrue($res instanceof Route);
    }
}
