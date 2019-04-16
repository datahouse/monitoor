<?php

namespace Datahouse\MON\Widget\Request;

use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\I18\I18;
use Datahouse\MON\Types\Gen\Error;

/**
 * Class ViewModel
 *
 * @package Widget
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModel extends \Datahouse\MON\ViewModel
{
    /**
     * @var int
     */
    private $lang = 1;
    private $rptType = 'Widget/Request';
    private $email;
    private $urlGroupId;

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
     * setUrlGroupId
     *
     * @param mixed $urlGroupId urlGroupId
     * @return void
     */
    public function setUrlGroupId($urlGroupId)
    {
        $this->urlGroupId = $urlGroupId;
    }

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
            $this->assertId($this->urlGroupId);
            $this->validateEmail($this->email);
            if (!$this->model->isAllowedUrlGroup($this->urlGroupId)) {
                throw new ValidationException('Group not allowed');
            }
            //subscribe to urlGroup and create user if necessary
            $hash = $this->model->addWidgetToUser(
                $this->urlGroupId,
                $this->email
            );
            if ($hash != null && $hash->getHash() != null) {
                $this->sendActivationEmail($hash->getHash(), $this->email);
                $envConf = $this->readConfigList();
                $subject = 'MON: new registration';
                $text = 'E-Mail: ' . $this->email . PHP_EOL;
                $text .= PHP_EOL . 'Preisplan: Widget';
                $text .= PHP_EOL . 'Userid: ' . $hash->getUserId();
                if ($envConf['environment'] == 'live') {
                    $from = 'no-reply@monitoor.com';
                    $mail =
                        new \eml_mail(
                            new \eml_address($from),
                            new \eml_address('mon.project@datahouse.ch'),
                            $subject,
                            $text
                        );
                    if ($this->isEnvAllowedToSendMail()) {
                        $mail->send();
                    }
                } else {
                    if ($this->isEnvAllowedToSendMail()) {
                    $from = 'no-reply@monitoor.com';
                    $mail =
                        new \eml_mail(
                            new \eml_address($from),
                            new \eml_address('peter.mueller@datahouse.ch'),
                            '(' . $envConf['environment'] . ') ' . $subject,
                            $text
                        );
                        $mail->send();
                    }
                }
                $log = new \rpt_rpt(
                    \rpt_level::L_INFO,
                    'Widget/Request'
                );
                $log->add('new registration ' . $this->email . ' ' . $hash->getUserId())->end();
            }
            return true;
        } catch (ValidationException $ve) {
            return $this->handleValidationException(
                $ve,
                array($ve->getMessage()),
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
     * validateEmail
     *
     * @param string $value the value
     *
     * @return void
     * @throws ValidationException
     */
    private function validateEmail($value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Please enter a valid email (' . $value . ')');
        }
    }

    /**
     * sendActivationEmail
     *
     * @param string $recoveryHash the hash
     * @param string $email        the hash
     *
     * @return void
     */
    private function sendActivationEmail($activationHash, $email)
    {
        $greet = $this->i18->translate('greet', $this->lang);
        $salutation = $this->i18->translate('salutation', $this->lang);
        $activationSubject = $this->i18->translate('activation_subject', $this->lang);
        $activationText = $this->i18->translate('activation_text', $this->lang);

        $envConf = $this->readConfigList();
        $emailFrom = $envConf['email_from'];
        $rootUrl = $envConf['root_url'];
        $unlockUrl = $envConf['activation_url'];
        if ($this->isEnvAllowedToSendMail()) {
        $mail =
            new \eml_mail(
                new \eml_address($emailFrom),
                new \eml_address($email),
                $activationSubject,
                $salutation . PHP_EOL . PHP_EOL .
                $activationText . $rootUrl . $unlockUrl . $activationHash .
                PHP_EOL . PHP_EOL . $greet . PHP_EOL
            );
            $mail->send();
        }
        $log = new \rpt_rpt(
            \rpt_level::L_INFO,
            'Widget/Request'
        );
        $log->add('activation link to ' . $email)->end();
    }
}
