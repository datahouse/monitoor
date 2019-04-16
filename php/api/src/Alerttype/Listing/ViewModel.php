<?php

namespace Datahouse\MON\Alerttype\Listing;

use Datahouse\MON\Exception\PermissionException;
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
    private $rptType  = 'Alerttype/Listing';

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
            $this->permissionHandler->assertRole(
                $this->userId,
                null
            );
            return $this->model->readAlertTypeList($this->lang, $this->userId);
        } catch (PermissionException $pe) {
            $this->status = 403;
            $log = new \rpt_rpt(
                \rpt_level::E_CRITICAL,
                $this->rptType
            );
            $log->add($pe->getMessage())->end();
            $error = new Error();
            $error->setCode(403);
            $error->setMsg(array('Forbidden'));
            return $error;
        } catch (\Exception $e) {
            $this->status = 500;
            $log = new \rpt_rpt(
                \rpt_level::E_CRITICAL,
                $this->rptType
            );
            $log->add($e->getMessage())->end();
            $error = new Error();
            $error->setCode(500);
            $error->setMsg(array('Unexpected Error'));
            return $error;
        }
    }
}
