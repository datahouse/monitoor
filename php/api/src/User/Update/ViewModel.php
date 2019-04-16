<?php

namespace Datahouse\MON\User\Update;

use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\PermissionViewModel;
use Datahouse\MON\Types\Gen\Error;
use Datahouse\MON\Types\Gen\User;

/**
 * Class ViewModel
 *
 * @package Alert
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModel extends PermissionViewModel
{
    /**
     * @var int
     */
    private $lang = 1;
    private $user;
    private $rptType = 'User/Update';

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
     * setUser
     *
     * @param User $user the user
     *
     * @return void
     */
    public function setUser($user)
    {
        $this->user = $user;
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
            $this->validateUser($this->user);
            return $this->model->updateUser($this->user);
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
    private function validateUser(User $user)
    {
        $this->assertId($user->getId());
        $this->assertNotEmpty($user->getFirstName());
        $this->assertNotEmpty($user->getLastName());
        $this->validateEmail($user->getEmail(), $user->getId());
    }

    /**
     * validateEmail
     *
     * @param string $value  the value
     * @param int    $userId the userId
     *
     * @return void
     * @throws ValidationException
     */
    private function validateEmail($value, $userId)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('not a valid email ' . $value);
        }
        if (!$this->model->isUniqueEmail($value, $userId)) {
            throw new ValidationException($value . ' wird bereits verwendet');
        }
    }
}
