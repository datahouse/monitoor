<?php

namespace Datahouse\MON\User\Update;

use \Datahouse\MON\Request as Request;
use Datahouse\MON\Types\Gen\User;
use Datahouse\MON\UserToken;

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
        $user = new User();
        $user->setId($this->userToken->getUserId());
        $user->setFirstName(
            strip_tags(
                $this->readJsonParam(
                    $this->request->getJsonReqParams(),
                    'firstName'
                )
            )
        );
        $user->setLastName(
            strip_tags(
                $this->readJsonParam(
                    $this->request->getJsonReqParams(),
                    'lastName'
                )
            )
        );
        $user->setEmail(
            strip_tags(
                $this->readJsonParam(
                    $this->request->getJsonReqParams(),
                    'email'
                )
            )
        );
        $user->setMobile(
            strip_tags(
                $this->readJsonParam(
                    $this->request->getJsonReqParams(),
                    'mobile'
                )
            )
        );
        $user->setCompany(
            strip_tags(
                $this->readJsonParam(
                    $this->request->getJsonReqParams(),
                    'company'
                )
            )
        );
        $this->viewModel->setUser($user);
    }
}
