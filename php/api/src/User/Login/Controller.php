<?php

namespace Datahouse\MON\User\Login;

use \Datahouse\MON\Request as Request;

/**
 * Class Controller
 *
 * @package User
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
        $this->viewModel->setLang($this->request->getLang());
        $this->viewModel->setEmailAndPwd(
            $this->readJsonParam(
                $this->request->getJsonReqParams(),
                'username'
            ),
            $this->readJsonParam($this->request->getJsonReqParams(), 'password')
        );
        //david belart filemaker
        if ($this->request->getJsonReqParams() == null &&
            array_key_exists('username', $_REQUEST) &&
            array_key_exists('password', $_REQUEST)
        ) {
            $this->viewModel->setEmailAndPwd(
                $_REQUEST['username'],
                $_REQUEST['password']
            );
        }
        $this->viewModel->setKeepLogin(
            $this->readJsonParam(
                $this->request->getJsonReqParams(),
                'stayLoggedIn'
            ) != null &&
            $this->readJsonParam(
                $this->request->getJsonReqParams(),
                'stayLoggedIn'
            ) == true
        );
    }
}
