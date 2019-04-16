<?php

namespace Datahouse\MON\User\Password;

use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Exception\OldPasswordIncorrectException;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\Types\Gen\Token;
use Datahouse\MON\Types\PwdChange;

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
     * @var PwdChange
     */
    private $pwdChange;
    private $rptType = 'User/Password';

    /**
     * @param Model             $model             the model
     * @param PermissionHandler $permissionHandler the permissionHandler
     */
    public function __construct(
        Model $model,
        PermissionHandler $permissionHandler
    ) {
        parent::__construct($model, $permissionHandler);
    }

    /**
     * getData
     *
     * @return Token
     */
    public function getData()
    {
        $token = new Token();
        try {
            if ($this->validatePassword(
                $this->pwdChange->getPwd1(),
                $this->pwdChange->getPwd2()
            )
            ) {
                $this->model->changePwd(
                    $this->pwdChange->getPwd1(),
                    $this->pwdChange->getOldPwd(),
                    $this->userId
                );
                $token->setId($this->createToken($this->userId, false));
            }
        } catch (OldPasswordIncorrectException $opie) {
            return $this->handleOldPasswordIncorrectException(
                $opie,
                array('Old Password incorrect'),
                $this->rptType
            );
        } catch (PermissionException $pe) {
            return $this->handlePermissionException(
                $pe,
                array('Forbidden'),
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
     * setPwdChange
     *
     * @param PwdChange $pwdChange pwdChange
     * @return void
     */
    public function setPwdChange(PwdChange $pwdChange)
    {
        $this->pwdChange = $pwdChange;
    }
}
