<?php

namespace Datahouse\MON\Urlgroup\Subscribe;

use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Exception\PermissionException;
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
    private $urlId = null;

    /**
     * setUrlId
     *
     * @param null $urlId urlId
     * @return void
     */
    public function setUrlId($urlId)
    {
        $this->urlId = $urlId;
    }
    private $rptType = 'Subscription/Add';

    /**
     * setId
     *
     * @param mixed $id id
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

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
            $this->validateGroupId($this->id, $this->urlId);
            return $this->model->subscribeUrlGroup(
                $this->id,
                $this->userId,
                $this->urlId,
                true
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
    }

    /**
     * validateGroupId
     *
     * @param $urlGroupId
     * @param $urlId
     *
     * @return bool
     * @throws \Exception
     */
    private function validateGroupId($urlGroupId, $urlId)
    {
        if ($urlId != null) {
            $this->assertId($urlId);
        }
        $this->assertId($urlGroupId);
        return $this->model->isSubscription($urlGroupId, $urlId);
    }
}
