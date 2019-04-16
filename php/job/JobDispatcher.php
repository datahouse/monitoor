<?php

namespace Datahouse\MON;

/**
 *
 * @package Job
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */



error_reporting(E_ALL | E_STRICT);

ini_set('xdebug.var_display_max_depth', 6);
ini_set('xdebug.max_nesting_level', 10000);
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

require_once(dirname(__FILE__) . '/vendor/autoload.php');

\rpt_rpt::set_report_file('./log/mon_job.log');
\rpt_rpt::enable_indent();

$db_config_str = file_get_contents(dirname(__FILE__) . '/conf/.db.conf.json');
$db_config = json_decode($db_config_str);

$dsn_parts = [];
if (array_key_exists("host", $db_config)) {
    $dsn_parts[] = "host=" . $db_config->host;
}
if (array_key_exists("port", $db_config)) {
    $dsn_parts[] = "port=" . $db_config->port;
}
if (array_key_exists("database", $db_config)) {
    $dsn_parts[] = "dbname=" . $db_config->database;
}
$dsn = "pgsql:" . implode(";", $dsn_parts);

// Switch PDO's error mode to give us proper exceptions.
$db_options = [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION];

$pdo = new \PDO($dsn, $db_config->username, $db_config->password, $db_options);

$argv = $_SERVER['argv'];

$commands = array(
    'ProcessDailyAlert',
    'ProcessImmediateAlert',
    'ProcessPushAlert',
    'AWPReport',
    'ProcessBusinessHoursAlert',
    'ProcessHourlyAlert'
);

$ok = false;

$return_code = 0;
try {
    foreach ($commands as $c) {
        if (in_array($c, $argv)) {
            $ok = true;
            $c_n = 'Datahouse\\MON\\Job\\' . str_replace('-', '_', $c);
            $inst = new $c_n($pdo, $argv); // $argv as argument if the are needed for the job
            $mutex = new \utl_pid_mutex('./pid_file_' . $c . '.txt');
            if (!$mutex->acquire()) {
                $r = new \rpt_rpt(
                    \rpt_level::L_INFO,
                    'main mutex acquiring failed'
                );
                $r->end();
                exit(2);
            }
            $r = new \rpt_rpt(\rpt_level::L_HIT, 'call ' . $c_n . '->run()');
            $r->end();
            $inst->run();
            $r = new \rpt_rpt(\rpt_level::L_HIT, 'end ' . $c_n);
            $r->end();
        }
    }
} catch (\Exception $e) {
    $r = new \rpt_rpt(\rpt_level::E_FATAL, 'exception');
    $r->add(var_export($e, true))->end();
    $return_code = 3;
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
}

if (isset($mutex)) {
    $mutex->release();
}

if ($ok) {
    exit($return_code);
}

echo "Usage: {$argv[0]} (<command>)+ (<args>)*
  ProcessDailyAlert:
  ProcessBusinessHoursAlert:
  ProcessImmediateAlert:
  ProcessPushAlert:
  AWPReport:
  ProcessHourlyAlert:
";

exit(1);
