<?php

namespace Datahouse\MON\Register\Check;

use \Datahouse\MON\Request as Request;

/**
 * Class Controller
 *
 * @package Alert
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Controller extends \Datahouse\MON\BaseController
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
        $email = $this->readJsonParam(
            $this->request->getJsonReqParams(),
            'email'
        );
        $this->viewModel->setEmail($email);
    }
}
