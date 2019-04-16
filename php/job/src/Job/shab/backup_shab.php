<?php
require_once(__DIR__ . '/../common/JsonLog.php');

$param = getopt('c:',array('config:','help'));
$configName = isset($param['c']) ? $param['c'] : null;
if (is_null($configName)) {
    $configName = isset($param['config']) ? $param['config'] : null;}
if (is_null($configName) || isset($param['help'])) {
    echo "\nusage: -c|--config [test|live]\n";
    echo "--config / -c\tloads conf_###.php\n";
    die(1);
}
require(__DIR__ . '/config_' . $configName . '.php');

try {
    /* init */
    $in_dir = __DIR__ . '/logfiles/';
    $tempdir = exec('mktemp -d'); /* php does not have make temp directory function */
    echo $tempdir . "\n";
    $d = new DateTime();
    $active_file = 'shab_' . $d->format('Y-m-d') . '.xml';
    $tar_filename = BACKUP_DIRECTORY . '/shab_' . ($d->format('Ym')) . '.tar.gz';
    $count = 0;
    /* make sure a backup file is not overwritten */
    while (file_exists($tar_filename)) {
        $tar_filename = BACKUP_DIRECTORY . '/shab_' . ($d->format('Ym')) . '_' . (++$count) . '.tar.gz';
    }

    /* copy files to be backuped to temp dir */
    $files = scandir($in_dir);
    $files_to_backup = [];
    foreach($files as $file) {
        $pinfo = pathinfo($in_dir . '/' . $file);
        if (
                $file != $active_file
            &&
                filetype($in_dir . '/' . $file) == 'file'
            &&
                $pinfo['extension'] == 'xml'
        ) {
            /* copy to tempdir */
            copy($in_dir . '/' . $file, $tempdir . '/' . $file);
            $files_to_backup[] = $file;
        }
    }
    if (count($files_to_backup) == 0) {
        /* todo: throw exception ? */
        echo 'nothing to backup' . "\n";
        die(0);
    }

    /* compress/archive */
    chdir($tempdir);
    $cmd = 'tar czf "' . $tar_filename . '" .';
    $returnval = shell_exec($cmd . '; echo $?');
    if ($returnval != 0) {
        throw new exception('Tar compressing problem. Return value = ' . $returnval);
    }

    /* on successfull tar-ing, remove files + copies */
    foreach ($files_to_backup as $file) {
        unlink($in_dir . '/' . $file);
        unlink($tempdir . '/' . $file);
    }
    rmdir($tempdir);

    /* send email */
    $headers = "Content-type: text/plain; charset=utf-8\r\n";
    $headers .= 'From: ' . EXCEPTION_FROM . "\r\n";
    $txt = 'backup_of_shab made to ' . $tar_filename . ':' . "\n";
    $txt .= implode("\n", $files_to_backup);
    mail(EXCEPTION_TO , 'MON / Backup from ' . __FILE__, $txt, $headers);

} catch (exception $e) {
    JsonLog::send_exception_mail($e);
    die;
}