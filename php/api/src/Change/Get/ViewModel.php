<?php

namespace Datahouse\MON\Change\Get;

use Datahouse\MON\Exception\KeyNotFoundException;
use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\PermissionViewModel;
use Datahouse\MON\Types\Gen\ChangeItem;
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
    private $rptType = 'Change/Get';
    private $changeHash = null;

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
     * setChangeHash
     *
     * @param null $changeHash changeHash
     * @return void
     */
    public function setChangeHash($changeHash)
    {
        $this->changeHash = $changeHash;
    }
    /**
     * getData
     *
     *
     * @return ChangeItem | Error
     */
    public function getData()
    {
        try {
            $this->validate();
            return $this->model->getChange($this->changeHash);
        } catch (ValidationException $ve) {
            return $this->handleValidationException(
                $ve,
                array('Bad Request'),
                $this->rptType
            );
        } catch (KeyNotFoundException $ke) {
            return $this->handleKeyNotFoundException(
                $ke,
                array('Not Found'),
                $this->rptType
            );
        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                array('Unexpected Error: ' . $e->getMessage()),
                $this->rptType
            );
        }
    }

    /**
     * validate
     *
     * @return void
     * @throws ValidationException
     */
    private function validate()
    {
        if (!isset($this->changeHash) || strlen($this->changeHash) != 10) {
            throw new ValidationException('change not valid');
        }
    }
}
