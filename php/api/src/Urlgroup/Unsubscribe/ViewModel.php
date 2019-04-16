<?php

namespace Datahouse\MON\Urlgroup\Unsubscribe;

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
    private $rptType = 'Urlgroup/Unsubscribe';
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
            $this->permissionHandler->hasUrlGroupReadAccess(
                $this->userId,
                $this->id
            );
            $this->assertUrlGroup($this->id, $this->urlId);
            return $this->model->subscribeUrlGroup(
                $this->id,
                $this->userId,
                $this->urlId,
                false
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
     * assertUrlGroup
     *
     * @param int $urlGroupId the url group id
     * @param int $urld the url
     *
     * @return bool
     * @throws ValidationException
     * @throws \Exception
     */
    private function assertUrlGroup($urlGroupId, $urlId)
    {
        if ($urlId != null) {
            $this->assertId($urlId);
        }
        $this->assertId($this->id);
        $this->model->isSubscription($urlGroupId, $urlId);
        return true;
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
}
