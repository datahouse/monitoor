<?php

namespace Datahouse\MON\Alert\Add;

use \Datahouse\MON\Request as Request;
use Datahouse\MON\Types\Gen\Alert;
use Datahouse\MON\UserToken;

/**
 * Class Controller
 *
 * @package Alert
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Controller extends \Datahouse\MON\Controller\AlertController
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
        $alert = new Alert();
        $alert->setUrlGroup($this->getUrlGroup());
        $alert->setAlertShapingList($this->getAlertshapingList());
        $this->viewModel->setAlert($alert);
    }
}
