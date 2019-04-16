<?php

namespace Datahouse\MON\Contact\Send;

use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Types\Gen\Error;

/**
 * Class ViewModel
 *
 * @package Change
 * @author  Flavio Neuenschwnader (fne) <flavio.neuenschwander@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModel extends \Datahouse\MON\ViewModel
{
    /**
     * @var int
     */
    private $lang = 1;
    private $name = null;
    private $email = null;
    private $message = null;
    private $rptType = 'Contact/Send';

    public function __construct()
    {
        parent::__construct(null);
    }

    /**
     * setLang
     *
     * @param null $name name
     * @return void
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    /**
     * setName
     *
     * @param null $name name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * setEmail
     *
     * @param null $email email
     * @return void
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * setMessage
     *
     * @param null $message message
     * @return void
     */
    public function setMessage($message)
    {
        $this->message = $message;
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
            $this->validate();
            $this->sendContactRequest();
            return true;
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
     * validate
     *
     * @return bool
     */
    private function validate()
    {
        $this->assertNotEmpty($this->email);
        $this->validateEmail($this->email);
        $this->assertNotEmpty($this->message);
        $this->validateStringLength($this->message, 1000);
        $this->validateStringLength($this->name, 50);
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
    }

    /**
     * validateMessage
     *
     * @param $message   string
     * @param $maxLength int
     * @throws ValidationException
     */
    private function validateStringLength($message, $maxLength ) {
        if (strlen($message) > $maxLength ) {
            throw new ValidationException('string is too long Max: ' . $maxLength . ' String: ' . $message);
        }
    }

    /**
     * sendContactRequest
     *
     * @return bool
     */
    private function sendContactRequest() {
        $envConf = $this->readConfigList();
        $emailFrom = $envConf['email_from'];
        $emailTo = $envConf['contact_email_to'];
        $subject = $envConf['contact_email_subject'];
        if ($this->isEnvAllowedToSendMail()) {
        $mail =
            new \eml_mail(
                new \eml_address($emailFrom),
                new \eml_address($emailTo),
                $subject,
                $this->getEmailContent()
            );

            $mail->send();
        }
        $log = new \rpt_rpt(
            \rpt_level::L_INFO,
            'Contact/Send'
        );
        $log->add(
            'contact request from ' . $this->getCleanedString($this->email)
            . ' to ' . $emailTo . ' sent with content: '
            . $this->getCleanedString($this->message)
        )->end();
    }

    /**
     * getEmailContent
     *
     * @return string
     */
    private function getEmailContent() {
        $emailContent = '';
        $emailContent .= 'Name: ' . $this->getCleanedString($this->name) . "\n";
        $emailContent .= 'Email: ' . $this->getCleanedString($this->email) . "\n\n";
        $emailContent .= 'Nachricht: ' . "\n";
        $emailContent .= $this->getCleanedString($this->message);

        return $emailContent;
    }

    /**
     * getCleanedString
     *
     * @param $inputString string string which should be cleaned
     * @return string cleaned string
     */
    private function getCleanedString($inputString) {
        return htmlspecialchars($inputString);
    }
}
