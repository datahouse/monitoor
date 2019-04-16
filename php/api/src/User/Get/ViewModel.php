<?php

namespace Datahouse\MON\User\Get;

use Datahouse\MON\Exception\KeyNotFoundException;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Exception\ValidationException;
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
    private $rptType = 'User/Get';

    /**
     * @param Model             $model             the model
     * @param PermissionHandler $permissionHandler the permissionHandler
     */
    public function __construct(Model $model, PermissionHandler $permissionHandler)
    {
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
     * getData
     *
     *
     * @return User | Error
     */
    public function getData()
    {
        $user = null;
        try {
            $this->permissionHandler->assertRole(
                $this->userId,
                null
            );
            $this->assertId($this->userId);
            $user = $this->model->readUser($this->userId);
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
        } catch (KeyNotFoundException $ke) {
            return $this->handleKeyNotFoundException(
                $ke,
                array('Not Found'),
                $this->rptType
            );
        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                array('Unexpected Error'),
                $this->rptType
            );
        }
        return $user;
    }
}
