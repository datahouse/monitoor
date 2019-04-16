<?php

namespace Datahouse\MON\Tests\Urlgroup\Delete;

use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Urlgroup\Delete\Model;

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
        $model->deleteUrlGroup(0, 0);
        $this->assertTrue(true);
    }
}
