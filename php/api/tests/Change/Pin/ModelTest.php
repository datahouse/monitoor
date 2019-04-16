<?php

namespace Datahouse\MON\Tests\Change\Pin;

use Datahouse\MON\Change\Pin\Model;
use Datahouse\MON\Tests\AbstractModel;

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
        $this->assertTrue($model->addFavorite(1, 1));
        $this->assertFalse($model->checkUserChange(0, 0));
    }
}
