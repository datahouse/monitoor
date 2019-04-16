<?php

namespace Datahouse\MON\Alert\Add;

use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\PermissionViewModel;
use Datahouse\MON\Types\Gen\Alert;
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
    private $rptType = 'Alert/Add';

    private $alert;

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
     * setAlert
     *
     * @param Alert $alert the alert
     *
     * @return void
     */
    public function setAlert($alert)
    {
        $this->alert = $alert;
    }

    /**
     * getData
     *
     *
     * @return int | Error
     */
    public function getData()
    {
        try {
            $this->permissionHandler->assertRole(
                $this->userId,
                null
            );
            $this->validateAlert($this->alert);
            return $this->model->createAlert($this->alert, $this->userId);
        } catch (ValidationException $ve) {
            return $this->handleValidationException(
                $ve,
                array('Bad Request'),
                $this->rptType
            );
        } catch (PermissionException $pe) {
            return $this->handlePermissionException(
                $pe,
                array('Forbidden'),
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
     * @param Alert $alert the alert
     *
     * @return void
     * @throws ValidationException
     */
    private function validateAlert(Alert $alert)
    {
        $this->assertNotEmpty($alert->getUrlGroup());
        $this->assertKeyExist('id', $alert->getUrlGroup());
        $this->assertNotEmpty($alert->getUrlGroup()['id']);
        if ($this->model->checkForExistingAlert(
            $alert->getUrlGroup()['id'],
            $this->userId
        )
        ) {
            throw new ValidationException(
                'alert exists on url group ' . $alert->getUrlGroup()['id']
            );
        }
        $this->assertNotEmpty($alert->getAlertShapingList());
        foreach ($alert->getAlertShapingList() as $alertShaping) {
            $this->assertAlertShaping($alertShaping);
        }
    }
}
