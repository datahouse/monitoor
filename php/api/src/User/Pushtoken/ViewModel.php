<?php

namespace Datahouse\MON\User\Pushtoken;

use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\PermissionViewModel;
use Datahouse\MON\Types\Gen\Error;
use Datahouse\MON\Types\Gen\User;
use Datahouse\MON\Types\PushToken;

/**
 * Class ViewModel
 *
 * @package User
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModel extends PermissionViewModel
{
    /**
     * @var int
     */
    private $lang = 1;
    /**
     * @var PushToken
     */
    private $pushToken;
    private $rptType = 'User/Pushtoken';

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
     * setPushToken
     *
     * @param PushToken $pushToken the PushToken
     *
     * @return void
     */
    public function setPushToken($pushToken)
    {
        $this->pushToken = $pushToken;
    }

    /**
     * getData
     *
     *
     * @return bool | Error
     */
    public function getData()
    {
        try {
            $this->permissionHandler->assertRole(
                $this->userId,
                null
            );
            $this->validatePushToken($this->pushToken);
            return $this->model->handlePushToken($this->pushToken);
        } catch (PermissionException $pe) {
            return $this->handlePermissionException(
                $pe,
                array('Forbidden'),
                $this->rptType
            );
        } catch (ValidationException $ve) {
            return $this->handleValidationException(
                $ve,
                array($ve->getMessage()),
                $this->rptType
            );
        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                array('Unexpected Error'),
                $this->rptType
            );
        }
    }

    /**
     * validateAlert
     *
     * @param User $user the user
     *
     * @return void
     * @throws ValidationException
     */
    private function validatePushToken(PushToken $pushtoken)
    {
        $this->assertId($pushtoken->getUserId());
        $this->assertInt($pushtoken->getPlatform());
        if ($pushtoken->getPlatform() > 1 || $pushtoken->getPlatform() < 0) {
            throw new ValidationException(
                'invalid platform id '
            );
        }
        if ($pushtoken->getToken() == null ||
            strlen($pushtoken->getToken()) == 0
        ) {
            throw new ValidationException(
                'empty token'
            );
        }
        $this->assertBool($pushtoken->isDenied());
    }
}
