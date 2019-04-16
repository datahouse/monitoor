<?php

namespace Datahouse\MON\Register\Activate;

use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Types\Gen\Error;
use Datahouse\MON\Types\Gen\Token;

/**
 * Class ViewModel
 *
 * @package Register
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModel extends \Datahouse\MON\PermissionViewModel
{
    private $activationHash;

    private $rptType = 'Register/Activate';

    /**
     * @param Model $model the model
     */
    public function __construct(Model $model)
    {
        parent::__construct($model, null);
    }

    /**
     * getData
     *
     * @return array | Error
     */
    public function getData()
    {
        try {
            if ($this->assertNotEmpty($this->activationHash)) {
                $userId = $this->model->activateUser($this->activationHash);
                if (isset($userId)) {
                    $token = new Token();
                    $token->setId($this->createToken($userId, false));
                    $log = new \rpt_rpt(
                        \rpt_level::L_INFO,
                        'Register/Activate'
                    );
                    $log->add('user ' . $this->userId . ' activated ')->end();
                    return array('token' => $token);
                }
            }
            throw new PermissionException('no user has been generated');
        } catch (PermissionException $pe) {
            return $this->handlePermissionException(
                $pe,
                array('Forbidden'),
                $this->rptType
            );
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
     * setActivationHash
     *
     * @param string $activationHash activationHash
     * @return void
     */
    public function setActivationHash($activationHash)
    {
        $this->activationHash = $activationHash;
    }
}
