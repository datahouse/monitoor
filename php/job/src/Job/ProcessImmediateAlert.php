<?php

namespace Datahouse\MON\Job;

/**
 * Class ProcessImmediateAlert
 *
 * @package Job
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ProcessImmediateAlert extends ProcessAlert
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
        $notifications = $this->getAlertsToProcess(
            array(self::IMMEDIATE_EMAIL, self::IMMEDIATE_SMS, self::IMMEDIATE_EMAIL_PUSH)
        );
        $count = count($notifications);
        if ($count > 0) {
            $log = new \rpt_rpt(
                \rpt_level::L_INFO,
                count($notifications) . ' immediate alerts to process'
            );
            $log->end();
        }
        foreach ($notifications as $notification) {
            try {
                if ($notification->getTypeCycleId() == self::IMMEDIATE_EMAIL ||
                    $notification->getTypeCycleId() ==
                    self::IMMEDIATE_EMAIL_PUSH
                ) {
                    $this->sendEmailAlert(
                        array(
                            $notification->getUserEmail(
                            ) => array($notification)
                        )
                    );
                } elseif ($notification->getTypeCycleId() ==
                    self::IMMEDIATE_SMS
                ) {
                    $this->sendSMSAlert();
                } else {
                    throw new \Exception(
                        'invalid alert type ' . $notification->getTypeCycleId()
                    );
                }
                $this->setAlertSent($notification);
            } catch (\Exception $e) {
                $log = new \rpt_rpt(
                    \rpt_level::E_FATAL,
                    $e . ' while processing alert ' .
                    $notification->getAlertId()
                );
                $log->end();
            }
        }
    }
}
