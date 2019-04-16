<?php

namespace Datahouse\MON\Job;

use Datahouse\MON\Types\Report;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Worksheet_PageSetup;
use PHPExcel_Cell;
use DateTime;

/**
 * Class AWPReport
 *
 * @package
 * @author      Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2016 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class AWPReport
{
    /**
     * @var \PDO
     */
    private $pdo;
    /**
     * @var
     */
    protected $argv;

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
        //call php ./JobDispatcher.php AWPReport [url_group_id] [url_id]
        //php ./JobDispatcher.php AWPReport 126 2
        $urlGroupId = $this->argv[2];
        $urlId = $this->argv[3];
        $subscribers = $this->getSubscribers($urlGroupId, $urlId);
        if (count($subscribers) > 0) {
            $log = new \rpt_rpt(
                \rpt_level::L_INFO,
                count($subscribers) . ' subscriber found '
            );
            $log->end();
        }
        $envConfig =
            file_get_contents('conf/.env.conf.json');
        $envConf = json_decode($envConfig, true);
        $emailFrom = $envConf['email_from'];
        $emailTo = $envConf['awp_email'];
        
        $excel = $this->create_excel();
        $as = $excel->getActiveSheet();
        $excelTempFilename = __DIR__ . '/' . uniqid('excel_AWPReport_') . '.xlsx';
        $df = new DateTime();
        $excelRealFilename = 'AWPReport_' . $df->format('d.m.Y') . '.xlsx';
        $colNr = -1;
        $rowNr = 1;
        $as->setCellValueByColumnAndRow(++$colNr, $rowNr, 'Abotyp');
        $as->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($colNr))->setWidth(12);
        $as->setCellValueByColumnAndRow(++$colNr, $rowNr, 'E-Mail');
        $as->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($colNr))->setWidth(35);
        $as->setCellValueByColumnAndRow(++$colNr, $rowNr, 'Vorname');
        $as->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($colNr))->setWidth(20);
        $as->setCellValueByColumnAndRow(++$colNr, $rowNr, 'Nachname');
        $as->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($colNr))->setWidth(20);
        $as->setCellValueByColumnAndRow(++$colNr, $rowNr, 'Firmenname');
        $as->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($colNr))->setWidth(20);
        $as->setCellValueByColumnAndRow(++$colNr, $rowNr, 'Aktivierung');
        $as->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($colNr))->setWidth(10);
        $as->setCellValueByColumnAndRow(++$colNr, $rowNr, 'Deaktivierung');
        $as->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($colNr))->setWidth(10);
        foreach ($subscribers as $subscriber) {
            $colNr = 0;
            ++$rowNr;
            $as->setCellValueByColumnAndRow($colNr++,$rowNr,$subscriber->getPrice());
            $as->setCellValueByColumnAndRow($colNr++,$rowNr,$subscriber->getEmail());
            $as->setCellValueByColumnAndRow($colNr++,$rowNr,$subscriber->getFirstName());
            $as->setCellValueByColumnAndRow($colNr++,$rowNr,$subscriber->getLastName());
            $as->setCellValueByColumnAndRow($colNr++,$rowNr,$subscriber->getCompany());
            $as->setCellValueByColumnAndRow($colNr++,$rowNr,$subscriber->getActivation());
            $as->setCellValueByColumnAndRow($colNr++,$rowNr,$subscriber->getCancellation());
        }
        $this->save_excel($excel, $excelTempFilename);

        $body = '<span>Guten Tag</span><br/><br/>';
        $body .= '<span>Beiliegend erhalten Sie das monatliche Reporting der AWP Abos im Monitoor.</span><br/><br/>';
        $body .= '<span>Freundliche Grüsse, Datahouse AG</span><br/>';
        
        $mail = new \PHPMailer();
        $mail->From = $emailFrom;
        $mail->FromName = "Monitoor";
        $mail->addAddress($emailTo, 'AWP');
        $mail->addBCC($envConf['exception_to'], 'Monitoor Support');
        $mail->isHTML(true);
        $mail->CharSet = "utf-8";
        $mail->Subject = 'Monitoor: AWP Reporting';
        $mail->Body = $body;
        $mail->AltBody = $body;
        $mail->addAttachment($excelTempFilename, $excelRealFilename);
        if ($envConf['environment'] == 'develop') {
            $log = new \rpt_rpt(
                \rpt_level::E_NOTICE,
                $body
            );
            $log->end();
        }
        if (!$mail->send()) {
            unlink($excelTempFilename);
            throw new \Exception(
                'error while sending AWP Report'
            );
        }
        unlink($excelTempFilename);

        $this->deleteOutdatedChanges($urlId);
    }

    /**
     * getSubscribers
     *
     * @param $urlGroupId
     * @param $urlId
     *
     * @return array
     * @throws \Exception
     */
    private function getSubscribers($urlGroupId, $urlId)
    {
        $query = '';
        $query .= 'SELECT pricing_plan_text, user_email, account_name_first, ';
        $query .= 'account_name_last, account_company, activation, cancellation FROM ';
        $query .= '(SELECT user_id, MAX(subscription_ts) AS activation FROM user_subscription ';
        $query .= ' WHERE user_action = \'subscribe\' AND url_group_id = :groupId AND url_id = :urlid GROUP BY user_id) as a ';
        $query .= ' LEFT JOIN (SELECT user_id, MAX(subscription_ts) AS cancellation FROM ';
        $query .= ' user_subscription WHERE user_action = \'unsubscribe\' ';
        $query .= ' AND url_group_id = :groupId AND url_id = :urlid GROUP BY user_id) as b ON (a.user_id=b.user_id) ';
        $query .= ' JOIN mon_user u ON (a.user_id = u.user_id) JOIN account ac ';
        $query .= ' ON (a.user_id = ac.user_id) JOIN pricing_plan USING (pricing_plan_id) ';
        $query .= ' ORDER BY account_name_last, account_name_first ';
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':groupId', $urlGroupId, \PDO::PARAM_INT);
        $stmt->bindValue(':urlid', $urlId, \PDO::PARAM_INT);
        $stmt->execute();
        $subscribers = array();
        foreach ($stmt->fetchAll() as $res) {
            $report = new Report();
            $report->setPrice($res['pricing_plan_text']);
            $report->setEmail($res['user_email']);
            $report->setActivation(
                date_format(new \DateTime($res['activation']), 'd.m.Y')
            );
            if ($res['cancellation'] != null) {
                $report->setCancellation(
                    date_format(new \DateTime($res['cancellation']), 'd.m.Y')
                );
            }
            $report->setCompany($res['account_company']);
            $report->setFirstName($res['account_name_first']);
            $report->setLastName($res['account_name_last']);
            $subscribers[] = $report;
        }
        return $subscribers;
    }

    /**
     * deleteOutdatedChanges
     *
     * @param $urlId
     *
     * @return void
     * @throws \Exception
     */
    private function deleteOutdatedChanges($urlId)
    {
        try {
            $this->pdo->beginTransaction();

            $query = '';
            $query .= 'SELECT c.change_id FROM change c JOIN change_x_url USING (change_id) ';
            $query .= ' WHERE url_id=:urlid AND c.ts < date(NOW() - INTERVAL \'1 years\'); ';
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':urlid', $urlId, \PDO::PARAM_INT);
            $stmt->execute();
            $changeIds = array();
            foreach ($stmt->fetchAll() as $res) {
                $changeIds[] = $res['change_id'];
            }
            $log = new \rpt_rpt(
                \rpt_level::E_NOTICE,
                count($changeIds) . ' changes to delete'
            );
            $log->end();
            if (count($changeIds) > 0) {
                $query = 'DELETE FROM change_x_url WHERE change_id=:changeid';
                $stmt = $this->pdo->prepare($query);
                foreach ($changeIds as $changeId) {
                    $stmt->bindValue(':changeid', $changeId, \PDO::PARAM_INT);
                    $stmt->execute();
                }
                $query = 'DELETE FROM notification_x_keyword WHERE change_id=:changeid';
                $stmt = $this->pdo->prepare($query);
                foreach ($changeIds as $changeId) {
                    $stmt->bindValue(':changeid', $changeId, \PDO::PARAM_INT);
                    $stmt->execute();
                }
                $query = 'DELETE FROM notification WHERE change_id=:changeid';
                $stmt = $this->pdo->prepare($query);
                foreach ($changeIds as $changeId) {
                    $stmt->bindValue(':changeid', $changeId, \PDO::PARAM_INT);
                    $stmt->execute();
                }
                $query = 'DELETE FROM rating WHERE change_id=:changeid';
                $stmt = $this->pdo->prepare($query);
                foreach ($changeIds as $changeId) {
                    $stmt->bindValue(':changeid', $changeId, \PDO::PARAM_INT);
                    $stmt->execute();
                }
                $query = 'DELETE FROM change WHERE change_id=:changeid';
                $stmt = $this->pdo->prepare($query);
                foreach ($changeIds as $changeId) {
                    $stmt->bindValue(':changeid', $changeId, \PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
            $this->pdo->commit();
            return;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new \Exception($e . ' error while deleting outdated changes');
        }
    }

    /**
     * @return PHPExcel
     * @throws \PHPExcel_Exception
     */
    private function create_excel() {
        $excel = new PHPExcel();
        $excel->setActiveSheetIndex(0);
        $as = $excel->getActiveSheet();
        $ps = $as->getPageSetup();

        $ps->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
        $ps->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
        $ps->setFitToPage(true);
        $ps->setFitToWidth(1);
        $ps->setFitToHeight(0);

        $excel->getDefaultStyle()->getFont()->setName('Arial')->setSize(11);

        return $excel;
    }

    /**
     * @param PHPExcel $excel
     * @param $filename
     * @throws \PHPExcel_Reader_Exception
     */
    public function save_excel(PHPExcel $excel, $filename) {

        $writer = PHPExcel_IOFactory::createWriter(
            $excel, 'Excel2007'
        );

        $writer->save($filename);
    }
    
}
