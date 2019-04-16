<?php

namespace Datahouse\MON\Job;

/**
 * Class ProcessDailyAlert
 *
 * @package Job
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ProcessDailyAlert extends ProcessAlert
{

    /**
     * @param \PDO  $pdo  the pdo
     * @param array $argv the args
     */
    public function __construct(\PDO $pdo, $argv)
    {
        $this->pdo = $pdo;
        $this->argv = $argv;
    }

    /**
     * run
     *
     * @return void
     * @throws \Exception
     */
    public function run()
    {
        $notifications = $this->getAlertsToProcess(array(self::DAILY_EMAIL));
        $count = count($notifications);
        if ($count > 0) {
            $log = new \rpt_rpt(
                \rpt_level::L_INFO,
                count($notifications) . ' daily alerts to process'
            );
            $log->end();
        }
        $this->process($notifications);
    }
}
