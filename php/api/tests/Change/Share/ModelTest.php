<?php

namespace Datahouse\MON\Tests\Change\Share;

use Datahouse\MON\Change\Share\Model;
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
        $res = $model->shareChange(1, 1);
        $this->assertNotNull($res);
        $this->assertFalse($model->checkUserChange(0, 0));
    }
}
