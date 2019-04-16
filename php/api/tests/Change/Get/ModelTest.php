<?php

namespace Datahouse\MON\Tests\Change\Get;

use Datahouse\MON\Change\Get\Model;
use Datahouse\MON\Exception\KeyNotFoundException;
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
     * @expectedException Datahouse\MON\Exception\KeyNotFoundException
     *
     * @return void
     */
    public function test()
    {
        $model = new Model($this->getPDO());
        $res = $model->getChange('1111111111');
        print_r($res);
    }
}
