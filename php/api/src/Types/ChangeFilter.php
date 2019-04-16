<?php


namespace Datahouse\MON\Types;

/**
 * Class ChangeFilter
 *
 * @package Types
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ChangeFilter
{
    private $urlGroupId;
    private $urlId;
    private $alertId;
    private $startDate;
    private $keyword;

    /**
     * getKeyword
     *
     * @return mixed
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * setKeyword
     *
     * @param mixed $keyword keyword
     * @return void
     */
    public function setKeyword($keyword)
    {
        $this->keyword = $keyword;
    }

    /**
     * getUrlGroupId
     *
     * @return mixed
     */
    public function getUrlGroupId()
    {
        return $this->urlGroupId;
    }

    /**
     * setUrlGroupId
     *
     * @param mixed $urlGroupId urlGroupId
     * @return void
     */
    public function setUrlGroupId($urlGroupId)
    {
        $this->urlGroupId = $urlGroupId;
    }

    /**
     * getUrlId
     *
     * @return mixed
     */
    public function getUrlId()
    {
        return $this->urlId;
    }

    /**
     * setUrlId
     *
     * @param mixed $urlId urlId
     * @return void
     */
    public function setUrlId($urlId)
    {
        $this->urlId = $urlId;
    }

    /**
     * getAlertId
     *
     * @return mixed
     */
    public function getAlertId()
    {
        return $this->alertId;
    }

    /**
     * setAlertId
     *
     * @param mixed $alertId alertId
     * @return void
     */
    public function setAlertId($alertId)
    {
        $this->alertId = $alertId;
    }

    /**
     * getStartDate
     *
     * @return mixed
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * setStartDate
     *
     * @param mixed $startDate startDate
     * @return void
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }
}
