<?php

namespace FA;

/**
 * Basic log writer class
 */
class Log {

    /**
     * File path to write logs to
     * @var string
     */
    private static $lpath = '/../logs.txt';

    /**
     * Writes line to config file
     */
    public static function error($message) {

        echo self::$lpath;

        // Log time
        $time = date('Y-M-D');

        // Log line
        $line = "[{$time}] {$message}\n";

        // Write log
        file_put_contents(self::$lpath, $line, FILE_APPEND);
    }

}
