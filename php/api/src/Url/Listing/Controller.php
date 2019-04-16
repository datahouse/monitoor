<?php

namespace Datahouse\MON\Url\Listing;

use \Datahouse\MON\Request as Request;
use Datahouse\MON\Types\Gen\Url;
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
        $offset = null;
        $size = null;
        $sort = null;
        if (array_key_exists('offset', $_GET)) {
            $offset = $_GET['offset'];
        }
        if (array_key_exists('size', $_GET)) {
            $size = $_GET['size'];
        }
        if (array_key_exists('sort', $_GET)) {
            $sort = $_GET['sort'];
        }
        $this->viewModel->setPagingAndSorting($offset, $size, $sort);
        $this->viewModel->setUrl($this->getUrl());
    }

    /**
     * getUrl
     *
     *
     * @return Url
     */
    private function getUrl()
    {
            $url = new Url();
        if (array_key_exists('title', $_GET)) {
            $url->setTitle($_GET['title']);
        }
        if (array_key_exists('url', $_GET)) {
            $url->setUrl($_GET['url']);
        }
        return $url;
    }
}
