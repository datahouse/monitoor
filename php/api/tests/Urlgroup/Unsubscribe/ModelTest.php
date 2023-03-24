<?php

namespace Datahouse\MON\Tests\Urlgroup\Unsubscribe;

use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Urlgroup\Unsubscribe\Model;

/**
 * Class ModelTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ModelTest extends AbstractModel
{

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $model = new Model($this->getPDO());
        $model->subscribeUrlGroup(1280, 1, null, false);
        $this->assertTrue(true);
    }
}
