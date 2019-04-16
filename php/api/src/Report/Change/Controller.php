<?php

namespace Datahouse\MON\Report\Change;

use \Datahouse\MON\Request as Request;
use Datahouse\MON\UserToken;

/**
 * Class Controller
 *
 * @package Report
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
        if (array_key_exists('urlGroupId', $_GET)) {
            $this->viewModel->setUrlGroupId($_GET['urlGroupId']);
        }
        if (array_key_exists('demo', $_GET)) {
            $this->viewModel->setDemoRequest($_GET['demo']);
        }
    }
}
