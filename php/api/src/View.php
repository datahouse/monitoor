<?php

namespace Datahouse\MON;

use \Datahouse\Libraries\JSON as JSON;

/**
 * Class View
 *
 * @package View
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class View extends \Datahouse\Framework\View
{

    /**
     * @var JSON\Converter
     */
    private $converter;

    /**
     * @param ViewModel      $viewModel the view model
     * @param JSON\Converter $converter the json converter
     */
    public function __construct(ViewModel $viewModel, JSON\Converter $converter)
    {
        parent::__construct($viewModel);
        $this->converter = $converter;
    }

    /**
     * getOutput
     *
     *
     * @return mixed
     */
    public function getOutput()
    {
        $data = $this->viewModel->getData();
        $status = $this->viewModel->getStatus();
        header("HTTP/1.1 " . $status . " " . $this->requestStatus($status));
        header('Content-Type: application/json; charset=utf-8');
        return $this->converter->encode($data);
    }

    /**
     * requestStatus
     *
     * @param int $code the error code
     *
     * @return string
     */
    private function requestStatus($code)
    {
        $status = array(
            200 => 'OK',
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            405 => 'Method not allowed',
            420 => 'Policy Not Fulfilled',
            500 => 'Unexpected Server Error',
        );
        return ($status[$code]) ? $status[$code] : $status[500];
    }
}
