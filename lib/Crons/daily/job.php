<?php

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

// As authentication passed create new service using the authenticated client
$api = new \GA\API();

// -------------------------------------------------------

// Get dates
$dates = array(

    'yesterday'             => date('Y-m-d', strtotime('yesterday')),
    'yesterday_last_week'   => date('Y-m-d', strtotime('yesterday - 7 days'))
    );

// Get data
$yesterday = $api->get_as_groups( $dates['yesterday'] );
$yesterday_last_week = $api->get_as_groups( $dates['yesterday_last_week'] );

// Prepare data
$template_data = array();


// Set some limits to determine classes
$warn_server_resp = 1;
$max_server_resp = 2;
$warn_page_load = 6;
$max_page_load = 10;
$warn_change = -25;
$max_change = -50;
$good_change = 25;
$success_change = 50;

function get_val_class( $val, $max, $warn ) {

    if ($val >= $max) return 'max';
    elseif ($val >= $warn) return 'warn';
    else return NULL;
}

function get_chg_class( $val, $max, $warn, $good = NULL, $success = NULL ) {

    if( $val >= $success) return 'success';
    elseif ( $val >= $good) return 'good';
    elseif ( $val <= $max) return 'max';
    elseif ( $val <= $warn) return 'warn';
    else return NULL;
}

// Loop groups
foreach ($yesterday['data'] as $group => $date_data) {
    
    // Loop first value (date) in group only as only one date was requested
    foreach ( __::first($date_data) as $profile => $metrics) {

        // Get visitors for last week
        $prev_visitors = __::first($yesterday_last_week['data'][$group]);
        $prev_visitors = floatval($prev_visitors[$profile]['visits']);

        // Get current visitors and visitors chnage from prev week
        $visitors = round(floatval($metrics['visitors']), 2 );

        // Get difference between 2 dtes
        $visitors_diff =  $prev_visitors - $visitors;

        // Check both dates had visitors
        // If either is 0 then percentage change is infinite
        $percent_change = '&#8734;';
        if( $visitors && $prev_visitors ) {

            // Calculate percentage difference
            $percent_change =  $visitors_diff / $prev_visitors;
            $percent_change *= 100;
            $percent_change = round($percent_change);
            $percent_change .= '%';
        }

        // Format floats
        $avg_server_response_time = round(floatval($metrics['avg_server_response_time']), 1 );
        $avg_page_load_time = round(floatval($metrics['avg_page_load_time']), 1 );
        $avg_views_per_visit = round(floatval($metrics['avg_views_per_visit']), 1 );

        // Get classes
        $classes = 

        // Create teplate data
        $template_data[$group][] = array(

                'profile'                  => $profile,
                'url'                      => $metrics['url'],
                'avg_server_response_time' => $avg_server_response_time,
                'avg_page_load_time'       => $avg_page_load_time,
                'visitors'                 => $visitors,
                'percent_change'           => $percent_change,
                'avg_views_per_visit'      => $avg_views_per_visit,
                'class'                    => array(

                        'avg_server_response_time' => get_val_class( $avg_server_response_time, $max_server_resp, $warn_server_resp ),
                        'avg_page_load_time'       => get_val_class( $avg_page_load_time, $max_page_load, $warn_page_load ),
                        'percent_change'           => get_chg_class( $percent_change, $max_change, $warn_change, $good_change, $success_change )
                    )
            );
    }
}

// -------------------------------------------------------

// Use https://github.com/christiaan/InlineStyle to convert email template files to a single email template
// All stylesheets are applied inline, so there's no need to be linked in the template directly. ( prob won't make much of a diff though )

// Get html template
$html = file_get_contents( __DIR__ . '/email_template/template.html' );

// Process as underscore temlpate
// Important this is done prior to inlining as template tags will be converted to special characters
$html = __::template($html, array(

        'config' => $config,
        'date'   => $dates['yesterday'],
        'data'   => $template_data
    ));

// Create new inline instance
$htmldoc = new \InlineStyle\InlineStyle($html);

// Apply all .css stylesheets in template directory
foreach ( glob( __DIR__ . '/email_template/*.css') as $css ) {

    $htmldoc->applyStylesheet(file_get_contents($css));
}

// Process
$email_html = $htmldoc->getHTML();

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

// Set encoding to utf-8
$mail->AddCustomHeader("Content-Type: text/html; charset=UTF-8");

// Set subject
$mail->Subject = "Fubra Analytics {$dates['yesterday']}";

// Set message body
$mail->Body = $email_html;

// Send and check for failure
if( ! $mail->send() ) {
    
    // Send mail to owner if daily mail failed
    mail(

        $config->admin,
        $config->product_name . ' daily cron failed',
        'Mailer Error: ' . $mail->ErrorInfo
    );
}

