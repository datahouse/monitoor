<?php

namespace Datahouse\MON\User\Pushtoken;

use Datahouse\MON\BaseController;
use \Datahouse\MON\Request as Request;
use Datahouse\MON\Types\PushToken;
use Datahouse\MON\UserToken;

/**
 * Class Controller
 *
 * @package     User
 * @author      Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Controller extends BaseController
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
        $pushToken = new PushToken();
        $pushToken->setUserId($this->userToken->getUserId());
        $pushToken->setPlatform(
            strip_tags(
                $this->readJsonParam(
                    $this->request->getJsonReqParams(),
                    'platform'
                )
            )
        );
        $pushToken->setToken(
            strip_tags(
                $this->readJsonParam(
                    $this->request->getJsonReqParams(),
                    'pushToken'
                )
            )
        );
        $pushToken->setDenied((bool)
            strip_tags(
                $this->readJsonParam(
                    $this->request->getJsonReqParams(),
                    'pushDenied'
                )
            )
        );
        $this->viewModel->setPushToken($pushToken);
    }
}
