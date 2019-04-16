<?php

namespace Datahouse\MON\Change\Listing;

use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\PermissionViewModel;
use Datahouse\MON\Types\ChangeFilter;
use Datahouse\MON\Types\Gen\ChangeList;
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
    private $offset = 0;
    private $size = null;
    private $sorting = '';
    private $changeFilter;
    private $rptType = 'Change/Listing';
    private $demoRequest = false;
    private $showFavorites = false;


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
     * setShowFavorites
     *
     * @param boolean $showFavorites showFavorites
     * @return void
     */
    public function setShowFavorites($showFavorites)
    {
        $this->showFavorites = $showFavorites;
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
     * setChangeFilter
     *
     * @param ChangeFilter $changeFilter changeFilter
     * @return void
     */
    public function setChangeFilter(ChangeFilter $changeFilter)
    {
        $this->changeFilter = $changeFilter;
    }

    /**
     * setPagingAndSorting
     *
     * @param int    $offset  the offset
     * @param int    $size    the size
     * @param string $sorting the sorting
     *
     * @return void
     */
    public function setPagingAndSorting($offset, $size, $sorting)
    {
        if ($offset != null && is_numeric($offset)) {
            $this->offset = $offset;
        }
        if ($size != null && is_numeric($size)) {
            $this->size = $size;
        }
        if ($sorting != null) {
            $this->sorting = $sorting;
        }
    }

    /**
     * getData
     *
     *
     * @return ChangeList | Error
     */
    public function getData()
    {
        try {
            $this->validate($this->changeFilter);
            $this->handlePermissions($this->changeFilter, $this->userId);
            return $this->model->readChangeList(
                $this->changeFilter,
                $this->offset,
                $this->size,
                $this->sorting,
                $this->userId,
                $this->demoRequest,
                $this->showFavorites
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
                array('Unexpected Error: ' . $e->getMessage()),
                $this->rptType
            );
        }
    }

    /**
     * handlePermissions
     *
     * @param ChangeFilter $changeFilter the filter
     * @param int          $userId       the user id
     *
     * @return bool
     */
    private function handlePermissions(ChangeFilter &$changeFilter, $userId) {
        if ($changeFilter->getUrlGroupId() != null) {
            $this->permissionHandler->hasUrlGroupReadAccess(
                $userId,
                $changeFilter->getUrlGroupId()
            );
        }
        if ($changeFilter->getUrlId() != null) {
            $this->permissionHandler->hasUrlReadAccess(
                $userId,
                $changeFilter->getUrlId()
            );
        }
        return true;
    }

    /**
     * validate
     *
     * @param ChangeFilter $changeFilter the change Filter
     *
     * @return void
     * @throws ValidationException
     */
    private function validate(ChangeFilter &$changeFilter)
    {
        if (($changeFilter->getUrlId() == null &&
            $changeFilter->getUrlGroupId() == null &&
            $changeFilter->getAlertId() == null &&
            $changeFilter->getStartDate() == null &&
            !isset($this->size)) &&
            !$this->showFavorites
        ) {
            throw new ValidationException('no filter criteria');
        }
        if ($changeFilter->getUrlId() != null) {
            $this->assertId($changeFilter->getUrlId());
        }
        if ($changeFilter->getAlertId() != null) {
            $this->assertId($changeFilter->getAlertId());
        }
        if ($changeFilter->getUrlGroupId() != null) {
            $this->assertId($changeFilter->getUrlGroupId());
        }
        if ($changeFilter->getStartDate() != null) {
            $time = strtotime($changeFilter->getStartDate());
            if ($time == false) {
                throw new ValidationException('not a valid date');
            }
            $d = date('Y-m-d', $time);
            $changeFilter->setStartDate($d);
        }
    }
}
