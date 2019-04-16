<?php

namespace Datahouse\MON;

use Datahouse\Framework\Model;
use Datahouse\MON\Exception\MethodNotAllowedException;

/**
 * Class Controller
 *
 * @package Controller
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
abstract class BaseController extends \Datahouse\Framework\Controller
{
    /**
     * @var Request
     */
    protected $request;
    protected $userToken;
    protected $allowedRequestMethods = array(
        'GET',
    );

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
        parent::__construct($model, $viewModel);
        $this->request = $request;
        $this->userToken = $userToken;
    }

    /**
     * readJsonParam
     *
     * @param object $json      the json object
     * @param string $paramName the parameter name
     * @return null
     */
    protected function readJsonParam($json, $paramName)
    {
        if ($json != null && property_exists($json, $paramName)) {
            return $json->$paramName;
        }
        return null;
    }

    /**
     * checks if the request method is allowed.
     *
     * @return bool
     * @throws MethodNotAllowedException
     */
    public function checkRequestMethod()
    {
        if (!in_array(
            $this->request->getRequestMethod(),
            $this->allowedRequestMethods
        )
        ) {
            throw new MethodNotAllowedException();
        }
    }
}
