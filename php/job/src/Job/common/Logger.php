<?php
/*
 * Logger ist needed for 2 functions:
 * 1: general logging
 * 2: sending exception mail.
 */
class Logger {
    private static $log_filename_ = null;
    private static $echo_ = true;

    /* the other logfile */
    public static function set_log_filename($filename = null)
    {
        static::$log_filename_ = $filename;
    }

    public static function log($text)
    {
        if (static::$echo_) {
            echo $text . "\n";
        }
        if (!is_null(static::$log_filename_)) {
            file_put_contents(static::$log_filename_,  $text . "\n", FILE_APPEND);
        }
    }

    /**
     * send an exception email
     * redact texts if in redactions array (i.e. passwords)
     * @param exception $e
     */
    public static function send_exception_mail(exception $e, $redactions = [])
    {
        $headers = "Content-type: text/plain; charset=utf-8\r\n";
        $headers .= 'From: ' . EXCEPTION_FROM . "\r\n";
        $txt = 'Exception: ' . var_export($e,true)
            . "\n" . 'Trace:' . "\n"
            . var_export(debug_backtrace(), true);
        $txt = substr($txt, 0, 100000);
        $t = array_fill(0,count($redactions),'******');
        $txt = str_replace($redactions, $t, $txt);
        mail(EXCEPTION_TO , 'MON / Exception from ' . __FILE__, $txt, $headers);
    }

}
