<?php

// Autoload Composer modules
require dirname(__DIR__) . '/../composer/vendor/autoload.php';

// Initialise app
new \FA\Init();

// Get options instance
$options = new \FA\Options();

// Get config object
$config = new \FA\Config();

// Set timezone
date_default_timezone_set($config->timezone);

// As authentication passed create new service using the authenticated client
$service = new \GA\Data();

// -------------------------------------------------------
// Update data for past month and same data one year ago
// -------------------------------------------------------

// Get date ranges
$ranges = array(

    'month_to'      => date('Y-m-d', strtotime('yesterday')),
    'month_from'    => date('Y-m-d', strtotime('-30 days')),
    'month_to_ly'   => date('Y-m-d', strtotime('-1 year yesterday')),
    'month_from_ly' => date('Y-m-d', strtotime('-1 year -30 days'))

    );

// Update last month
$service->get_data( $ranges['month_from'], $ranges['month_to'] );

// Update last month a year ago
$service->get_data( $ranges['month_from_ly'], $ranges['month_to_ly'] );

// -------------------------------------------------------

// Prepare email inside buffer
ob_start(); 
include("email_template.php");
$email_template = ob_get_contents();
ob_end_clean(); 



// Set email headers
$to = $config->report['email'];
$subject = "Fubra Analytics - " . date('Y-m-d');

// Send email
mail( $to, $subject, $email_template);






