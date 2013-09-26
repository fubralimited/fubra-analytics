<?php

namespace FA;

/**
 * Helper class to access config.ini file
 * Instance has section objects which holds array values
 * For more info on ini section see: http://php.net/manual/en/function.parse-ini-file.php
 */
class Config {

    /**
     * Constructor parses confic.ini
     */
    public function __construct() {

        // Parse file with sections
        $config = parse_ini_file( dirname(__DIR__) . '/../config.ini', true );

        // Loop result and create objects from each section
        foreach ($config as $key => $value) $this->{$key} = $value;
    }
}