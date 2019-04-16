<?php

namespace Datahouse\MON\Tests\Url\Listing;

use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Types\Gen\Url;
use Datahouse\MON\Url\Listing\Model;

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
        $url = new Url();
        $url->setTitle('DH');
        $url->setUrl('data');
        $model = new Model($this->getPDO());
        $urlList = $model->readUrlList(0, 1, '-title,url', $url, 1);
        $this->assertTrue(1 < $urlList->getCount());
        $this->assertTrue(count($urlList->getUrlItems()) == 1);
        foreach ($urlList->getUrlItems() as $url) {
            $this->assertTrue($url->getId() > 0);
            $this->assertNotNull($url->getTitle());
            $this->assertNotNull($url->getUrl());
        }
    }
}
