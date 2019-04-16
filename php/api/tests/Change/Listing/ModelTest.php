<?php

namespace Datahouse\MON\Tests\Change\Listing;

use Datahouse\MON\Change\Listing\Model;
use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Types\ChangeFilter;

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
        $filter = new ChangeFilter();
        $filter->setAlertId(123);
        $filter->setStartDate(date('Y-m-d', time()));
        $filter->setUrlGroupId(113);
        $filter->setUrlId(123);
        $model = new Model($this->getPDO());
        $changeList = $model->readChangeList($filter, 0, 0, '-start_date', 1, false, false);
        $this->assertTrue(count($changeList->getChangeItems()) == 0);
        //demo
        $model->readChangeList($filter, 0, 0, '-start_date', 1, true, false);
        $this->assertTrue(true);
        //favorites
        $model->readChangeList($filter, 0, 0, '-start_date', 1, false, true);
        $this->assertTrue(true);
    }
}
