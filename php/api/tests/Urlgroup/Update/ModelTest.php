<?php

namespace Datahouse\MON\Tests\Urlgroup\Update;

use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Types\Gen\UrlGroup;
use Datahouse\MON\Urlgroup\Update\Model;

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
        $urlGroup = new UrlGroup();
        $urlGroup->setTitle('data');
        $urlGroup->setId(0);
        $this->assertTrue($model->updateUrlGroup($urlGroup));
    }
}
