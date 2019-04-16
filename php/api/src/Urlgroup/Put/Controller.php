<?php

namespace Datahouse\MON\Urlgroup\Put;

use \Datahouse\MON\Request as Request;
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
        $this->allowedRequestMethods = array('PUT');
    }

    /**
     * control
     *
     *
     * @return void
     */
    public function control()
    {
        $this->viewModel->setOldUrlGroupId(
            $this->readJsonParam(
                $this->request->getJsonReqParams(),
                'oldGroupId'
            )
        );
        $this->viewModel->setId(
            $this->readJsonParam(
                $this->request->getJsonReqParams(),
                'newGroupId'
            )
        );
        $this->viewModel->setUrlIds(
            array(
                $this->readJsonParam(
                    $this->request->getJsonReqParams(),
                    'urlId'
                )
            )
        );
        $this->viewModel->setLang($this->request->getLang());
        $this->viewModel->setUserId($this->userToken->getUserId());
    }
}