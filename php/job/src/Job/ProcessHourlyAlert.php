<?php

namespace Datahouse\MON\Job;

/**
 * Class ProcessHourlyAlert
 *
 * @package Job
 * @author  Markus Wanner (mwa) <markus.wanner@datahouse.ch>
 * @license (c) 2014 - 2018 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ProcessHourlyAlert extends ProcessAlert
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
        $notifications = $this->getAlertsToProcess(array(self::HOURLY_EMAIL));
        $count = count($notifications);
        if ($count > 0) {
            $log = new \rpt_rpt(
                \rpt_level::L_INFO,
                count($notifications) . ' hourly alerts to process'
            );
            $log->end();
        }
        $this->process($notifications);
    }
}
