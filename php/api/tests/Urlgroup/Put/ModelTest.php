<?php

namespace Datahouse\MON\Tests\Urlgroup\Put;

use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Urlgroup\Put\Model;

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
        $this->assertTrue($model->putUrlIntoGroup(0, array(0)));
    }
}
