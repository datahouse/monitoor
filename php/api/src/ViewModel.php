<?php

namespace Datahouse\MON;

use Datahouse\Framework\Model;
use Datahouse\MON\Exception\AccountExpiredException;
use Datahouse\MON\Exception\KeyNotFoundException;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Exception\UnauthorizedException;
use Datahouse\MON\Exception\UrlsExceededException;
use Datahouse\MON\Exception\ValidationException;
use Datahouse\MON\Exception\OldPasswordIncorrectException;
use Datahouse\MON\Exception\VoucherExpiredException;
use Datahouse\MON\Exception\VoucherInvalidException;
use Datahouse\MON\I18\I18;
use Datahouse\MON\Types\Gen\AlertShaping;
use Datahouse\MON\Types\Gen\Error;

/**
 * Class ViewModel
 *
 * @package     ViewModel
 * @author      Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
abstract class ViewModel extends \Datahouse\Framework\ViewModel
{
    protected $status = 200;
    protected $userId;

    /**
     * @var I18
     */
    protected $i18;

    /**
     * @param \Datahouse\Framework\Model $model the model
     * @param I18                        $i18   internationalization text
     *
     */
    public function __construct(Model $model = null, I18 $i18 = null)
    {
        $this->i18 = $i18;
        parent::__construct($model);
    }

    /**
     * getData
     *
     *
     * @return mixed
     */
    abstract public function getData();

    /**
     * getStatus
     *
     *
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * setUserId
     *
     * @param int $userId the userid
     *
     * @return void
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * assertNotEmtpy
     *
     * @param mixed $value the value
     *
     * @return bool
     * @throws ValidationException
     */
    protected function assertNotEmpty($value)
    {
        if (isset($value)) {
            return true;
        }
        throw new ValidationException(
            'empty value'
        );
    }

    /**
     * assertUrl
     *
     * @param string $url the url
     *
     * @return bool
     * @throws ValidationException
     */
    protected function assertUrl($url, $lang = null)
    {
        $pattern =
            '/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i';
        //if (!filter_var($url), FILTER_VALIDATE_URL) === false) {
        if (preg_match($pattern, $url)) {
            $file_headers = @get_headers($url);
            if (!$file_headers ||
                (strpos($file_headers[0], '404'))
            ) {
                $msg = 'page ' . $url . ' does not exists';
                if (isset($lang)) {
                    $msg = $url . $this->i18->translate('url_not_found_error', $lang);
                }
                throw new ValidationException($msg);
            }
            return true;
        } else {
            $msg = $url . ' is not valid';
            if (isset($lang)) {
                $msg = $url . $this->i18->translate('url_not_valid_error', $lang);
            }
            throw new ValidationException($msg);
        }
    }

    /**
     * assertKeyExist
     *
     * @param string $key the key
     * @param array  $arr the array
     *
     * @return bool
     * @throws ValidationException
     */
    protected function assertKeyExist($key, $arr)
    {
        if (array_key_exists($key, $arr)) {
            return true;
        }
        throw new ValidationException(
            'key not exists'
        );
    }

    /**
     * validatePassword
     *
     * @param string $pwd1 the pwd
     * @param string $pwd2 the pwd
     *
     * @return bool
     * @throws ValidationException
     */
    protected function validatePassword($pwd1, $pwd2)
    {
        if (!isset($pwd1) ||
            preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $pwd1) != 1
        ) {
            throw new ValidationException($pwd1 . ' not valid pwd');
        }
        if ($pwd1 !== $pwd2) {
            throw new ValidationException('pwds not equal');
        }
        return true;
    }

    /**
     * assertId
     *
     * @param int $id the id
     *
     * @return bool
     * @throws ValidationException
     */
    protected function assertId($id)
    {
        if (is_numeric($id) && intval($id) > 0) {
            return true;
        }
        throw new ValidationException(
            'invalid id ' . $id
        );
    }

    /**
     * assertInt
     *
     * @param $int
     *
     * @return bool
     * @throws ValidationException
     */
    protected function assertInt($int)
    {
        if (is_numeric($int)) {
            return true;
        }
        throw new ValidationException(
            'invalid int ' . $int
        );
    }

    /**
     * assertBool
     *
     * @param $bool
     *
     * @return bool
     * @throws ValidationException
     */
    protected function assertBool($bool)
    {
        if (is_bool($bool)) {
            return true;
        }
        throw new ValidationException(
            'not a bool value'
        );
    }

    /**
     * assertIds
     *
     * @param int $id     the id
     * @param int $userId the userId
     *
     * @return bool
     * @throws ValidationException
     * @throws \Datahouse\MON\Exception\ValidationException
     */
    protected function assertIds($id, $userId)
    {
        $this->assertId($id);
        if (intval($id) == intval($userId)) {
            return true;
        }
        throw new ValidationException(
            'id ' . $id . ' not the same as userid ' . $userId
        );
    }

    /**
     * assertAlertShaping
     *
     * @param AlertShaping $alertShaping the AlertShaping
     *
     * @return bool
     * @throws ValidationException
     */
    protected function assertAlertShaping(AlertShaping $alertShaping)
    {
        $this->assertId($alertShaping->getAlertOption()->getId());
        if ($alertShaping->getAlertOption()->getId() == 2) {
            $this->assertNotEmpty($alertShaping->getKeywords());
        }
        if ($alertShaping->getAlertOption()->getId() == 3) {
            $this->assertNotEmpty($alertShaping->getAlertThreshold());
            $threshold = intval($alertShaping->getAlertThreshold());
            if ($threshold >= 0 && $threshold <= 100) {
                return true;
            }
            throw new ValidationException(
                'threshold not valid ' . $alertShaping->getAlertThreshold()
            );
        }
        return true;
    }

    /**
     * handlePermissionException
     *
     * @param PermissionException $exception  the Exception
     * @param array               $msg        message array
     * @param string              $identifier the identifier for the logfile
     *
     * @return Error
     */
    protected function handlePermissionException(
        PermissionException $exception,
        array $msg,
        $identifier
    ) {
        $this->status = 403;
        $log = new \rpt_rpt(
            \rpt_level::E_CRITICAL,
            $identifier
        );
        $log->add($exception->getMessage())->end();
        $error = new Error();
        $error->setCode(403);
        $error->setMsg(array('keine Berechtigung für diese Aktion'));
        return $error;
    }

    /**
     * handlePermissionException
     *
     * @param UnauthorizedException $exception  the Exception
     * @param array                 $msg        message array
     * @param string                $identifier the identifier for the logfile
     *
     * @return Error
     */
    protected function handleUnauthorizedException(
        UnauthorizedException $exception,
        array $msg,
        $identifier
    ) {
        $this->status = 401;
        $log = new \rpt_rpt(
            \rpt_level::E_CRITICAL,
            $identifier
        );
        $log->add($exception->getMessage())->end();
        $error = new Error();
        $error->setCode(401);
        $error->setMsg($msg);
        return $error;
    }

    /**
     * handleAccountExpiredException
     *
     * @param AccountExpiredException $exception  the Exception
     * @param array                   $msg        message array
     * @param string                  $identifier the identifier for the logfile
     *
     * @return Error
     */
    protected function handleAccountExpiredException(
        AccountExpiredException $exception,
        array $msg,
        $identifier
    ) {
        $this->status = 401;
        $log = new \rpt_rpt(
            \rpt_level::E_CRITICAL,
            $identifier
        );
        $log->add($exception->getMessage())->end();
        $error = new Error();
        $error->setCode(401);
        $error->setMsg($msg);
        return $error;
    }

    /**
     * handleKeyNotFoundException
     *
     * @param KeyNotFoundException $exception  the Exception
     * @param array                $msg        message array
     * @param string               $identifier the identifier for the logfile
     *
     * @return Error
     */
    protected function handleKeyNotFoundException(
        KeyNotFoundException $exception,
        array $msg,
        $identifier
    ) {
        $this->status = 404;
        $log = new \rpt_rpt(
            \rpt_level::E_CRITICAL,
            $identifier
        );
        $log->add($exception->getMessage())->end();
        $error = new Error();
        $error->setCode(404);
        $error->setMsg($msg);
        return $error;
    }

    /**
     * handleValidationException
     *
     * @param ValidationException $exception  the Exception
     * @param array               $msg        message array
     * @param string              $identifier the identifier for the logfile
     *
     * @return Error
     */
    protected function handleValidationException(
        ValidationException $exception,
        array $msg,
        $identifier
    ) {
        $this->status = 400;
        $log = new \rpt_rpt(
            \rpt_level::E_CRITICAL,
            $identifier
        );
        $log->add($exception->getMessage())->end();
        $error = new Error();
        $error->setCode(400);
        $error->setMsg($msg);
        return $error;
    }

    /**
     * handleVoucherExpiredException
     *
     * @param VoucherExpiredException $exception  the Exception
     * @param array                   $msg        message array
     * @param string                  $identifier the identifier for the logfile
     *
     * @return Error
     */
    protected function handleVoucherExpiredException(
        VoucherExpiredException $exception,
        array $msg,
        $identifier
    ) {
        $this->status = 400;
        $log = new \rpt_rpt(
            \rpt_level::E_CRITICAL,
            $identifier
        );
        $log->add($exception->getMessage())->end();
        $error = new Error();
        $error->setCode(400);
        $error->setMsg($msg);
        return $error;
    }

    /**
     * handleUrlsExceededException
     *
     * @param UrlsExceededException $exception  the Exception
     * @param array                 $msg        message array
     * @param string                $identifier the identifier for the logfile
     *
     * @return Error
     */
    protected function handleUrlsExceededException(
        UrlsExceededException $exception,
        array $msg,
        $identifier
    ) {
        $this->status = 403;
        $log = new \rpt_rpt(
            \rpt_level::E_CRITICAL,
            $identifier
        );
        $log->add($exception->getMessage())->end();
        $error = new Error();
        $error->setCode(403);
        $error->setMsg($msg);
        return $error;
    }

    /**
     * handleVoucherInvalidException
     *
     * @param VoucherInvalidException $exception  the Exception
     * @param array                   $msg        message array
     * @param string                  $identifier the identifier for the logfile
     *
     * @return Error
     */
    protected function handleVoucherInvalidException(
        VoucherInvalidException $exception,
        array $msg,
        $identifier
    ) {
        $this->status = 400;
        $log = new \rpt_rpt(
            \rpt_level::E_CRITICAL,
            $identifier
        );
        $log->add($exception->getMessage())->end();
        $error = new Error();
        $error->setCode(400);
        $error->setMsg($msg);
        return $error;
    }

    /**
     * handleOldPasswordIncorrectException
     *
     * @param OldPasswordIncorrectException $exception  the Exception
     * @param array                         $msg        message array
     * @param string                        $identifier the identifier for the logfile
     *
     * @return Error
     */
    protected function handleOldPasswordIncorrectException(
        OldPasswordIncorrectException $exception,
        array $msg,
        $identifier
    ) {

        $this->status = 420;
        $log = new \rpt_rpt(
            \rpt_level::E_CRITICAL,
            $identifier
        );
        $log->add($exception->getMessage())->end();
        $error = new Error();
        $error->setCode(420);
        $error->setMsg($msg);

        return $error;
    }

    /**
     * handleException
     *
     * @param \Exception $exception  the Exception
     * @param array      $msg        message array
     * @param string     $identifier the identifier for the logfile
     *
     * @return Error
     */
    protected function handleException(
        \Exception $exception,
        array $msg,
        $identifier
    ) {
        $this->status = 500;
        $log = new \rpt_rpt(
            \rpt_level::E_CRITICAL,
            $identifier
        );
        $log->add($exception->getMessage())->end();
        $envConf = $this->readConfigList();
        $from = $envConf['email_from'];
        if ($this->isEnvAllowedToSendMail()) {
            $mail =
                new \eml_mail(
                    new \eml_address($from),
                    new \eml_address($envConf['exception_to']),
                    $envConf['exception_subject'],
                    \utl_obs::var_export($exception)
                );
            $mail->send();
        }
        $error = new Error();
        $error->setCode(500);
        $error->setMsg($msg);
        return $error;
    }

    /**
     * readConfigList
     *
     *
     * @return array
     */
    protected function readConfigList()
    {
        if (PHP_SAPI == 'cli') {
            $envConfig =
                file_get_contents('conf/.unittest.env.conf.json');
        } else {
            $envConfig =
                file_get_contents('conf/.env.conf.json');
        }
        $envConf = json_decode($envConfig, true);
        return $envConf;
    }

    /**
     * validateXpath
     *
     * @param string $xpath the xpath
     *
     * @return void
     * @throws ValidationException
     */
    protected function validateXpath($xpath)
    {
        $doc = new \DOMDocument();
        $path = new \DOMXPath($doc);
        $query = @$path->query($xpath);
        if (!$query) {
            throw new ValidationException('not a valid xpath');
        }
    }

    protected function isEnvAllowedToSendMail() {
        $envConf = $this->readConfigList();
        $env = $envConf['environment'];
        if ($env =='live' || $env=='test') {
            return true;
        }
        return false;
    }
}
