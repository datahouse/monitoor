<?php

namespace Datahouse\MON\User\Pwd;

use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Types\Gen\Error;
use Datahouse\MON\Types\Gen\Token;
use Datahouse\MON\Types\PwdChange;

/**
 * Class ViewModel
 *
 * @package Alert
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModel extends \Datahouse\MON\PermissionViewModel
{
    /**
     * @var PwdChange
     */
    private $pwdChange;
    private $rptType = 'User/Pwd';

    /**
     * @param Model $model the model
     */
    public function __construct(Model $model)
    {
        parent::__construct($model, null);
    }

    /**
     * getData
     *
     * @return array | Error
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
                $userId = $this->model->changePwd(
                    $this->pwdChange->getPwd1(),
                    $this->pwdChange->getHashValue()
                );
                $token->setId($this->createToken($userId, false));
            }
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
     * @param mixed $pwdChange pwdChange
     * @return void
     */
    public function setPwdChange($pwdChange)
    {
        $this->pwdChange = $pwdChange;
    }
}
