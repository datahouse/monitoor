<?php

namespace Datahouse\MON\Url\Listing;

use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\PermissionViewModel;
use Datahouse\MON\Types\Gen\Error;
use Datahouse\MON\Types\Gen\Url;
use Datahouse\MON\Types\Gen\UrlList;

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
    private $offset = 0;
    private $size = null;
    private $sorting = '';
    private $url;
    private $rptType = "Url/Listing";

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
     * setUrl
     *
     * @param Url $url the url
     * @return void
     */
    public function setUrl($url)
    {
        $this->url = $url;
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
     * @return UrlList | Error
     */
    public function getData()
    {
        try {
            $this->permissionHandler->assertRole(
                $this->userId,
                null
            );
            return $this->model->readUrlList(
                $this->offset,
                $this->size,
                $this->sorting,
                $this->url,
                $this->userId
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
}
