<?php

namespace Datahouse\MON\Tests\Url\Get;

use Datahouse\MON\I18\I18;
use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Url\Get\Model;

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
        $model = new Model($this->getPDO(), new I18());
        $url = $model->readUrl(1, 1);
        $this->assertTrue($url->getId() > 0);
        $this->assertNotNull($url->getTitle());
        $this->assertNotNull($url->getUrl());
        $this->assertTrue($url->getFrequency() > 0);
        $this->assertTrue(count($url->getFrequencyOptions()) > 0);
    }
}
