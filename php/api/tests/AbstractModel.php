<?php

namespace Datahouse\MON\Tests;

/**
 * Class AbstractModel
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
abstract class AbstractModel extends \PHPUnit_Framework_TestCase
{

    /**
     * getPDO
     *
     *
     * @return \PDO
     */
    protected function getPDO()
    {
        $dbConfigCtr = file_get_contents(dirname(__FILE__) . '/conf/db.conf.json');
        $dbConfig = json_decode($dbConfigCtr);
        $dsnParts = [];
        if (array_key_exists("host", $dbConfig)) {
            $dsnParts[] = "host=" . $dbConfig->host;
        }
        if (array_key_exists("port", $dbConfig)) {
            $dsnParts[] = "port=" . $dbConfig->port;
        }
        if (array_key_exists("database", $dbConfig)) {
            $dsnParts[] = "dbname=" . $dbConfig->database;
        }
        if (array_key_exists("username", $dbConfig)) {
            $dsnParts[] = "user=" . $dbConfig->username;
        }
        if (array_key_exists("password", $dbConfig)) {
            $dsnParts[] = "password=" . $dbConfig->password;
        }
        $dsn = "pgsql:" . implode(";", $dsnParts);
        $dbOptions = [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION];
        //$dbOptions = [];
        $pdo = new \PDO(
            $dsn,
            $dbConfig->username,
            $dbConfig->password,
            $dbOptions
        );
        return $pdo;
    }
}
