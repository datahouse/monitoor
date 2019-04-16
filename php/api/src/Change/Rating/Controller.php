<?php

namespace Datahouse\MON\Change\Rating;

use \Datahouse\MON\Request as Request;
use Datahouse\MON\UserToken;

/**
 * Class Controller
 *
 * @package Change
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
        $alertId = $this->readJsonParam(
            $this->request->getJsonReqParams(),
            'alertId'
        );
        $changeId = $this->readJsonParam(
            $this->request->getJsonReqParams(),
            'changeId'
        );
        $rating = $this->readJsonParam(
            $this->request->getJsonReqParams(),
            'rating'
        );
        //david belart filemaker kann kein json schicken
        if ($this->request->getJsonReqParams() == null) {
            if (array_key_exists('alertId', $_REQUEST)) {
                $alertId = $_REQUEST['alertId'];
            }
            if (array_key_exists('changeId', $_REQUEST)) {
                $changeId = $_REQUEST['changeId'];
            }
            if (array_key_exists('rating', $_REQUEST)) {
                $rating = $_REQUEST['rating'];
            }
        }
        $this->viewModel->setAlertId($alertId);
        $this->viewModel->setChangeId($changeId);
        $this->viewModel->setRating($rating);
    }
}
