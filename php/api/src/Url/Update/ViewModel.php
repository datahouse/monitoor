<?php

namespace Datahouse\MON\Url\Update;

use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\PermissionViewModel;
use Datahouse\MON\Types\Gen\Url;
use Datahouse\MON\Types\Gen\Error;

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
    private $id;
    private $rptType = 'Url/Update';
    private $urlGroupName;

    /**
     * @var Url
     */
    private $url;

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
     * setId
     *
     * @param int $id the id
     *
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * setUrl
     *
     * @param Url $url the url
     *
     * @return void
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * setUrlGroupName
     *
     * @param mixed $urlGroupName urlGroupName
     * @return void
     */
    public function setUrlGroupName($urlGroupName)
    {
        $this->urlGroupName = $urlGroupName;
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
            $this->permissionHandler->hasUrlWriteAccess(
                $this->userId,
                $this->id
            );
            if ($this->url->getUrlGroupId() > 0) {
                $this->permissionHandler->hasUrlGroupWriteAccess(
                    $this->userId,
                    $this->url->getUrlGroupId()
                );
            }
            $this->validateUrl($this->url);

            return $this->model->updateUrl(
              $this->url,
              $this->urlGroupName,
              $this->userId
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
                array($ve->getMessage()),
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
     * validateUrl
     *
     * @param Url $url the url
     *
     * @return void
     * @throws ValidationException
     */
    private function validateUrl(Url $url)
    {
        $this->assertUrl($url->getUrl());
        $domain = parse_url($url->getUrl(), PHP_URL_HOST);
        if ($this->model->checkBlackList($domain)) {
            throw new ValidationException('Url not allowed');
        }
        $title = $url->getTitle();
        if (empty($title)) {
            $this->url->setTitle($domain);
        }
        $this->assertId($url->getId());
        if ($url->getUrlGroupId() > 0) {
            $this->assertId($url->getUrlGroupId());  
        }
        if ($url->getXpath() != null) {
            $this->validateXpath($url->getXpath());
        }
    }
}
