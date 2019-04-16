<?php

namespace Datahouse\MON\Job;

use Datahouse\MON\Common\KeywordHighlighter;
use Datahouse\MON\Types\Notification;

/**
 * Class ProcessAlert
 *
 * @package Job
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
abstract class ProcessAlert
{
    const IMMEDIATE_SMS = 1;
    const IMMEDIATE_EMAIL = 3;
    const DAILY_EMAIL = 2;
    const PUSH = 5;
    const BUSINESS_HOURS_EMAIL = 6;
    const IMMEDIATE_EMAIL_PUSH = 7;
    const HOURLY_EMAIL = 8;

    /**
     * @var \PDO
     */
    protected $pdo;
    protected $argv;

    /**
     * run
     *
     *
     * @return mixed
     */
    abstract protected function run();

    /**
     * getAlertsToProcess
     *
     * @param array $cycleTypes the types
     * @param bool  $isPush     app flag
     *
     * @return array
     * @throws \Exception
     */
    protected function getAlertsToProcess($cycleTypes, $isPush = false)
    {
        $query = '';
        try {
            $query .= 'SELECT distinct a.alert_id, c.change_id, c.delta, u.url_id, u.url, u.url_title, new_doc_id,';
            $query .= ' type_x_cycle_id, c.ts as creation_ts, m.user_email, m.user_id, ';
            $query .= ' acc.account_name_first, acc.account_name_last, acc.account_mobile, ';
            $query .= ' g.url_group_id, g.url_group_title ';
            if ($isPush) {
                $query .= ', pt.platform, pt.token ';
            }
            $query .= ' FROM notification n JOIN alert a ON (a.alert_id = n.alert_id)';
            $query .= ' JOIN change c ON n.change_id = c.change_id ';
            $query .= ' JOIN change_x_url cu ON c.change_id = cu.change_id ';
            $query .= ' JOIN url u ON (u.url_id = cu.url_id) ';
            $query .= ' JOIN url_group g ON (u.url_group_id = g.url_group_id) ';
            $query .= ' JOIN mon_user m ON (a.user_id=m.user_id) ';
            if ($isPush) {
                $query .= ' JOIN push_token pt ON (m.user_id= pt.user_id) ';
            }
            $query .= ' JOIN account acc ON (acc.user_id=m.user_id) ';
            $query .= ' WHERE m.user_activated AND (m.user_valid_till is null OR m.user_valid_till > NOW()) ';
            $query .= ' AND NOT is_retained ';
            $query .= ' AND type_x_cycle_id IN (' .
                implode(', ', $cycleTypes) . ')';
            if (!$isPush) {
                $query .= ' AND delivery_ts IS NULL ';
            } else {
                $query .= ' AND push_ts IS NULL AND c.ts > pt.ts AND denied=false ';
            }
            $query .= 'AND EXISTS (SELECT user_id FROM access_control ac WHERE ';
            $query .= ' ((m.user_id=ac.user_id AND ac.user_id IS NOT NULL) OR ';
            $query .= ' (m.user_group_id = ac.user_group_id AND ac.user_group_id IS NOT NULL)) ';
            $query .= ' AND u.url_id = ac.url_id) ';
            $query .= ' ORDER BY u.url_id asc, c.ts desc ';
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $notifications = array();
            foreach ($stmt->fetchAll() as $res) {
                $notification = new Notification();
                $notification->setAlertId($res['alert_id']);
                $notification->setNewDocId($res['new_doc_id']);
                $notification->setTypeCycleId($res['type_x_cycle_id']);
                $notification->setUrl($res['url']);
                $notification->setUrlId($res['url_id']);
                $notification->setUrlTitle($res['url_title']);
                $notification->setUrlGroupId($res['url_group_id']);
                $notification->setUrlGroupTitle($res['url_group_title']);
                $notification->setChangeId($res['change_id']);
                $notification->setLastUrlChange(
                    date_format(new \DateTime($res['creation_ts']), 'd.m.Y H:i:s')
                );
                $notification->setUserId($res['user_id']);
                $notification->setUserEmail($res['user_email']);
                $notification->setUserFirstName($res['account_name_first']);
                $notification->setUserLastName($res['account_name_last']);
                $notification->setUserMobile($res['account_mobile']);
                if ($isPush) {
                    $notification->setToken($res['token']);
                    $notification->setPlatform($res['platform']);
                }

                if (!is_null($res['delta'])) {
                    $alertId = $res['alert_id'];
                    $delta_data = json_decode($res['delta'], true);
                    // sections are only in delta v2 >= MON 1.5
                    if (isset($delta_data['sections'])) {
                        $sections = $delta_data['sections'];
                        assert(array_key_exists('match_positions', $delta_data));
                        $mpos = $delta_data['match_positions'];
                        $alert_mpos = isset($mpos[$alertId]) ? $mpos[$alertId] : [];
                        list($diffPreview, $alternativeUrl) = $this->renderDeltaVersion2($sections, $alert_mpos);

                        $notification->setDiffPreview($this->transformDiffPreview($diffPreview, false));
                        $notification->setDiffPreviewHtml($this->transformDiffPreview($diffPreview));
                        $notification->setAlternativeUrl($alternativeUrl);
                    } else {
                        $notification->setDiffPreview('-' . PHP_EOL);
                        $notification->setDiffPreviewHtml('<div>-</div>');
                    }
                } else {
                    $notification->setDiffPreview('-' . PHP_EOL);
                    $notification->setDiffPreviewHtml('<div>-</div>');
                }

                $notifications[] = $notification;
            }
            return $notifications;
        } catch (\Exception $e) {
            throw new \Exception($e . ': executing query ' . $query);
        }
    }

    /**
     * sendEmailAlert
     *
     * @param array $notifications the notification
     *
     * @return void
     */
    protected function sendEmailAlert($notifications)
    {
        $envConf = $this->readConfigValues();
        $emailFrom = $envConf['email_from'];
        $rootUrl = $envConf['root_url'];
        $alertUrl = $rootUrl . $envConf['alert_url'];
        $groupsUrl = $rootUrl . $envConf['url_groups'];
        $alertSettingsUrl = $rootUrl . $envConf['alert_setting_url'];

        foreach ($notifications as $emailTo => $emailNotifications) {
            $mailBodyAlt = $this->getEmailText($emailNotifications, $alertUrl, $groupsUrl, $alertSettingsUrl);
            $mailBodyHtml = $this->getEmailTextHtml($emailNotifications, $alertUrl, $groupsUrl, $alertSettingsUrl);

            $mail = new \PHPMailer();
            $mail->From = $emailFrom;
            $mail->FromName = "Monitoor";
            $mail->addAddress($emailTo, 'Alert');
            $mail->isHTML(true);
            $mail->CharSet = "utf-8";
            $mail->Subject = 'MONITOOR-Alert';
            $mail->Body = $mailBodyHtml;
            $mail->AltBody = $mailBodyAlt;
            if ($envConf['environment'] == 'develop') {
                $log = new \rpt_rpt(
                    \rpt_level::E_NOTICE,
                    $mailBodyAlt
                );
                $log->end();
            }
            if (!$mail->send()) {
                throw new \Exception(
                    'error while sending alert'
                );
            }
        }
    }

    /**
     * getEmailText
     *
     * @param array  $notifications the notification
     * @param string $alertUrl      the alert url
     * @param string $groupsUrl     the alert groups url
     * @param string $settingUrl    the setting url
     *
     * @return string
     */
    private function getEmailText($notifications, $alertUrl, $groupsUrl, $alertSettingsUrl)
    {
        $emailText = 'Guten Tag' . PHP_EOL . PHP_EOL;
        $emailText .= 'Folgende  Webseite(n) wurde(n) geändert:' . PHP_EOL;

        $urlId = 0;
        foreach ($notifications as $notification) {
            if ($urlId != $notification->getUrlId()) {
                $urlId = $notification->getUrlId();
                $emailText .= PHP_EOL . $notification->getUrlTitle() . ': ' . $notification->getUrl() . PHP_EOL;
                $emailText .=
                    'Details dieser Benachrichtigung: ' .
                    $alertUrl . $notification->getUrlId() . PHP_EOL . PHP_EOL;
            }

            $emailText .= $notification->getLastUrlChange() . ': ' . PHP_EOL;
            $emailText .= $notification->getDiffPreview() . PHP_EOL;

        }
        $emailText .= PHP_EOL . 'Freundliche Grüsse, Datahouse' . PHP_EOL;
        $emailText .= $this->getFooterText($groupsUrl, $alertSettingsUrl);

        return $emailText;
    }

    private function getEmailTextHtml($notifications, $alertUrl, $groupUrl, $alertSettingsUrl)
    {
        $html = '';
        $template = file_get_contents('template/mail_template.html');
        $logo = base64_encode(file_get_contents('template/mail_logo.png'));

        if (isset($notifications[0])) {
            $alertSettingsUrl .= $notifications[0]->getAlertId();
        }

        $urlId = 0;
        foreach ($notifications as $notification) {
            if ($urlId != $notification->getUrlId()) {
                if ($urlId != 0) {
                    $html .= $this->getHtmlUrlDivider();
                }

                $urlId = $notification->getUrlId();

                $urlExtern = $notification->getUrl();
                if (!is_null($notification->getAlternativeUrl())) {
                    $urlExtern = $notification->getAlternativeUrl();
                }

                $html .= $this->getHtmlUrlHeader(
                    $notification->getUrlTitle(),
                    $urlExtern, $alertUrl .
                    $notification->getUrlId()
                );
            }

            $html .= $this->getHtmlUrlContent(
                $notification->getLastUrlChange(),
                $notification->getDiffPreviewHtml()
            );
        }

        $replacement = array(
            '{{base64_logo}}' => $logo,
            '{{content}}' => $html,
            '{{footer}}' => $this->getHtmlUrlFooter($groupUrl, $alertSettingsUrl)
        );

        return strtr($template, $replacement);
    }

    private function transformDiffPreview($preview, $html = true)
    {
        $preview = str_replace(PHP_EOL, '', $preview);
        $transforms = array(
            '{{mark}}' => '',
            '{{/mark}}' => '',
            '{{ins}}' => '',
            '{{/ins}}' => PHP_EOL,
            '{{del}}' => '',
            '{{/del}}' => PHP_EOL);

        $transformsHtml = array(
            '{{mark}}' => '<mark>',
            '{{/mark}}' => '</mark>',
            '{{ins}}' => '<span class="ins">',
            '{{/ins}}' => '</span><br/>',
            '{{del}}' => '<span class="del">',
            '{{/del}}' => '</span><br/>');

        if ($html) {
            return strtr($preview, $transformsHtml);
        }

        return strtr($preview, $transforms);
    }

    /**
     * getFooterText
     *
     * @param string $groupsUrl the groups url
     *
     * @return string
     */
    private function getFooterText($groupsUrl, $alertSettingsUrl)
    {
        $footerText = PHP_EOL . '----------' . PHP_EOL;
        $footerText .= 'Ändern der Einstellungen: ' . $alertSettingsUrl . PHP_EOL;
        $footerText .=
            'Verwalten aller Benachrichtigungen: ' . $groupsUrl . PHP_EOL;
        return $footerText;
    }

    /**
     * sendSMSAlert
     *
     *
     * @return void
     * @throws \Exception
     */
    protected function sendSMSAlert()
    {
        throw new \Exception('sms alert not yet implemented');
    }

    /**
     * setAlertSent
     *
     * @param Notification $notification the notification
     * @param bool         $isPush       app flag
     *
     * @return void
     * @throws \Exception
     */
    protected function setAlertSent(Notification $notification, $isPush = false)
    {
        $query = '';
        $timestamp = 'delivery_ts';
        if ($isPush) {
            $timestamp = 'push_ts';
        }
        try {
            $this->pdo->beginTransaction();
            $query = 'UPDATE notification SET ' . $timestamp . ' = NOW() ';
            $query .= ' WHERE alert_id = :alertId ';
            $query .= ' AND change_id = :changeId ';
            if (!$isPush) {
                $query .= ' AND type_x_cycle_id = :typeCycleId ';
            }
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(
                ':alertId',
                $notification->getAlertId(),
                \PDO::PARAM_INT
            );
            if (!$isPush) {
                $stmt->bindValue(
                    ':typeCycleId',
                    $notification->getTypeCycleId(),
                    \PDO::PARAM_INT
                );
            }
            $stmt->bindValue(
                ':changeId',
                $notification->getChangeId(),
                \PDO::PARAM_INT
            );
            $stmt->execute();
            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ': executing query ' . $query);
        }
    }

    /**
     * readConfigValues
     *
     *
     * @return array
     * @throws \Exception
     */
    public function readConfigValues()
    {
        $envConfig =
            file_get_contents('conf/.env.conf.json');
        $envConf = json_decode($envConfig, true);
        return $envConf;
    }

    protected function process($notifications)
    {
        $emailNotifications = array();
        foreach ($notifications as $notification) {
            /* @var Notification $notification */
            if (array_key_exists(
                $notification->getUserEmail(),
                $emailNotifications
            )) {
                $tempArry = array_merge(
                    $emailNotifications[$notification->getUserEmail()],
                    array($notification)
                );
            } else {
                $tempArry = array($notification);
            }
            $emailNotifications[$notification->getUserEmail()] = $tempArry;
        }
        try {
            $this->sendEmailAlert($emailNotifications);
            foreach ($notifications as $notification) {
                $this->setAlertSent($notification);
            }
        } catch (\Exception $e) {
            $log = new \rpt_rpt(
                \rpt_level::E_FATAL,
                $e . ' while processing alert ' . $notification->getAlertId()
            );
            $log->end();
        }
    }

    private function renderDeltaVersion2($sections, $alert_mpos)
    {
        $diffPreview = '';
        $changedLineInfo = [];
        $alternativeUrl = null;
        foreach ($sections as $section_idx => $section) {
            assert(is_array($section));
            if (array_key_exists('add', $section)) {
                // We cannot use splitByNewline here, as that would break
                // keyword highlighting. Awwk!
                $lines = $section['add'];

                // External changes may contain newlines.
                $lines = is_array($lines) ? $lines : [$lines];

                $section_mpos = isset($alert_mpos[$section_idx])
                    ? $alert_mpos[$section_idx] : [];

                foreach ($lines as $idx => $line) {
                    // mark keywords within the line
                    list ($matching_keywords, $line)
                        = KeywordHighlighter::mark($section_mpos, $idx, $line);

                    $altUrl =
                        $this->splitLines($line, $changedLineInfo, $section_idx, $idx, $matching_keywords, 'ins');
                    if (!isset($alternativeUrl)) {
                        $alternativeUrl = $altUrl;
                    }
                }
            }
            if (array_key_exists('del', $section)) {
                $hasChangedLineInfo = count($changedLineInfo) > 0;
                $lines = $this->splitByNewline($section['del']);
                foreach ($lines as $idx => $line) {
                    if (!$hasChangedLineInfo) {
                        $this->splitLines($line, $changedLineInfo, $section_idx, $idx, [], 'del');
                    }
                }
            }
        }

        if (count($changedLineInfo) > 5) {
            // For the preview, limit to the five *best* lines (by number of
            // keyword matches and index).
            $matchCmpFunc = function ($a, $b) {
                if ($a[2] == $b[2]) {
                    if ($a[0] == $b[0]) {
                        if ($a[1] == $b[1]) {
                            return 0;
                        } else {
                            return $a[1] < $b[1] ? -1 : 1;
                        }
                    } else {
                        return $a[0] < $b[0] ? -1 : 1;
                    }
                } else {
                    return $a[2] < $b[2] ? -1 : 1;
                }
            };
            usort($changedLineInfo, $matchCmpFunc);
            $changedLineInfo = array_slice($changedLineInfo, 0, 5);

            // Then reorder the lines according to their index, so they appear
            // in the correct order.
            sort($changedLineInfo);
        }

        foreach ($changedLineInfo as $entry) {
            $diffPreview .= $entry[3];
        }

        return [$diffPreview, $alternativeUrl];
    }

    private function splitByNewline($input)
    {
        $input = is_array($input) ? $input : [$input];
        $result = [];
        foreach ($input as $entry) {
            foreach (explode("\n", $entry) as $line) {
                $result[] = $line;
            }
        }
        return $result;
    }

    /**
     * @param $change
     * @param $changedLineInfo (Reference)
     * @param $section_idx
     * @param $idx
     * @param $matching_keywords
     */
    private function splitLines(
        $change, &$changedLineInfo, $section_idx, $idx, $matching_keywords, $diffType)
    {
        $wrapperPre = '{{' . $diffType . '}}'; $wrapperSuf = '{{/' . $diffType . '}}';
        $alternativeUrl = null;

        // Do newline splitting here. This way, line and change
        // both really are misnomers. Sorry.
        foreach (explode("\n", $change) as $real_line) {
            $altUrl = $this->getAlternativeUrl($real_line);
            if ($altUrl) {
                if (!isset($alternativeUrl)) {
                    $alternativeUrl = $altUrl;
                }
                continue;
            }
            if (strlen($real_line) == 0) {
                continue;
            }
            $changedLineInfo[] = [
                $section_idx,
                $idx,
                count($matching_keywords),
                $wrapperPre . $this->substituteMarkdown($real_line, false) . $wrapperSuf
            ];
        }

        return $alternativeUrl;
    }

    private function getAlternativeUrl($line)
    {
        if (preg_match('/\[.*\]\((?<url>.+)\)/',$line, $matches)) {
            return $matches['url'];
        } else {
            return null;
        }
    }

    private function substituteMarkdown($line, $html = true)
    {
        $sz = strlen($line);
        if ($sz > 1 && $line[0] == '#') {
            if ($sz > 2 && $line[1] == '#') {
                if ($sz > 3 && $line[2] == '#') {
                    if ($sz > 4 && $line[3] == '#') {
                        if ($sz > 5 && $line[4] == '4' && $line[5] == ' ') {
                            $line = $this->substitute($line, 5, $html);
                        } elseif ($line[4] == ' ') {
                            $line = $this->substitute($line, 4, $html);
                        }
                    } elseif ($line[3] == ' ') {
                        $line = $this->substitute($line, 3, $html);
                    }
                } elseif ($line[2] == ' ') {
                    $line = $this->substitute($line, 2, $html);
                }
            } elseif ($line[1] == ' ') {
                $line = $this->substitute($line, 1, $html);
            }
        }
        return $line;
    }

    private function substitute($line, $level, $html)
    {
        $wrapperPre = ''; $wrapperSuf = '';
        if ($html) {
            $wrapperPre = '<div class="header' . $level . '">';
            $wrapperSuf = '</div>';
        }

        return $wrapperPre . substr($line, $level + 1) . $wrapperSuf;
    }

    private function getHtmlUrlDivider()
    {
        $divider = <<<DIVIDER
<table class="mcnDividerBlock" style="min-width:100%;" cellspacing="0" cellpadding="0" border="0" width="100%">
    <tbody class="mcnDividerBlockOuter">
        <tr>
            <td class="mcnDividerBlockInner" style="min-width:100%; padding:18px;">
                <table class="mcnDividerContent" style="min-width: 100%;border-top: 1px solid #EAEAEA;" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tbody><tr>
                        <td>
                            <span></span>
                        </td>
                    </tr>
                </tbody></table>
            </td>
        </tr>
    </tbody>
</table>
DIVIDER;
        return $divider;
    }

    private function getHtmlUrlHeader($name, $urlExtern, $urlIntern)
    {
        $header = <<<HEADER
<table class="mcnTextBlock" style="min-width:100%;" cellspacing="0" cellpadding="0" border="0" width="100%">
    <tbody class="mcnTextBlockOuter">
        <tr>
            <td class="mcnTextBlockInner" style="padding-top:9px;" valign="top">
                <table style="max-width:100%; min-width:100%;" class="mcnTextContentContainer" cellspacing="0" cellpadding="0" border="0" width="100%" align="left">
                    <tbody><tr>
                        <td class="mcnTextContent" style="padding-top:0; padding-right:18px; padding-bottom:9px; padding-left:18px;" valign="top">
                            <span style="font-size:14px"><a class="contentLink" href="$urlExtern"><strong>$name</strong></a><br>
    Details dieser Benachrichtigung(en): <a class="contentLink" href="$urlIntern">$urlIntern</span>
                        </td>
                    </tr>
                </tbody></table>
            </td>
        </tr>
    </tbody>
</table>
HEADER;
        return $header;
    }

    private function getHtmlUrlContent($dateTime, $preview)
    {
        $content = <<<CONTENT
<table class="mcnTextBlock" style="min-width:100%;" cellspacing="0" cellpadding="0" border="0" width="100%">
    <tbody class="mcnTextBlockOuter">
        <tr>
            <td class="mcnTextBlockInner" style="padding-top:9px;" valign="top">
                <table style="max-width:100%; min-width:100%;" class="mcnTextContentContainer" cellspacing="0" cellpadding="0" border="0" width="100%" align="left">
                    <tbody><tr>
                        <td class="mcnTextContent" style="padding-top:0; padding-right:18px; padding-bottom:9px; padding-left:18px;" valign="top">
                            <span style="font-size:14px">$dateTime:<br>
                            $preview</span>
                        </td>
                    </tr>
                </tbody></table>
            </td>
        </tr>
    </tbody>
</table>
CONTENT;
        return $content;
    }

    private function getHtmlUrlFooter($groupsUrl, $alertUrl)
    {
        $footer = <<<FOOTER
<td class="mcnTextContent" style="padding-top:0; padding-right:18px; padding-bottom:9px; padding-left:18px;" valign="top">
    <span><a href="$alertUrl">Ändern der Einstellungen</a></span>&nbsp;&nbsp;
    <span><a href="$groupsUrl">Verwalten aller Benachrichtigungen</a></span><br>
    <span style="font-size:12px"><span style="font-family:arial,helvetica neue,helvetica,sans-serif">Monitoor ist ein Produkt der <a href="https://www.datahouse.ch" target="_blank">Datahouse AG</a>.<br>
Copyright © Datahouse AG 2017</span></span>
</td>
FOOTER;
        return $footer;
    }
}
