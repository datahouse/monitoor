<?php

namespace Datahouse\MON\Types;

/**
 * Class UserHash
 *
 * @package     Types
 * @author      Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2016 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class UserHash
{
    private $userId;
    private $hash;

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
     * getHash
     *
     * @return mixed
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * setHash
     *
     * @param mixed $hash hash
     * @return void
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

}
