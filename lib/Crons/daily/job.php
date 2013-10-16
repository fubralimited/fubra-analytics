<?php

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

// As authentication passed create new service using the authenticated client
$api = new \GA\API();

// -------------------------------------------------------

// Get dates
$dates = array(

    'yesterday'             => date('Y-m-d', strtotime('yesterday')),
    'yesterday_last_week'   => date('Y-m-d', strtotime('yesterday - 7 days'))
    );

// Get data
$yesteday = $api->get( $dates['yesterday'] );
$yesteday_last_week = $api->get( $dates['yesterday_last_week'] );

// -------------------------------------------------------

// Prepare email inside buffer
ob_start(); 
include(__DIR__."/template.php");
$email_template = ob_get_contents();
ob_end_clean(); 

// New PHPMailer object
$mail = new PHPMailer;

// Set mailer to use php mail()
$mail->isMail();

// Add sender
$mail->From = 'analytics@fubra.com';
$mail->FromName = 'Fubra Analytics';

  // Add a recipient
$mail->addAddress($config->report['email']);

// Set email format to HTML
$mail->isHTML(true);

// Set subject
$mail->Subject = "Fubra Analytics - {$dates['yesterday']}";

// Set message body
$mail->Body = $email_template;

// Send
if(!$mail->send()) {
   echo 'Message could not be sent.';
   echo 'Mailer Error: ' . $mail->ErrorInfo;
   exit;
}






