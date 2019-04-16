<?php

namespace Datahouse\MON\Types;

/**
 * Class PwdChange
 *
 * @package Types
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class PwdChange
{

    private $pwd1;
    private $pwd2;
    private $hashValue;
    private $oldPwd;

    /**
     * getPwd1
     *
     * @return mixed
     */
    public function getPwd1()
    {
        return $this->pwd1;
    }

    /**
     * setPwd1
     *
     * @param mixed $pwd1 pwd1
     *
     * @return void
     */
    public function setPwd1($pwd1)
    {
        $this->pwd1 = $pwd1;
    }

    /**
     * getPwd2
     *
     * @return mixed
     */
    public function getPwd2()
    {
        return $this->pwd2;
    }

    /**
     * setPwd2
     *
     * @param mixed $pwd2 pwd2
     *
     * @return void
     */
    public function setPwd2($pwd2)
    {
        $this->pwd2 = $pwd2;
    }

    /**
     * getHashValue
     *
     * @return mixed
     */
    public function getHashValue()
    {
        return $this->hashValue;
    }

    /**
     * setHashValue
     *
     * @param mixed $hashValue hashValue
     *
     * @return void
     */
    public function setHashValue($hashValue)
    {
        $this->hashValue = $hashValue;
    }

    /**
     * getOldPwd
     *
     * @return mixed
     */
    public function getOldPwd()
    {
        return $this->oldPwd;
    }

    /**
     * setOldPwd
     *
     * @param mixed $oldPwd oldPwd
     * @return void
     */
    public function setOldPwd($oldPwd)
    {
        $this->oldPwd = $oldPwd;
    }
}
