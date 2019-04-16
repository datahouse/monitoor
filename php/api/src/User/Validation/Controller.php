<?php

namespace Datahouse\MON\User\Validation;

use \Datahouse\MON\Request as Request;
use Datahouse\MON\UserToken;

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
        $this->viewModel->setIsValidToken($this->userToken->isIsValidToken());
        $this->viewModel->setIat($this->userToken->getIat());
        $this->viewModel->setUserId($this->userToken->getUserId());
        $this->viewModel->setKeepLogin($this->userToken->getKeepLogin());
        if (array_key_exists('page', $_GET)) {
            $page = explode('/', ltrim($_GET['page'], '/'));
            if (count($page) > 0 && strlen($page[0]) > 0) {
                $this->viewModel->setPage($page[0]);
            }
        }
    }
}
