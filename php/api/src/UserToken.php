<?php

namespace Datahouse\MON;

use Firebase\JWT\JWT;

/**
 * Class UserToken
 *
 * @package Request
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class UserToken
{

    const KEY_STRING = 'scO12xi9s239cpD783Si6d130MgoNyWM';

    const ALG = 'HS256';

    private $userId;
    private $isValidToken = false;
    private $keepLogin = false;
    private $iat;

    /**
     * constructor
     */
    public function __construct()
    {
        if (array_key_exists('HTTP_AUTH_TOKEN', $_SERVER)) {
            $token = $_SERVER['HTTP_AUTH_TOKEN'];
            $data = $this->checkToken($token);
            if ($data != null) {
                $this->isValidToken = true;
                $this->userId = $data->userid;
                $this->keepLogin = $data->keepLogin;
                $this->iat = $data->iat;
            }
        }
    }

    /**
     * checkToken
     *
     * @param string $token the token
     *
     * @return null|string
     */
    private function checkToken($token)
    {
        try {
            $data = JWT::decode($token, self::KEY_STRING, array(self::ALG));
        } catch (\Exception $e) {
            return null;
        }
        return $data;
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
     * getIat
     *
     * @return mixed
     */
    public function getIat()
    {
        return $this->iat;
    }

    /**
     * isIsValidToken
     *
     * @return boolean
     */
    public function isIsValidToken()
    {
        return $this->isValidToken;
    }

    /**
     * getKeepLogin
     *
     *
     * @return bool
     */
    public function getKeepLogin()
    {
        return $this->keepLogin;
    }
}
