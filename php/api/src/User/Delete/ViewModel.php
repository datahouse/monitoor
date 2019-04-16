<?php

namespace Datahouse\MON\User\Delete;

use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\PermissionViewModel;
use Datahouse\MON\Types\Gen\Error;

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
    private $id;
    private $rptType = 'User/Delete';

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
     * setId
     *
     * @param int $id the id
     *
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
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
            $this->assertIds($this->id, $this->userId);
            return $this->model->deleteUser($this->id);
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
    }
}
