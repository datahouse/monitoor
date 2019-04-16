<?php
/**
 * JsonLog ist needed  to track which items have been sent to Monitoor
 */
class JsonLog {
    private static $date_ = null;

    public static function is_in_logfile($text)
    {
        $h = @fopen(self::get_logfile_name(), 'r');
        if ($h === false) {
            return false;
        }
        while ($line = fgets($h)) {
            $line = substr($line, 21,-1);
            if ($text === $line) {
                return true;
            }
        }
        return false;
    }

    public static function add_to_logfile($text)
    {
        $h = @fopen(self::get_logfile_name(),'a');
        if ($h === false) {
            throw new exception('logfile ' . self::get_logfile_name() . ' not writable');
        }
        $d = new DateTime();
        $d = $d->format('Y-m-d\TH:i:s');
        fputs($h, $d . ":\t" . $text . "\n");
    }

    private static function get_logfile_name()
    {
        $date = is_null(static::$date_) ? new DateTime() : static::$date_;
        return __DIR__ . '/../shab/logfiles/json_log_' . $date->format('Ymd') . '.log';
    }

    public static function set_filename_date($date)
    {
        if (!$date instanceof DateTime) {
            $date = new DateTime($date);
        }
        static::$date_ = $date;
    }

}
