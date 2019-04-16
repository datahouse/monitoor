<?php

namespace Datahouse\MON\User\Login;

use Datahouse\MON\Exception\AccountExpiredException;
use Datahouse\MON\Exception\UnauthorizedException;
use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\I18\I18;
use Datahouse\MON\Types\Gen\Token;

/**
 * Class ViewModel
 *
 * @package User
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModel extends \Datahouse\MON\PermissionViewModel
{
    /**
     * @var int
     */
    private $lang = 1;
    private $email;
    private $keepLogin = false;
    private $pwd;
    private $rptType = 'User/Login';

    /**
     * @param Model $model the model
     * @param I18 $i18 the i18
     */
    public function __construct(Model $model, I18 $i18)
    {
        parent::__construct($model, null, $i18);
    }

    /**
     * setLang
     *
     * @param int $lang the lang
     *
     * @return void
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    /**
     * setKeepLogin
     *
     * @param boolean $keepLogin keepLogin
     *
     * @return void
     */
    public function setKeepLogin($keepLogin)
    {
        $this->keepLogin = $keepLogin;
    }

    /**
     * setUserIdAndPwd
     *
     * @param string $email the userid
     * @param string $pwd   the pwd
     *
     * @return void
     */
    public function setEmailAndPwd($email, $pwd)
    {
        $this->email = $email;
        $this->pwd = $pwd;
    }

    /**
     * getData
     *
     *
     * @return array
     */
    public function getData()
    {
        $token = new Token();
        try {
            $this->validateCredentials($this->email, $this->pwd);
            $userId = $this->model->login($this->email, $this->pwd);
            $token->setId($this->createToken($userId, $this->keepLogin));
        } catch (UnauthorizedException $pe) {
            return $this->handleUnauthorizedException(
                $pe,
                array($this->i18->translate('error_login', $this->lang)),
                $this->rptType
            );
        } catch (AccountExpiredException $ae) {
            return $this->handleAccountExpiredException(
                $ae,
                array($this->i18->translate('error_login_expired', $this->lang)),
                $this->rptType
            );
        } catch (ValidationException $ve) {
            return $this->handleValidationException(
                $ve,
                array('Bad Request'),
                $this->rptType
            );
        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                array('Unexpected Error'),
                $this->rptType
            );
        }
        return array('token' => $token);
    }

    /**
     * validateCredentials
     *
     * @param string $email the email
     * @param string $pwd   the pwd
     *
     * @return void
     * @throws ValidationException
     * @throws \Datahouse\MON\Exception\ValidationException
     */
    private function validateCredentials($email, $pwd)
    {
        $this->assertNotEmpty($email);
        $this->assertNotEmpty($pwd);
        if (strlen($email) < 3 || strlen($pwd) < 3) {
            throw new ValidationException('missing credentials ');
        }
    }
}
