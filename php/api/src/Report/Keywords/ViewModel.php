<?php

namespace Datahouse\MON\Report\Keywords;

use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\PermissionViewModel;
use Datahouse\MON\Types\Gen\Error;

/**
 * Class ViewModel
 *
 * @package Report
 * @author  Flavio Neuenschwander (fne) <flavio.neuenschwander@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModel extends PermissionViewModel
{
    /**
     * @var int
     */
    private $lang = 1;
    private $rptType = 'Report/Keywords';
    private $urlGroupId;

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
            return $this->model->readKeywordList($this->urlGroupId, $this->userId);
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
