<?php

namespace Datahouse\MON;

use Datahouse\MON\I18\I18;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\Framework\Model;
use Firebase\JWT\JWT;

/**
 * Class PermissionViewModel
 *
 * @package ViewModel
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
abstract class PermissionViewModel extends ViewModel
{
    /**
     * @var PermissionHandler
     */
    protected $permissionHandler;

    /**
     *
     * @param \Datahouse\Framework\Model $model             the model
     * @param PermissionHandler          $permissionHandler the permissionHandler
     *
     */
    public function __construct(
        Model $model,
        PermissionHandler $permissionHandler = null,
        I18 $i18 = null
    ) {
        parent::__construct($model, $i18);
        $this->permissionHandler = $permissionHandler;
    }

    /**
     * createToken
     *
     * @param int  $userId    the user id
     * @param bool $keepLogin the keep logged in flag
     * @param int  $iat       issued at
     *
     * @return string
     */
    protected function createToken($userId, $keepLogin = false, $iat = null)
    {
        $now = time();
        if ($iat != null) {
            if (($iat + (60 * 30)) < time()) {
                //checkUser all 30 minutes
                $this->permissionHandler->isValidUserId($userId);
            } else {
                $now = $iat;
            }
        }
        $payLoad = array(
            'userid' => $userId,
            'iat' => $now,
            'nbf' => $now
        );
        if (!$keepLogin) {
            //default is one hour
            $payLoad['exp'] = $now + (60 * 60);
            $payLoad['keepLogin'] = false;
        } else {
            //one week
            $payLoad['exp'] = $now + (7 * 24 * 60 * 60);
            $payLoad['keepLogin'] = true;
        }
        $token = JWT::encode($payLoad, UserToken::KEY_STRING, UserToken::ALG);
        return $token;
    }
}
