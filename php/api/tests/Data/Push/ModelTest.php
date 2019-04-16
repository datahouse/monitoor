<?php

namespace Datahouse\MON\Tests\Data\Push;

use Datahouse\MON\Data\Push\Model;
use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Types\Gen\ExternalData;

require_once(dirname(__FILE__) . '/../../AbstractModel.php');

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

        $item = new ExternalData();
        $item->setTimestamp('2016-02-17T10:03:32');
        $item->setAddition('new text');
        $item->setDeletion('dropped text');
        $items = array($item);
        $model->insertProviderData(1, 1, $items);
        $this->assertTrue(true);
    }
}
