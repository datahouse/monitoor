<?php

namespace Datahouse\MON\Tests\Url\Free;

use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Url\Free\Model;
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
        $url->setUrl('https://www.datahouse.ch');
        $url->setTitle('unittest');
        $result = $model->addFreeUrl($url, 'unit.test@datahouse.ch');
        $this->assertTrue($result != null);
    }
}
