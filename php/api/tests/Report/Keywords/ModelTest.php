<?php

namespace Datahouse\MON\Tests\Report\Keywords;

use Datahouse\MON\Report\Keywords\Model;
use Datahouse\MON\Tests\AbstractModel;

/**
 * Class ModelTest
 *
 * @package Test
 * @author  Flavio Neuenschwander (fne) <flavio.neuenschwander@datahouse.ch>
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
        $model->readKeywordList(1335, 1);
        $this->assertTrue(true);
    }
}
