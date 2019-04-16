<?php

namespace Datahouse\MON\Tests\Urlgroup\Subscriptions;

use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Urlgroup\Subscriptions\Model;

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
        $model = new Model($this->getPDO(), new PermissionHandler($this->getPDO()));
        $urlGroups = $model->readSubscriptionList(2);
        $this->assertTrue(count($urlGroups) > 0);
        foreach ($urlGroups->getUrlGroupItems() as $item) {
            $this->assertNotNull($item->getTitle());
            $this->assertTrue($item->getId() > 0);
        }
    }
}
