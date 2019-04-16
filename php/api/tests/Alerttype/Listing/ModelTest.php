<?php

namespace Datahouse\MON\Tests\Alerttype\Listing;

use Datahouse\MON\Alerttype\Listing\Model;
use Datahouse\MON\I18\I18;
use Datahouse\MON\Tests\AbstractModel;

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
        $alertTypeList = $model->readAlertTypeList(1,1);
        $this->assertTrue(count($alertTypeList) > 0);
        foreach ($alertTypeList as $alertType) {
            $this->assertTrue($alertType->getId() > 0);
            $this->assertNotNull($alertType->getTitle());
            $this->assertTrue(count($alertType->getCycle()) > 0);
            foreach ($alertType->getCycle() as $cycles) {
                $this->assertTrue($cycles['id'] > 0);
                $this->assertNotNull($cycles['title']);
            }
        }
    }
}
