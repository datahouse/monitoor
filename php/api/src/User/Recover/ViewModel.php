<?php

namespace Datahouse\MON\User\Recover;

use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\I18\I18;
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

    private $lang = 1;
    private $email;
    private $rptType = 'User/Recover';

    /**
     * @param Model $model the model
     * @param I18   $i18   the i18
     */
    public function __construct(Model $model, I18 $i18)
    {
        parent::__construct($model, $i18);
    }

    /**
     * setLang
     *
     * @param int $lang the lang
     *
     * @return void
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
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
            $hash = $this->model->createPwdRecovery($this->email);
            if ($hash != null) {
                $this->sendEmail($hash, $this->email);
            }
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
        return true;
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

    /**
     * setEmail
     *
     * @param mixed $email email
     * @return void
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * sendEmail
     *
     * @param string $recoveryHash the hash
     * @param string $email        the hash
     *
     * @return void
     */
    private function sendEmail($recoveryHash, $email)
    {
        $greet = $this->i18->translate('greet', $this->lang);
        $salutation = $this->i18->translate('salutation', $this->lang);
        $resetSubject = $this->i18->translate('reset_subject', $this->lang);
        $resetText = $this->i18->translate('reset_text', $this->lang);

        $envConf = $this->readConfigList();
        $emailFrom = $envConf['email_from'];
        $rootUrl = $envConf['root_url'];
        $resetUrl = $envConf['reset_url'];
        if ($this->isEnvAllowedToSendMail()) {
        $mail =
            new \eml_mail(
                new \eml_address($emailFrom),
                new \eml_address($email),
                $resetSubject,
                $salutation . PHP_EOL . PHP_EOL .
                $resetText . $rootUrl . $resetUrl . $recoveryHash .
                PHP_EOL . PHP_EOL . $greet . PHP_EOL
            );
            $mail->send();
        }
        $log = new \rpt_rpt(
            \rpt_level::L_INFO,
            $this->rptType
        );
        $log->add('pw recovery to ' . $email)->end();
    }
}
