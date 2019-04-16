<?php

namespace Datahouse\MON\Tests\Urlgroup\Get;

use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Urlgroup\Get\Model;

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
        $urlGroup = $model->readUrlGroup(1, 1);
        $this->assertTrue($urlGroup->getId() > 0);
        $this->assertNotNull($urlGroup->getTitle());
        //$this->assertTrue(count($urlGroup->getUrls()) > 0);
        foreach ($urlGroup->getUrls() as $url) {
            $this->assertNotNull($url->getUrl());
            $this->assertNotNull($url->getTitle());
            $this->assertTrue($url->getId() > 0);
        }
    }
}
