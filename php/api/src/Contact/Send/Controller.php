<?php

namespace Datahouse\MON\Contact\Send;

use \Datahouse\MON\Request as Request;
use Datahouse\MON\UserToken;

/**
 * Class Controller
 *
 * @package Change
 * @author  Flavio Neuenschwnader (fne) <flavio.neuenschwander@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Controller extends \Datahouse\MON\BaseController
{

    /**
     * @param ViewModel $viewModel the view model
     * @param Request   $request   the request
     * @param UserToken $userToken the user token
     */
    public function __construct(
        ViewModel $viewModel,
        Request $request,
        UserToken $userToken
    ) {
        $this->viewModel = $viewModel;
        $this->request = $request;
        $this->userToken = $userToken;
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
        $name = $this->readJsonParam(
            $this->request->getJsonReqParams(),
            'name'
        );
        $email = $this->readJsonParam(
            $this->request->getJsonReqParams(),
            'email'
        );
        $message = $this->readJsonParam(
            $this->request->getJsonReqParams(),
            'message'
        );
        $this->viewModel->setName($name);
        $this->viewModel->setEmail($email);
        $this->viewModel->setMessage($message);
    }
}
