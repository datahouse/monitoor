<?php

namespace Datahouse\MON\Tests\Pricing\Listing;

use Datahouse\MON\Pricing\Listing\Model;
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
        $model = new Model($this->getPDO());
        $pricingPlans = $model->readPricingPlans();
        $this->assertTrue(count($pricingPlans) > 0);
        foreach ($pricingPlans as $pricingPlan) {
            $this->assertTrue($pricingPlan->getId() > 0);
            $this->assertNotNull($pricingPlan->getText());
        }
    }
}
