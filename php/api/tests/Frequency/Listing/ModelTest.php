<?php

namespace Datahouse\MON\Tests\Frequency\Listing;

use Datahouse\MON\Frequency\Listing\Model;
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
        $frequencyList = $model->readFrequencyList(2);
        $this->assertTrue(count($frequencyList) > 0);
        foreach ($frequencyList as $frequency) {
            $this->assertTrue($frequency->getId() > 0);
            $this->assertNotNull($frequency->getTitle());
        }
    }
}
