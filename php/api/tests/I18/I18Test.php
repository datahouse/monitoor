<?php

namespace Datahouse\MON\Tests\I18;

use Datahouse\MON\I18\I18;

/**
 * Class I18Test
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class I18Test extends \PHPUnit_Framework_TestCase
{
    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $i18 = new I18();
        $test = $i18->translate('alert_type_1', 1);
        $this->assertEquals('SMS', $test);
        $test = $i18->translate('n/a', 1);
        $this->assertEquals('n/a', $test);
        $test = $i18->translate('alert_type_1', 5);
        $this->assertEquals('SMS', $test);
    }
}
