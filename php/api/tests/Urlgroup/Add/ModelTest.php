<?php

namespace Datahouse\MON\Tests\Urlgroup\Add;

use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Types\Gen\UrlGroup;
use Datahouse\MON\Urlgroup\Add\Model;

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
        $url = new UrlGroup();
        $url->setTitle('data');
        $url = $model->createUrlGroup($url, 1);
        $this->assertTrue($url->getId() > 0);
        $deleteModel = new \Datahouse\MON\Urlgroup\Delete\Model($this->getPDO());
        $this->assertTrue($deleteModel->deleteUrlGroup($url->getId(), 1));
    }
}
