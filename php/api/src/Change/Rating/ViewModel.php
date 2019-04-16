<?php

namespace Datahouse\MON\Change\Rating;

use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\PermissionViewModel;
use Datahouse\MON\Types\Gen\Error;

/**
 * Class ViewModel
 *
 * @package Change
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModel extends PermissionViewModel
{
    /**
     * @var int
     */
    private $lang = 1;
    private $alertId = null;
    private $rating = null;
    private $changeId = null;
    private $rptType = 'Change/Rating';

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
     * setAlertId
     *
     * @param null $alertId alertId
     * @return void
     */
    public function setAlertId($alertId)
    {
        $this->alertId = $alertId;
    }

    /**
     * setRating
     *
     * @param null $rating rating
     * @return void
     */
    public function setRating($rating)
    {
        $this->rating = $rating;
    }

    /**
     * setChangeId
     *
     * @param null $changeId id of the change
     * @return void
     */
    public function setChangeId($changeId)
    {
        $this->changeId = $changeId;
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
            $this->validate();
            return $this->model->insertRating(
                $this->changeId,
                $this->userId,
                $this->rating
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
     * validate
     *
     * @return bool
     * @throws ValidationException
     */
    private function validate()
    {
        $this->assertId($this->alertId);
        $this->assertId($this->changeId);
        $this->assertNotEmpty($this->rating);
        return true;
    }
}
