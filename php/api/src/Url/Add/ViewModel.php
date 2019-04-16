<?php

namespace Datahouse\MON\Url\Add;

use Datahouse\MON\Exception\UrlsExceededException;
use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\I18\I18;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\PermissionViewModel;
use Datahouse\MON\Types\Gen\Error;
use Datahouse\MON\Types\Gen\Url;

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
    private $rptType = 'Url/Add';
    /**
     * @var array
     */
    private $urls;
    private $urlGroupName;

    /**
     * @var I18
     */
    protected $i18;

    /**
     * @param Model             $model                  the model
     * @param PermissionHandler $permissionHandler      the permissionHandler
     * @param I18               $i18
     */
    public function __construct(
        Model $model,
        PermissionHandler $permissionHandler,
        I18 $i18
    ) {
        parent::__construct($model, $permissionHandler, $i18);
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
     * setUrls
     *
     * @param array $urls urls
     * @return void
     */
    public function setUrls($urls)
    {
        $this->urls = $urls;
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
     * @return array | Error
     */
    public function getData()
    {
        $groupId = null;
        $validationErrors = array();
        try {
            $this->permissionHandler->assertRole(
                $this->userId,
                null
            );
            $newUrls = array();

            foreach ($this->urls as $url) {
                if ($url->getUrlGroupId() > 0) {
                    $this->permissionHandler->hasUrlGroupWriteAccess(
                        $this->userId,
                        $url->getUrlGroupId()
                    );
                }
                try {
                    $newUrls[] = $this->validateUrl($url);
                } catch (ValidationException $ve) {
                    $validationErrors[] = $ve->getMessage();
                }
            }
            if (count($validationErrors) > 0) {
                throw new ValidationException(implode(",", $validationErrors));
            }
            return $this->model->createUrl(
                $newUrls,
                $this->urlGroupName,
                $this->userId
            );
        } catch (PermissionException $pe) {
            return $this->handlePermissionException(
                $pe,
                array('Forbidden'),
                $this->rptType
            );
        } catch (UrlsExceededException $ue) {
            return $this->handleUrlsExceededException(
                $ue,
                array($this->i18->translate('error_urls_exceeded', $this->lang)),
                $this->rptType
            );
        } catch (ValidationException $ve) {
            return $this->handleValidationException(
                $ve,
                $validationErrors,
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
     * @return Url
     * @throws ValidationException
     */
    private function validateUrl(Url $url)
    {
        $this->assertUrl($url->getUrl(), $this->lang);
        $domain = parse_url($url->getUrl(), PHP_URL_HOST);
        if ($this->model->checkBlackList($domain)) {
            throw new ValidationException('Url not allowed');
        }
        $title = $url->getTitle();
        if (empty($title)) {
            $url->setTitle($domain);
        }
        if ($url->getUrlGroupId() > 0) {
            $this->assertId($url->getUrlGroupId());
        }
        if ($url->getXpath() != null) {
            $this->validateXpath($url->getXpath());
        }
        return $url;
    }
}
