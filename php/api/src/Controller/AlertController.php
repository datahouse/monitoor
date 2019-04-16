<?php

namespace Datahouse\MON\Controller;

use Datahouse\MON\Types\Gen\AlertOption;
use Datahouse\MON\Types\Gen\AlertShaping;
use Datahouse\MON\Types\Gen\AlertType;

/**
 * Class AlertController
 *
 * Base controller for AlertControllers
 *
 * @package Datahouse\MON\Alert\Controller
 * @author  Flavio Neuenschwander (fne) <flavio.neuenschwander@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
abstract class AlertController extends \Datahouse\MON\BaseController
{

    /**
     * getUrlGroup
     *
     * @return array
     */
    protected function getUrlGroup()
    {
        $urlGroup = array();
        $urlParam =
            $this->readJsonParam(
                $this->request->getJsonReqParams(),
                'urlGroup'
            );
        if ($urlParam != null) {
            $urlGroup['id'] = $this->readJsonParam($urlParam, 'id');
        }
        return $urlGroup;
    }

    /**
     * getAlertShapingList
     *
     * @return array
     */
    protected function getAlertShapingList()
    {
        $alertShapingList = array();
        $jsonAlertShapingList = $this->readJsonParam(
            $this->request->getJsonReqParams(),
            'alertShapingList'
        );
        foreach ($jsonAlertShapingList as $jsonAlertShaping) {
            $alertShapingList[] = $this->getAlertShaping($jsonAlertShaping);
        }
        return $alertShapingList;
    }

    /**
     * getAlertShaping
     *
     * @param $jsonAlertShaping
     * @return AlertShaping
     */
    private function getAlertShaping($jsonAlertShaping)
    {
        $alertShaping = new AlertShaping();
        $alertShaping->setAlertType($this->getAlertType($this->readJsonParam($jsonAlertShaping, 'alertType')));
        $alertShaping->setAlertOption($this->getAlertOption($this->readJsonParam($jsonAlertShaping, 'alertOption')));
        if ($alertShaping->getAlertOption()->getId() === 2) {
            $alertShaping->setKeywords($this->getKeywords($jsonAlertShaping->keywords));
        } else {
            $alertShaping->setKeywords(array());
        }
        if ($alertShaping->getAlertOption()->getId() === 3) {
            $alertShaping->setAlertThreshold(
                $this->readJsonParam($jsonAlertShaping, 'alertThreshold')
            );
        }
        return $alertShaping;
    }

    /**
     * getAlertType
     *
     * @param $jsonAlertType
     * @return AlertType AlertType
     */
    private function getAlertType($jsonAlertType)
    {
        $alertType = new AlertType();
        if ($jsonAlertType != null) {
            $alertType->setCycle($jsonAlertType->cycleId);
            $alertType->setId($jsonAlertType->id);
            $alertType->setTitle('');
        }
        return $alertType;
    }

    /**
     * getKeywords
     *
     * @param $jsonKeywords
     * @return array
     */
    private function getKeywords($jsonKeywords)
    {
        $keywords = array();
        if ($jsonKeywords != null && is_array($jsonKeywords) && count($jsonKeywords) > 0) {
            foreach ($jsonKeywords as $jsonKeyword) {
                if ($jsonKeyword != null && strlen($jsonKeyword) > 0) {
                    $keywords[] = strip_tags($jsonKeyword);
                }
            }
        }
        return array_unique($keywords);
    }

    /**
     * getAlertOption
     *
     * @param $jsonAlertOption
     * @return AlertOption AlertOption
     */
    private function getAlertOption($jsonAlertOption)
    {
        $alertOption = new AlertOption();
        if ($jsonAlertOption != null) {
            $alertOption->setTitle($jsonAlertOption->title);
            $alertOption->setId($jsonAlertOption->id);
        }
        return $alertOption;
    }
}
