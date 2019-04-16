<?php

namespace Datahouse\MON\Register\Check;

use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Types\Gen\Error;

/**
 * Class ViewModel
 *
 * @package Alert
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModel extends \Datahouse\MON\ViewModel
{

    private $email;
    private $rptType = 'Register/Check';

    /**
     * @param Model $model the model
     */
    public function __construct(Model $model)
    {
        parent::__construct($model);
    }

    /**
     * getData
     *
     *
     * @return bool | Error
     */
    public function getData()
    {
        try {
            $this->validateEmail($this->email);
            return $this->model->isUniqueEmail($this->email);
        } catch (ValidationException $ve) {
            return $this->handleValidationException(
                $ve,
                array('Bad Request'),
                $this->rptType
            );
        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                array('Unexpected Error'),
                $this->rptType
            );
        }
    }

    /**
     * setEmail
     *
     * @param string $email email
     * @return void
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }


    /**
     * validateEmail
     *
     * @param string $value the value
     *
     * @return void
     * @throws ValidationException
     */
    private function validateEmail($value)
    {
        if (!preg_match(
            '/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]{2,})+$/',
            $value
        )
        ) {
            throw new ValidationException('email not valid ' . $value);
        }
    }
}
