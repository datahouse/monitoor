<?php

namespace Datahouse\MON;

ini_set('xdebug.var_display_max_depth', 6);
ini_set('xdebug.max_nesting_level', 10000);
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

require_once(dirname(__FILE__) . '/vendor/autoload.php');

use Datahouse\MON\Types\Gen\Error;
use \Dice\Dice as Dice;
use \Dice\Rule as DiceRule;
use \Datahouse\Framework\Router as Router;
use \Datahouse\MON\Router\Rule as Rule;
use Datahouse\MON\Exception\BadRequestException;
use Datahouse\MON\Exception\MethodNotAllowedException;

\rpt_rpt::set_report_file('./log/mon.log');
\rpt_rpt::enable_indent();

try {
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        header("HTTP/1.1 200 Ok");
        header('Content-Type: text/plain; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        return;
    }

    $dice = new Dice();

    $rule = new DiceRule;
    $rule->shared = true;
    $rule->constructParams = [true, 512];
    $dice->addRule('Datahouse\\Libraries\\JSON\\Converter\\Config', $rule);

    // Load the database configuration from the project-wide json.
    $db_config_str =
        file_get_contents(dirname(__FILE__) . '/conf/.db.conf.json');
    if ($db_config_str === false) {
        throw new \Exception(
            "Cannot read database configuration file: .db.conf.json"
        );
    }

    $conv = $dice->create('Datahouse\\Libraries\\JSON\\Converter');
    $db_config = $conv->decode($db_config_str);
    assert($db_config['type'] == "postgres");

    $dsn_parts = [];
    if (array_key_exists("host", $db_config)) {
        $dsn_parts[] = "host=" . $db_config['host'];
    }
    if (array_key_exists("port", $db_config)) {
        $dsn_parts[] = "port=" . $db_config['port'];
    }
    if (array_key_exists("database", $db_config)) {
        $dsn_parts[] = "dbname=" . $db_config['database'];
    }
    $dsn = "pgsql:" . implode(";", $dsn_parts);

    // Switch PDO's error mode to give us proper exceptions.
    $db_options = [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION];

    $rule = new DiceRule;
    $rule->shared = true;
    $rule->constructParams = [$dsn, $db_config['username'], $db_config['password'], $db_options];
    $dice->addRule('PDO', $rule);

    try {
        // Test-run the database connection, so as to emit a somewhat
        // helpfull error message. Otherwise, we only get a misleading Dice
        // stack trace, complaining that it cannot instantiate a certain
        // Model.
        $dice->create('PDO');
    } catch (\Exception $e) {
        $log = new \rpt_rpt(
            \rpt_level::E_CRITICAL,
            'api.php'
        );
        $log->add("Failed to connect to the database: " .
                  $e->getMessage())->end();
        // FIXME: This certainly isn't the best way to display an error
        // message, especially not for an API.
        echo "Configuration error, please check the log file.\n";
        exit;
    }

    $req = explode('/', $_REQUEST['request']);

    $rule = new DiceRule;
    $rule->constructParams[] = $req;
    $dice->addRule('Datahouse\\MON\\Request', $rule);

    $router = new Router\Router;
    $router->addRule(new Rule($dice));

    foreach ($router->getRoute($req) as $route) {
        $route->getController()->checkRequestMethod();
        $route->getController()->control();
        if (!ob_start("ob_gzhandler")) {
            ob_start();
        }
        echo $route->getView()->getOutput();
        ob_end_flush();
        break;
    }

} catch (MethodNotAllowedException $me) {
        $log = new \rpt_rpt(
            \rpt_level::E_CRITICAL,
            'api.php'
        );
        $log->add($me->getMessage())->end();
        $error = new Error();
        $error->setCode(405);
        $error->setMsg(array('Method not allowed'));
        header("HTTP/1.1 " . 405 . " " . 'Method not allowed');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($error);
} catch (BadRequestException $e) {
    $log = new \rpt_rpt(
        \rpt_level::E_CRITICAL,
        'api.php'
    );
    $log->add($e->getMessage())->end();
    $error = new Error();
    $error->setCode(400);
    $error->setMsg(array('Bad Request'));
    header("HTTP/1.1 " . 400 . " " . 'Bad request');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($error);
} catch (\Exception $e) {
    $log = new \rpt_rpt(
        \rpt_level::E_CRITICAL,
        'api.php'
    );
    $log->add($e->getMessage())->end();
    $envConfig =
        file_get_contents(dirname(__FILE__) . '/conf/.env.conf.json');
    $envConf = json_decode($envConfig, true);
    if ($envConf['environment'] == 'live') {
        $mail =
            new \eml_mail(
                new \eml_address($envConf['email_from']),
                new \eml_address($envConf['exception_to']),
                $envConf['exception_subject'],
                \utl_obs::var_export($e)
            );
        $mail->send();
    }
    $error = new Error();
    $error->setCode(500);
    $error->setMsg(array('Unexpected Server Error'));
    header("HTTP/1.1 " . 500 . " " . 'Unexpected Server Error');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($error);
}
