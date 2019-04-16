<?php

namespace Datahouse\MON\Tests\Urlgroup\Listing;

use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Types\Gen\UrlGroup;
use Datahouse\MON\Urlgroup\Listing\Model;

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
        $urlGroup = new UrlGroup();
        $urlGroup->setTitle('data');
        $model = new Model($this->getPDO(), new PermissionHandler($this->getPDO()));
        $urlList = $model->readUrlGroupList(0, 1, '-title,url', $urlGroup, 1);
        $this->assertTrue(0 < $urlList->getCount());
        $this->assertTrue(count($urlList->getUrlGroupItems()) == 1);
    }
}
