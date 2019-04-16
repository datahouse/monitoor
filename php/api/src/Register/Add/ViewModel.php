<?php

namespace Datahouse\MON\Register\Add;

use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Exception\VoucherExpiredException;
use Datahouse\MON\Exception\VoucherInvalidException;
use Datahouse\MON\I18\I18;
use Datahouse\MON\Types\Gen\Error;

/**
 * Class ViewModel
 *
 * @package Register
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModel extends \Datahouse\MON\ViewModel
{
    private $email;
    private $lang = 1;
    private $rptType = 'Register/Add';
    private $pwd1;
    private $pwd2;
    private $firstname;
    private $lastname;
    private $company;
    private $pricingPlanId;
    private $voucherCode;

    /**
     * setVoucherCode
     *
     * @param mixed $voucherCode voucherCode
     * @return void
     */
    public function setVoucherCode($voucherCode)
    {
        $this->voucherCode = $voucherCode;
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
     * setPricingPlanId
     *
     * @param mixed $pricingPlanId pricingPlanId
     * @return void
     */
    public function setPricingPlanId($pricingPlanId)
    {
        $this->pricingPlanId = $pricingPlanId;
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
     * setCompany
     *
     * @param mixed $company company
     * @return void
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * setPwd1
     *
     * @param mixed $pwd1 pwd1
     * @return void
     */
    public function setPwd1($pwd1)
    {
        $this->pwd1 = $pwd1;
    }

    /**
     * setPwd2
     *
     * @param mixed $pwd2 pwd2
     * @return void
     */
    public function setPwd2($pwd2)
    {
        $this->pwd2 = $pwd2;
    }

    /**
     * setFirstname
     *
     * @param mixed $firstname pwd2
     * @return void
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * setLastname
     *
     * @param mixed $lastname pwd2
     * @return void
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
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
            if ($this->voucherCode != null) {
                $this->validateVoucher($this->voucherCode);
            }
            $this->validateEmail($this->email);
            $this->validatePassword($this->pwd1, $this->pwd2);
            $this->validateName($this->firstname, 'firstname');
            $this->validateName($this->lastname, 'lastname');
            $this->assertId($this->pricingPlanId);
            $hash = $this->model->createUser(
                $this->email,
                $this->pwd1,
                $this->firstname,
                $this->lastname,
                $this->company,
                $this->pricingPlanId,
                $this->voucherCode
            );
            if ($hash != null && $hash->getHash() != null) {
                $this->sendEmail($hash->getHash(), $this->email);
                $envConf = $this->readConfigList();
                $subject = 'MON: new registration';
                $text = 'E-Mail: ' . $this->email . PHP_EOL . 'Vorname: ' .
                    $this->firstname . PHP_EOL . 'Nachname: ' .
                    $this->lastname . PHP_EOL . 'Userid: ' . $hash->getUserId();
                if (isset($this->company)) {
                    $text .= PHP_EOL . 'Firma: ' . $this->company;
                }
                $text .= PHP_EOL . 'Preisplan: ' . $this->pricingPlanId;
                if ($envConf['environment'] == 'live') {
                    $from = 'no-reply@monitoor.com';
                    $mail =
                        new \eml_mail(
                            new \eml_address($from),
                            new \eml_address('mon.project@datahouse.ch'),
                            $subject,
                            $text
                        );
                        $mail->send();
                } else {
                    $from = 'no-reply@monitoor.com';
                    if ($this->isEnvAllowedToSendMail()) {
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
                    'Register/Add'
                );
                $log->add('new registration ' . $this->email . ' ' . $this->firstname . ' ' . $this->lastname . ' ' . $hash->getUserId())->end();
            }
        } catch (ValidationException $ve) {
            return $this->handleValidationException(
                $ve,
                array('Bad Request'),
                $this->rptType
            );
        } catch (VoucherExpiredException $ve) {
            return $this->handleVoucherExpiredException(
                $ve,
                array($this->i18->translate('error_voucher_expired', $this->lang)),
                $this->rptType
            );
        }
        catch (VoucherInvalidException $ve) {
            return $this->handleVoucherInvalidException(
                $ve,
                array($this->i18->translate('error_voucher_invalid', $this->lang)),
                $this->rptType
            );
        }catch (\Exception $e) {
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
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('email not valid ' . $value);
        }
        if (!$this->model->isEmailUnique($value)) {
            throw new ValidationException('email in use ' . $value);
        }
    }

    /**
     * validateVoucher
     *
     * @param string $value the value
     *
     * @return void
     * @throws VoucherExpiredException
     * @throws VoucherInvalidException
     */
    private function validateVoucher($value)
    {
        if (strlen($value) != 8) {
            throw new VoucherInvalidException();
        }
        $voucherUsed = $this->model->getVoucher($value);
        if ($voucherUsed != null) {
            throw new VoucherExpiredException();
        }
    }

    private function validateName($name, $type)
    {
        if (!isset($name) || $name === '') {
            throw new ValidationException($type . ' not set');
        }
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
                $activationText . $rootUrl . $unlockUrl . $recoveryHash .
                PHP_EOL . PHP_EOL . $greet . PHP_EOL
            );
            $mail->send();
        }
        $log = new \rpt_rpt(
            \rpt_level::L_INFO,
            'Register/Add'
        );
        $log->add('activation link to ' . $email)->end();
    }
}
