<?php

namespace Datahouse\MON\Types;

/**
 * Class Notification
 *
 * @package Types
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Notification
{

    private $alertId;
    private $typeCycleId;
    private $urlId;
    private $url;
    private $urlTitle;
    private $lastUrlChange;
    private $userId;
    private $userEmail;
    private $userFirstName;
    private $userLastName;
    private $userMobile;
    private $newDocId;
    private $platform;
    private $token;
    private $urlGroupId;
    private $urlGroupTitle;
    private $changeId;
    private $diffPreview;
    private $diffPreviewHtml;
    private $alternativeUrl;

    /**
     * @return string
     */
    public function getAlternativeUrl()
    {
        return $this->alternativeUrl;
    }

    /**
     * @param string $alternativeUrl alternativeUrl
     * @void
     */
    public function setAlternativeUrl($alternativeUrl)
    {
        $this->alternativeUrl = $alternativeUrl;
    }

    /**
     * @return string
     */
    public function getDiffPreviewHtml()
    {
        return $this->diffPreviewHtml;
    }

    /**
     * @param string $diffPreviewHtml diffPreviewHtml
     * @return void
     */
    public function setDiffPreviewHtml($diffPreviewHtml)
    {
        $this->diffPreviewHtml = $diffPreviewHtml;
    }

    /**
     * @return string
     */
    public function getDiffPreview()
    {
        return $this->diffPreview;
    }

    /**
     * @param string $diffPreview diffPreview
     * @return void
     */
    public function setDiffPreview($diffPreview)
    {
        $this->diffPreview = $diffPreview;
    }

    /**
     * getChangeId
     *
     * @return mixed
     */
    public function getChangeId()
    {
        return $this->changeId;
    }

    /**
     * setChangeId
     *
     * @param mixed $changeId changeId
     * @return void
     */
    public function setChangeId($changeId)
    {
        $this->changeId = $changeId;
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
     * getUrlGroupTitle
     *
     * @return mixed
     */
    public function getUrlGroupTitle()
    {
        return $this->urlGroupTitle;
    }

    /**
     * setUrlGroupTitle
     *
     * @param mixed $urlGroupTitle urlGroupTitle
     * @return void
     */
    public function setUrlGroupTitle($urlGroupTitle)
    {
        $this->urlGroupTitle = $urlGroupTitle;
    }

    /**
     * getPlatform
     *
     * @return mixed
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * setPlatform
     *
     * @param mixed $platform platform
     * @return void
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
    }

    /**
     * getToken
     *
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * setToken
     *
     * @param mixed $token token
     * @return void
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * getUrlTitle
     *
     * @return mixed
     */
    public function getUrlTitle()
    {
        return $this->urlTitle;
    }

    /**
     * setUrlTitle
     *
     * @param mixed $urlTitle urlTitle
     * @return void
     */
    public function setUrlTitle($urlTitle)
    {
        $this->urlTitle = $urlTitle;
    }

    /**
     * getNewDocId
     *
     * @return mixed
     */
    public function getNewDocId()
    {
        return $this->newDocId;
    }

    /**
     * setNewDocId
     *
     * @param mixed $newDocId newDocId
     * @return void
     */
    public function setNewDocId($newDocId)
    {
        $this->newDocId = $newDocId;
    }



    /**
     * getUserId
     *
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * setUserId
     *
     * @param mixed $userId userId
     * @return void
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * getLastUrlChange
     *
     * @return mixed
     */
    public function getLastUrlChange()
    {
        return $this->lastUrlChange;
    }

    /**
     * setLastUrlChange
     *
     * @param mixed $lastUrlChange lastUrlChange
     * @return void
     */
    public function setLastUrlChange($lastUrlChange)
    {
        $this->lastUrlChange = $lastUrlChange;
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
     * getTypeCycleId
     *
     * @return mixed
     */
    public function getTypeCycleId()
    {
        return $this->typeCycleId;
    }

    /**
     * setTypeCycleId
     *
     * @param mixed $typeCycleId typeCycleId
     * @return void
     */
    public function setTypeCycleId($typeCycleId)
    {
        $this->typeCycleId = $typeCycleId;
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
     * getUrl
     *
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * setUrl
     *
     * @param mixed $url url
     * @return void
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * getUserEmail
     *
     * @return mixed
     */
    public function getUserEmail()
    {
        return $this->userEmail;
    }

    /**
     * setUserEmail
     *
     * @param mixed $userEmail userEmail
     * @return void
     */
    public function setUserEmail($userEmail)
    {
        $this->userEmail = $userEmail;
    }

    /**
     * getUserFirstName
     *
     * @return mixed
     */
    public function getUserFirstName()
    {
        return $this->userFirstName;
    }

    /**
     * setUserFirstName
     *
     * @param mixed $userFirstName userFirstName
     * @return void
     */
    public function setUserFirstName($userFirstName)
    {
        $this->userFirstName = $userFirstName;
    }

    /**
     * getUserLastName
     *
     * @return mixed
     */
    public function getUserLastName()
    {
        return $this->userLastName;
    }

    /**
     * setUserLastName
     *
     * @param mixed $userLastName userLastName
     * @return void
     */
    public function setUserLastName($userLastName)
    {
        $this->userLastName = $userLastName;
    }

    /**
     * getUserMobile
     *
     * @return mixed
     */
    public function getUserMobile()
    {
        return $this->userMobile;
    }

    /**
     * setUserMobile
     *
     * @param mixed $userMobile userMobile
     * @return void
     */
    public function setUserMobile($userMobile)
    {
        $this->userMobile = $userMobile;
    }
}
