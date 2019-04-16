<?php

namespace Datahouse\MON\Url\Add;

use \Datahouse\MON\Request as Request;
use Datahouse\MON\Types\Gen\Url;
use Datahouse\MON\Types\Gen\UrlGroup;
use Datahouse\MON\UserToken;

/**
 * Class Controller
 *
 * @package Url
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Controller extends \Datahouse\MON\BaseController
{

    /**
     * @param Model     $model     the model
     * @param ViewModel $viewModel the view model
     * @param Request   $request   the request
     * @param UserToken $userToken the user token
     */
    public function __construct(
        Model $model,
        ViewModel $viewModel,
        Request $request,
        UserToken $userToken
    ) {
        parent::__construct($model, $viewModel, $request, $userToken);
        $this->allowedRequestMethods = array('POST');
    }

    /**
     * control
     *
     *
     * @return void
     */
    public function control()
    {
        $this->viewModel->setLang($this->request->getLang());
        $this->viewModel->setUserId($this->userToken->getUserId());


        $urlList = array();
        $jsonUrlList = $this->readJsonParam(
            $this->request->getJsonReqParams(),
            'urls'
        );
        foreach ($jsonUrlList as $jsonUrl) {
            $url = new Url();
            $url->setUrl(array());
            $urlString = strip_tags(
                $this->readJsonParam($jsonUrl, 'url')
            );
            $urlScheme = parse_url($urlString, PHP_URL_SCHEME);
            if (!isset($urlScheme)) {
                $urlString = 'http://' . $urlString;
            }
            $url->setUrl($urlString);
            $url->setTitle(
                strip_tags($this->readJsonParam($jsonUrl, 'title')
                )
            );
            $url->setFrequency(
                $this->readJsonParam(
                    $this->request->getJsonReqParams(),
                    'frequency'
                )
            );
            $url->setUrlGroupId(
                strip_tags(
                    $this->readJsonParam($this->request->getJsonReqParams(), 'urlGroupId')
                )
            );
            $url->setXpath(
                str_replace(
                    '\'',
                    '"',
                    strip_tags(
                        $this->readJsonParam(
                            $this->request->getJsonReqParams(),
                            'xpath'
                        )
                    )
                )
            );
            $urlList[] = $url;
        }




        $this->viewModel->setUrlGroupName(
            strip_tags(
                $this->readJsonParam(
                    $this->request->getJsonReqParams(),
                    'urlGroupName'
                )
            )
        );
        //david belart filemaker kann kein json schicken TODO: old API
        if ($this->request->getJsonReqParams() == null) {
            $url = new Url();
            if (array_key_exists('url', $_REQUEST)) {
                $urlString = strip_tags($_REQUEST['url']);
                $url->setUrl($urlString);
                /*                $urlScheme = parse_url($urlString, PHP_URL_SCHEME);
                                if (!isset($urlScheme)) {
                                    $urlString = 'http://' . $urlString;
                                    $url->setUrl($urlString);
                                }*/
            }
            if (array_key_exists('urlGroupId', $_REQUEST)) {
                $url->setUrlGroupId(
                    strip_tags($_REQUEST['urlGroupId'])
                );
            }
            if (array_key_exists('urlGroupName', $_REQUEST)) {
                $this->viewModel->setUrlGroupName(
                    strip_tags($_REQUEST['urlGroupName'])
                );
            }
            if (array_key_exists('frequency', $_REQUEST)) {
                $url->setFrequency(
                    strip_tags($_REQUEST['frequency'])
                );
            }
            if (array_key_exists('title', $_REQUEST)) {
                $url->setTitle(
                    strip_tags($_REQUEST['title'])
                );
            }
            //dbe xpath
            if (array_key_exists('xpath', $_REQUEST)) {
                $url->setXpath(
                    str_replace(
                        '\'',
                        '"',
                        strip_tags($_REQUEST['xpath'])
                    )
                );
            }
            $urlList[] = $url;
        }
        $this->viewModel->setUrls($urlList);
    }
}
