<?php

namespace Datahouse\MON\Tests\Alertoption\Listing;

use Datahouse\MON\Alertoption\Listing\Model;
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
        $optionList = $model->readAlertOptionList(1);
        $this->assertTrue(count($optionList) > 0);
        foreach ($optionList as $option) {
            $this->assertTrue($option->getId() > 0);
            $this->assertNotNull($option->getTitle());
        }
    }
}
