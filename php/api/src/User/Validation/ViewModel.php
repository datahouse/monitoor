<?php

namespace Datahouse\MON\User\Validation;

use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Permission\PermissionHandler;
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
    protected $isValidToken = false;
    private $keepLogin = false;
    private $page = null;
    private $iat = null;
    private $rptType = 'User/Validation';

    /**
     * constructor
     *
     * @param PermissionHandler $permissionHandler the permissionHandler
     */
    public function __construct(PermissionHandler $permissionHandler)
    {
        // Note: cannot use the parent constructor due to $model == null.
        $this->model = null;
        $this->permissionHandler = $permissionHandler;
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
     * setIat
     *
     * @param \DateTime $iat iat
     * @return void
     */
    public function setIat($iat)
    {
        $this->iat = $iat;
    }

    /**
     * setKeepLogin
     *
     * @param bool $keepLogin the keep login flag
     *
     * @return void
     */
    public function setKeepLogin($keepLogin)
    {
        $this->keepLogin = $keepLogin;
    }

    /**
     * setPage
     *
     * @param string $page the page
     *
     * @return void
     */
    public function setPage($page)
    {
        $this->page = $page;
    }

    /**
     * getData
     *
     * @return array
     */
    public function getData()
    {
        try {
            //renew token
            $token = null;
            if ($this->isValidToken) {
                $token = new Token();
                $token->setId(
                    $this->createToken(
                        $this->userId,
                        $this->keepLogin,
                        $this->iat
                    )
                );
                return array('token' => $token);
            }
            if (isset($this->page) &&
                ($this->page == 'product' ||
                    $this->page == 'prices' ||
                    $this->page == 'developer' ||
                    $this->page == 'login' ||
                    $this->page == 'contact' ||
                    $this->page == 'impressum' ||
                    $this->page == 'disclaimer' ||
                    $this->page == 'passwordRecovery' ||
                    $this->page == 'passwordReset' ||
                    $this->page == 'registration' ||
                    $this->page == 'share' ||
                    $this->page == 'activate')
            ) {
                return array('token' => $token);
            }
        } catch (PermissionException $pe) {
            return $this->handlePermissionException(
                $pe,
                array('Forbidden'),
                $this->rptType
            );
        }
        $this->status = 401;
        return array('token' => $token);
    }

    /**
     * setIsValidToken
     *
     * @param bool $isValidToken request with valid token?
     *
     * @return void
     */
    public function setIsValidToken($isValidToken)
    {
        $this->isValidToken = $isValidToken;
    }
}
