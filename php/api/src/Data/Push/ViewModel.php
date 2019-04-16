<?php

namespace Datahouse\MON\Data\Push;

use Datahouse\MON\Exception\ValidationException;
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
    private $rptType = 'Data/Push';

    private $data = array();

    /**
     * @var int
     */
    private $urlId;

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
     * setUrlId
     *
     * @param int $urlId urlId
     * @return void
     */
    public function setUrlId($urlId)
    {
        $this->urlId = $urlId;
    }

    /**
     * setData
     *
     * @param array $data the ddat
     *
     * @return void
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * getData
     *
     *
     * @return void | Error
     */
    public function getData()
    {
        try {
            $this->assertId($this->urlId);
            $this->validateData();
            $this->permissionHandler->hasUrlWriteAccess(
                $this->userId,
                $this->urlId
            );
            $this->model->insertProviderData(
                $this->userId,
                $this->urlId,
                $this->data
            );
        } catch (ValidationException $ve) {
            return $this->handleValidationException(
                $ve,
                array($ve->getMessage()),
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
     * validateData
     *
     *
     * @return void
     * @throws ValidationException
     */
    private function validateData()
    {
        foreach ($this->data as $item) {
            $this->assertNotEmpty($item->getTimestamp());
            $time = strtotime($item->getTimestamp());
            if ($time == false) {
                throw new ValidationException('not a valid date');
            }
            $del = $item->getDeletion();
            $add = $item->getAddition();
            if (empty($add) && empty($del)) {
                throw new ValidationException('no input data sent');
            }
        }
    }
}
