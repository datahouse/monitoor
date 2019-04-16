<?php

namespace Datahouse\MON\Pricing\Listing;

use Datahouse\MON\Types\Gen\PricingPlan;

/**
 * Class Model
 *
 * @package Alert
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Model extends \Datahouse\Framework\Model
{

    /**
     * @param \PDO $pdo the pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * readPricingPlans
     *
     * @param int $langCode the lang code
     *
     * @return array
     * @throws \Exception
     */
    public function readPricingPlans()
    {
        $query = '';
        $pricingPlans = array();
        try {
            $query =
                'SELECT pricing_plan_id, pricing_plan_text FROM pricing_plan ';
            $query .= ' ORDER BY pricing_plan_sort_order';
            foreach ($this->pdo->query($query) as $res) {
                $pricingPlan = new PricingPlan();
                $pricingPlan->setId($res['pricing_plan_id']);
                $pricingPlan->setText($res['pricing_plan_text']);
                $pricingPlans[] = $pricingPlan;
            }
            return $pricingPlans;
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
    }
}
