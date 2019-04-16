<?php


namespace Datahouse\MON\Model;

use Datahouse\Framework\Model;

/**
 * Class PasswordModel
 *
 * @package Model
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
abstract class PasswordModel extends Model
{

    /**
     * checkPwdMatch
     *
     * @param string $pwd     the pwd
     * @param string $pwdHash the hash
     * @param string $pwdSalt the salt
     *
     * @return bool
     */
    protected function checkPwdMatch($pwd, $pwdHash, $pwdSalt)
    {
        if (isset($pwdSalt)) {
            $pwd .= $pwdSalt;
        }
        return (hash('sha256', $pwd) === $pwdHash);
    }

    /**
     * encrypt
     *
     * @param string $pwdClear the pwd
     * @param string $pwdSalt  the pwd salt
     *
     * @return string
     */
    protected function encrypt($pwdClear, $pwdSalt)
    {
        return hash('sha256', $pwdClear . $pwdSalt);
    }

    /**
     * createPwdSalt
     *
     *
     * @return string
     */
    protected function createPwdSalt()
    {
        $salt = '';
        for ($i = 0; $i < 16; $i++) {
            $salt .= chr(rand(33, 126)); // random, printable ascii char
        }
        return $salt;
    }
}
