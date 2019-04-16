<?php

namespace Datahouse\MON\Urlgroup\Add;

use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\PermissionViewModel;
use Datahouse\MON\Types\Gen\Error;
use Datahouse\MON\Types\Gen\UrlGroup;

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

    private $urlGroup;
    private $rptType = 'Urlgroup/Add';

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
     * setUrlgroup
     *
     * @param Urlgroup $urlGroup the url group
     *
     * @return void
     */
    public function setUrlGroup($urlGroup)
    {
        $this->urlGroup = $urlGroup;
    }

    /**
     * getData
     *
     *
     * @return UrlGroup | Error
     */
    public function getData()
    {
        try {
            $this->permissionHandler->assertRole(
                $this->userId,
                null
            );
            $this->validateUrlGroup($this->urlGroup);
            return $this->model->createUrlGroup($this->urlGroup, $this->userId);
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
     * validateUrlGroup
     *
     * @param UrlGroup $urlGroup the url group
     *
     * @return void
     * @throws ValidationException
     */
    private function validateUrlGroup(UrlGroup $urlGroup)
    {
        $this->assertNotEmpty($urlGroup->getTitle());
    }
}
