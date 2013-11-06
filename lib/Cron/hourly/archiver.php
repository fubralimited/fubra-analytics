<?php

/**
 * This job is run every hour to populate the database with past data.
 */

// Suppress strict notices.
error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);

// Autoload Composer modules
require dirname(__DIR__) . '/../../composer/vendor/autoload.php';

// Very first action is to get the config instance and check if archiving is enabled

// Get config object
$config = new \FA\Config();

// Simply exit if archiving is disabled
if ( ! $config->archive['enabled'] ) exit();

// Initialise app
new \FA\Init();

// Get options instance
$options = new \FA\Options();

// Set timezone
date_default_timezone_set($config->timezone);

// Get api instance
$api = new \GA\API();

// If application isn't authenticated alert admin and exit
if( ! $api->authenticated ) {

    mail(

        $config->admin,
        $config->product_name . ' data archiver failed',
        'Error: Application not authenticated'
    );    
    exit();
}

// Start checking data from yesterday backwards

// Start with yesterday's date
$date = strtotime('yesterday');

// Check how many days to archive
$num_days = $config->archive['hourly_rate'];

/**
 * Checks date for data and if found increments $date on day back and continues.
 * If data isn't found it will request the data, move the date back a day and reduce the num_days by one.
 */
while ($num_days) {

    // Form api friendly date
    $f_date = date('Y-m-d', $date);

    // Check date is not older than archive limit set in config file
    if ( $date < strtotime($config->archive['last_date']) ) exit();

    // Check date has data
    $has_data = ORM::for_table('analytics_total')->where('date', $f_date)->count('id');

    // If date doesn't have data, request the data
    if ( ! $has_data) {
        
        // Get data
        $api->get_data($f_date);

        // Decrement num_days
        --$num_days;
    }

    // Else set date back a day and call self
    $date = strtotime('-1 day', $date);

}

