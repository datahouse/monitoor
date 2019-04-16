<?php

namespace Datahouse\MON\Job;

use Datahouse\MON\Types\Notification;
use Sly\NotificationPusher\Adapter\Apns;
use Sly\NotificationPusher\Adapter\Gcm;
use Sly\NotificationPusher\Collection\DeviceCollection;
use Sly\NotificationPusher\Model\Device;
use Sly\NotificationPusher\Model\Message;
use Sly\NotificationPusher\Model\Push;
use Sly\NotificationPusher\PushManager;

/**
 * Class ProcessPushAlert
 *
 * @package     Job
 * @author      Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ProcessPushAlert extends ProcessAlert
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
        $notifications = $this->getAlertsToProcess(array(self::PUSH, self::IMMEDIATE_EMAIL_PUSH), true);
        $count = count($notifications);
        if ($count > 0) {
            $log = new \rpt_rpt(
                \rpt_level::L_INFO,
                $count . ' push alerts to process'
            );
            $log->end();
        } else {
            return;
        }
        $this->sendPushNotification($notifications);
    }

    /**
     * sendPushNotification
     *
     * @param array $notifications the notifications
     *
     * @return void
     */
    private function sendPushNotification(array $notifications)
    {
        $envConfig = file_get_contents('conf/.env.conf.json');
        $envConf = json_decode($envConfig, true);
        $appleCertFile = $envConf['ios_cert'];
        $androidApiKey = $envConf['android_key'];
        $appUrl = $envConf['app_url'];
        $env = $envConf['environment'];
        $passPhrase = $envConf['passphrase'];

        //https://github.com/Ph3nol/NotificationPusher/blob/master/doc/getting-started.md
        // First, instantiate the manager and declare an adapter.
        $pushManager = null;
        if ($env == 'live') {
            $pushManager = new PushManager(PushManager::ENVIRONMENT_PROD);
        } else {
            $pushManager = new PushManager(PushManager::ENVIRONMENT_DEV);
        }
         $IOSAdapter = new Apns(
            array(
                'certificate' => 'conf/' . $appleCertFile,
                'passPhrase' => $passPhrase
            )
        );
        $androidAdapter = new Gcm(array('apiKey' => $androidApiKey));

        foreach ($notifications as $notification) {
            // Then, create the push .
            $message = $this->createMessage($notification, $appUrl);
            if ($notification->getPlatform() == 0) {
                // Set the device(s) to push the notification to.
                $iOSDevices = new DeviceCollection();
                $iOSDevices->add(new Device($notification->getToken()));
                // create and add the push to the manager, and push it!
                $iOSpush = new Push($IOSAdapter, $iOSDevices, $message);
                $pushManager->add($iOSpush);
            } else {
                if ($notification->getPlatform() == 1) {
                    // Set the device(s) to push the notification to.
                    $androidDevices = new DeviceCollection();
                    $androidDevices->add(new Device($notification->getToken()));
                    // create and add the push to the manager, and push it!
                    $androidPush =
                        new Push($androidAdapter, $androidDevices, $message);
                    $pushManager->add($androidPush);
                }
            }
        }
        $log = new \rpt_rpt(
            \rpt_level::L_INFO,
            'try to send ' . count($notifications) . ' push notifications'
        );
        $log->end();
        $deviceCount = $pushManager->push();
        foreach ($notifications as $notification) {
            $this->setAlertSent($notification, true);
        }
        $log = new \rpt_rpt(
            \rpt_level::L_INFO,
            $deviceCount->count() . ' notifications sent '
        );
        $log->end();
        $feedback = $pushManager->getFeedback($IOSAdapter);
        $log = new \rpt_rpt(
            \rpt_level::L_INFO,
            'feedback: ' . serialize($feedback)
        );
        $log->end();
    }

    /**
     * createMessage
     *
     * @param Notification $notification the notification
     * @param string       $appUrl       the app url
     *
     * @return Message
     */
    private function createMessage(Notification $notification, $appUrl)
    {
        $msg = new Message(
            $notification->getUrlGroupTitle() . ' | ' . $notification->getUrlTitle() . PHP_EOL
            . $notification->getDiffPreview() . PHP_EOL . PHP_EOL,
            array(
                'url' => $appUrl . 'urlGroup/' . $notification->getUrlGroupId(),
                'custom' => array(
                    'url' => $appUrl . 'urlGroup/' .
                        $notification->getUrlGroupId()
                )
            )
        );
        return $msg;
    }
}
