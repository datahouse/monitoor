<?php

namespace Datahouse\MON\Types;

/**
 * Class PushToken
 *
 * @package
 * @author      Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2016 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class PushToken
{
    private $userId;
    private $platform;
    private $token;
    /**
     * @var bool
     */
    private $denied = false;

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
     * isDenied
     *
     * @return boolean
     */
    public function isDenied()
    {
        return $this->denied;
    }

    /**
     * setDenied
     *
     * @param boolean $denied denied
     * @return void
     */
    public function setDenied($denied)
    {
        $this->denied = $denied;
    }
}
