<?php

namespace Datahouse\MON\Urlgroup\Put;

use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\PermissionViewModel;
use Datahouse\MON\Types\Gen\Error;

/**
 * Class ViewModel
 *
 * @package Url
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
    private $oldUrlGroupId;
    private $urlIds = array();
    private $rptType = 'Urlgroup/Put';

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
     *
     * @return bool | Error
     */
    public function getData()
    {
        try {
            $this->validateIds($this->id, $this->oldUrlGroupId);
            if ($this->id != null) {
                $this->permissionHandler->hasUrlGroupWriteAccess(
                    $this->userId,
                    $this->id
                );
            }
            if ($this->oldUrlGroupId != null) {
                $this->permissionHandler->hasUrlGroupWriteAccess(
                    $this->userId,
                    $this->oldUrlGroupId
                );
            }
            return $this->model->putUrlIntoGroup($this->id, $this->urlIds);
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
     * setOldUrlGroupId
     *
     * @param int $oldUrlGroupId the old group id id
     *
     * @return void
     */
    public function setOldUrlGroupId($oldUrlGroupId)
    {
        $this->oldUrlGroupId = $oldUrlGroupId;
    }

    /**
     * setUrlIds
     *
     * @param array $urlIds the ids
     *
     * @return void
     */
    public function setUrlIds($urlIds)
    {
        $this->urlIds = $urlIds;
    }

    /**
     * validateIds
     *
     * @param int $oldId the old Id
     * @param int $newId the new Id
     *
     * @return bool
     * @throws ValidationException
     */
    private function validateIds($oldId, $newId)
    {
        $this->assertId($oldId);
        $this->assertId($newId);
        return true;
    }
}
