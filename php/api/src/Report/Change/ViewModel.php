<?php

namespace Datahouse\MON\Report\Change;

use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\PermissionViewModel;
use Datahouse\MON\Types\Gen\Error;

/**
 * Class ViewModel
 *
 * @package Report
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModel extends PermissionViewModel
{
    /**
     * @var int
     */
    private $lang = 1;
    private $rptType = 'Report/Change';

    private $urlGroupId;
    private $demoRequest = false;

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
     * setDemoRequest
     *
     * @param bool $demoRequest the demo req flag
     *
     * @return void
     */
    public function setDemoRequest($demoRequest)
    {
        if (isset($demoRequest)) {
            $this->demoRequest =
                filter_var($demoRequest, FILTER_VALIDATE_BOOLEAN);
        }
    }

    /**
     * getData
     *
     *
     * @return array() | Error
     */
    public function getData()
    {
        try {
            if (isset($this->urlGroupId)) {
                $this->assertId($this->urlGroupId);
                $this->permissionHandler->hasUrlGroupReadAccess(
                    $this->userId,
                    $this->urlGroupId
                );
            }
            return $this->model->readReportList($this->urlGroupId, $this->userId, $this->demoRequest);
        } catch (PermissionException $pe) {
            return $this->handlePermissionException(
                $pe,
                array('Forbidden'),
                $this->rptType
            );
        } catch (ValidationException $ve) {
            return $this->handleValidationException(
                $ve,
                array('Bad request'),
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
     * setUrlGroupId
     *
     * @param int $urlGroupId urlGroupId
     * @return void
     */
    public function setUrlGroupId($urlGroupId)
    {
        $this->urlGroupId = $urlGroupId;
    }
}
