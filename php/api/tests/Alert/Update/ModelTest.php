<?php

namespace Datahouse\MON\Tests\Alert\Update;

use Datahouse\MON\Alert\Update\Model;
use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Types\Gen\Alert;
use Datahouse\MON\Types\Gen\AlertShaping;
use Datahouse\MON\Types\Gen\AlertOption;
use Datahouse\MON\Types\Gen\AlertType;

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
        $alert = new Alert();
        $alert->setAlertShapingList($this->getAlertShapingList());
        $alert->setUrlGroup(array('id' => 1));
        $alert->setId(1);
        $model->updateAlert($alert, 1);
        $this->assertTrue(true);
    }

    private function getAlertShapingList()
    {
        $alertShapingList = array();
        $alertShaping = new AlertShaping();
        $alertShaping->setAlertType($this->getAlertType());
        $alertShaping->setKeywords(array('data', 'house'));
        $alertShaping->setAlertOption($this->getAlertOption());
        $alertShapingList[] = $alertShaping;
        return $alertShapingList;
    }

    private function getAlertType()
    {
        $alertType = new AlertType();
        $alertType->setId(3);
        $alertType->setCycle(3);
        return $alertType;
    }

    private function getAlertOption()
    {
        $alertOption = new AlertOption();
        $alertOption->setTitle("testTitle");
        $alertOption->setId(1);
        return $alertOption;
    }
}
