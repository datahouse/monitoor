<?php

namespace Datahouse\MON\Register\Add;

use \Datahouse\MON\Request as Request;
use Datahouse\MON\UserToken;

/**
 * Class Controller
 *
 * @package Register
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
        $this->viewModel->setEmail(
            $this->readJsonParam(
                $this->request->getJsonReqParams(),
                'username'
            )
        );
        $this->viewModel->setPwd1(
            $this->readJsonParam(
                $this->request->getJsonReqParams(),
                'pwd1'
            )
        );
        $this->viewModel->setPwd2(
            $this->readJsonParam(
                $this->request->getJsonReqParams(),
                'pwd2'
            )
        );
        $this->viewModel->setFirstname(
            $this->readJsonParam(
                $this->request->getJsonReqParams(),
                'firstname'
            )
        );
        $this->viewModel->setLastname(
            $this->readJsonParam(
                $this->request->getJsonReqParams(),
                'lastname'
            )
        );
        $this->viewModel->setCompany(
            $this->readJsonParam(
                $this->request->getJsonReqParams(),
                'company'
            )
        );
        $this->viewModel->setPricingPlanId(
            $this->readJsonParam(
                $this->request->getJsonReqParams(),
                'pricingPlanId'
            )
        );
        $this->viewModel->setVoucherCode(
            $this->readJsonParam(
                $this->request->getJsonReqParams(),
                'voucher'
            )
        );
        $this->viewModel->setLang($this->request->getLang());
    }
}
