<?php

namespace Datahouse\MON\User\Pwd;

use \Datahouse\MON\Request as Request;
use Datahouse\MON\Types\PwdChange;
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
        $pwdChange = new PwdChange();
        $pwdChange->setHashValue(
            $this->readJsonParam(
                $this->request->getJsonReqParams(),
                'hashValue'
            )
        );
        $pwdChange->setPwd1(
            $this->readJsonParam(
                $this->request->getJsonReqParams(),
                'pwd1'
            )
        );
        $pwdChange->setPwd2(
            $this->readJsonParam(
                $this->request->getJsonReqParams(),
                'pwd2'
            )
        );
        $this->viewModel->setPwdChange($pwdChange);
    }
}
