<?php

// Suppress strict notices.
error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);

// Autoload Composer modules
require dirname(__DIR__) . '/../../composer/vendor/autoload.php';

// Initialise app
new \FA\Init();

// Get options instance
$options = new \FA\Options();

// Get config object
$config = new \FA\Config();

// Set timezone
date_default_timezone_set($config->timezone);

// Get api instance
$api = new \GA\API();

// If application isn't authenticated alert admin and exit
if( ! $api->authenticated ) {

    mail(

        $config->admin,
        $config->product_name . ' daily cron failed',
        'Error: Application not authenticated'
    );
    exit();
}

// -------------------------------------------------------

// Form dates
$date = date('Y-m-d', strtotime('yesterday'));

// Get data
$data = $api->get_airport_path_data($date);


// Write report to archives directory
$archive_path = dirname(__DIR__) . '/../../http/archives/airport_guides/' . $date . '.csv';

// Write csv headers
file_put_contents( $archive_path, "Path,Pageviews,Sessions,Bounces\r\n" );

// Write csv rows
foreach ($data as $path_data) {

    // Format csv line
    $line  = $path_data['path'] . ',';
    $line .= $path_data['pageviews'] . ',';
    $line .= $path_data['sessions'] . ',';
    $line .= $path_data['bounces'] . "\r\n";

    file_put_contents( $archive_path, $line, FILE_APPEND );
}


// New PHPMailer object
$mail = new PHPMailer;

// Set mailer to use php mail()
$mail->isMail();

// Add sender
$mail->From = $config->product_email;
$mail->FromName = $config->product_name;

// Add recipients
foreach ( explode(',', $config->report['ag_emails']) as $email) {

    // Add a recipient
    $mail->addAddress(trim($email));
}

// Add report as attachment
$mail->addAttachment($archive_path, "ag_report_{$date}.csv");

// Set subject
$mail->Subject = 'Airport Guides Content Report - ' . date('D, M jS', strtotime('yesterday'));

// Set message body
$mail->Body = "Please find yesterday's Airport Guides content report attached.\n\nAlternitively visit http://analytics.fubra.com/reports/airport_guides for archived reports.";

// Send and check for failure
if( ! $mail->send() ) {

    // Send mail to owner if daily mail failed
    mail(

        $config->admin,
        $config->product_name . ' daily cron failed',
        'Mailer Error: ' . $mail->ErrorInfo
    );
}

