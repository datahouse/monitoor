<?php

namespace Datahouse\MON\Tests\Url\Update;

use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Url\Update\Model;
use Datahouse\MON\Types\Gen\Url;

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
        $url = new Url();
        $url->setTitle('data');
        $url->setUrl('http://www.datahouse.ch');
        $url->setId(0);
        $url->setUrlGroupId(1);
        $url->setFrequency(1);
        $this->assertTrue($model->updateUrl($url, 'unit test', 1));
    }
}
