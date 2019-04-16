<?php

namespace Datahouse\MON\Data\Push;

use \Datahouse\MON\Request as Request;
use Datahouse\MON\Types\Gen\ExternalData;
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
        $data = array();
        $dataList = $this->readJsonParam(
            $this->request->getJsonReqParams(),
            'data'
        );
        if ($dataList != null) {
            foreach ($dataList as $jsonData) {
                $exData = new ExternalData();
                $exData->setAddition(
                    $this->readJsonParam($jsonData, 'addition')
                );
                $exData->setDeletion(
                    $this->readJsonParam($jsonData, 'deletion')
                );
                $exData->setTimestamp(
                    $this->readJsonParam($jsonData, 'timestamp')
                );
                $data[] = $exData;
            }
        }
        $urlId = $this->readJsonParam(
            $this->request->getJsonReqParams(),
            'id'
        );
        $this->viewModel->setData($data);
        $this->viewModel->setUrlId($urlId);
    }
}
