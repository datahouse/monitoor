<?php

namespace Datahouse\MON\Tests\Url\Add;

use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Types\Gen\UrlGroup;
use Datahouse\MON\Url\Add\Model;
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
        $url->setFrequency(1);
        $urlIds = $model->createUrl(array($url), 'unit test', 1);
        $this->assertTrue($urlIds['urlIds'][0] > 0);
        $this->assertTrue($urlIds['urlGroupId'] > 0);
        $deleteModel = new \Datahouse\MON\Url\Delete\Model($this->getPDO());
        $this->assertTrue($deleteModel->deleteUrl($urlIds['urlIds'][0]));
        $delGroupModel = new \Datahouse\MON\Urlgroup\Delete\Model($this->getPDO());
        $delGroupModel->deleteUrlGroup($urlIds['urlGroupId'], 1);

        $count = $model->getNbrOfUrls(1);
        $this->assertTrue($count > 1);
    }
}
