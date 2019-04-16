<?php

namespace Datahouse\MON\Url\Free;

use Datahouse\MON\BaseController;
use \Datahouse\MON\Request as Request;

/**
 * Class Widget
 *
 * @package     Widget
 * @author      Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Controller extends BaseController
{

    /**
     * @param Model     $model     the model
     * @param ViewModel $viewModel the view model
     * @param Request   $request   the request
     */
    public function __construct(
        Model $model,
        ViewModel $viewModel,
        Request $request
    ) {
        $this->model = $model;
        $this->viewModel = $viewModel;
        $this->request = $request;
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
        $urlString = strip_tags(
            $this->readJsonParam($this->request->getJsonReqParams(), 'url')
        );
        $urlScheme = parse_url($urlString, PHP_URL_SCHEME);
        if (!isset($urlScheme)) {
            $urlString = 'http://' . $urlString;
        }
        $this->viewModel->setUrl($urlString);
        $this->viewModel->setEmail(
            strip_tags(
                $this->readJsonParam(
                    $this->request->getJsonReqParams(),
                    'email'
                )
            )
        );
    }
}
