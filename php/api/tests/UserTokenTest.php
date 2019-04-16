<?php

namespace Datahouse\MON\Tests;

use Datahouse\MON\UserToken;
use Firebase\JWT\JWT;

/**
 * Class UserTokenTest.php
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class UserTokenTest extends \PHPUnit_Framework_TestCase
{
    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $userId = 244;
        $_SERVER['HTTP_AUTH_TOKEN'] = $this->generateToken($userId);
        $userToken = new UserToken();
        $this->assertTrue($userToken->isIsValidToken());
        $this->assertEquals($userId, $userToken->getUserId());
        $this->assertFalse($userToken->getKeepLogin());

        $_SERVER['HTTP_AUTH_TOKEN'] = 'token';
        $userToken = new UserToken();
        $this->assertFalse($userToken->isIsValidToken());
    }

    /**
     * generateToken
     *
     * @param int $userId the userId
     *
     * @return string
     */
    private function generateToken($userId)
    {
        $now = time();
        $payLoad = array(
            'userid' => $userId,
            'keepLogin' => false,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 60
        );
        $token = JWT::encode($payLoad, UserToken::KEY_STRING, 'HS256');
        return $token;
    }
}
