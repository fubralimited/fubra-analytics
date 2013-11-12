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
$dates = array(

    'yesterday'             => date('Y-m-d', strtotime('yesterday')),
    'yesterday_last_week'   => date('Y-m-d', strtotime('yesterday - 7 days'))
    );


// Get data
$yesterday = $api->get_as_groups( $dates['yesterday'] );
$yesterday_last_week = $api->get_as_groups( $dates['yesterday_last_week'] );

// Prepare data
$template_data = array('record' => false);

// Returns a red or green rgb value based on a value and a base
function get_rgb($val, $base) {

    // Get absolute value
    $abs = abs($val);

    // Set no more than base value
    if ($abs >= $base) $abs = $base;

    // Calculate percentage of 255
    $var_c = round( ($abs/$base) * 255 );

    // Get remaing value of 255
    $remain = round( (255 - $var_c) );

    // Check if val is positive then use green else use red
    $rgb = ($val < 0) ? array( 255, $remain, $remain ) : array( $remain , 255, $remain );

    $hex = "#";
    $hex .= str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
    $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
    $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

    return $hex; // returns the hex value including the shebang
}

// Loop groups
foreach ($yesterday['data'] as $group => $date_data) {
    
    // Loop first value (date) in group only as only one date was requested
    foreach ( __::first($date_data) as $profile => $metrics) {

        // Get prev week data set
        $prev_metrics = __::first($yesterday_last_week['data'][$group])[$profile];

        // Get visitors for last week
        $prev_visitors = $prev_metrics['visitors'];

        // Get current visitors and visitors chnage from prev week
        $visitors = $metrics['visitors'];

        // Get difference
        $visitors_change = \FA\Util::percent_change($prev_visitors, $visitors);

        // Get prev bounce rate (bounces / visits)
        $prev_bounce_rate = $prev_metrics['bounces'];
        $prev_bounce_rate = $prev_metrics['visits'] ? $prev_metrics['bounces'] / $prev_metrics['visits'] : 0;
        $prev_bounce_rate = round($prev_bounce_rate * 100);

        // Get current bounce rate (bounces / visits)
        $bounce_rate = $metrics['bounces'];
        $bounce_rate = $metrics['visits'] ? $metrics['bounces'] / $metrics['visits'] : 0;
        $bounce_rate = round($bounce_rate * 100);

        // Get difference
        $bounce_rate_change = \FA\Util::percent_change($prev_bounce_rate, $bounce_rate);

        // Round views per visit
        $avg_views_per_visit = round($metrics['avg_views_per_visit'], 1 );

        // Check if record traffic was recorded
        $record = ( $visitors > $api->get_record_visitors($metrics['profile_id'], $dates['yesterday']) );

        // Set template record field to true if a record traffic was recorded
        if($record) $template_data['record'] = true;

        // Create teplate data
        $template_data['profiles'][$group][] = array(

                'profile'                  => $profile,
                'url'                      => $metrics['url'],
                'visitors'                 => $visitors,
                'visitors_change'          => $visitors_change,
                'avg_views_per_visit'      => $avg_views_per_visit,
                'bounce_rate'              => $bounce_rate,
                'bounce_rate_change'       => $bounce_rate_change,
                'record'                   => $record
            );
    }
}

// Add group totals
foreach ($template_data['profiles'] as $group => $profiles) {

    // Create totals entry
    $template_data['group_totals'][$group] = array(
    
        'visitors'                 => 0,
        'prev_visitors'            => 0,
        'visitors_change'           => 0
    );

    
    // Add group profile visits    
    foreach ($profiles as $profile) {
        
        // Get visitors for last week
        $prev_visitors = __::first($yesterday_last_week['data'][$group]);
        $prev_visitors = (int)$prev_visitors[$profile['profile']]['visitors'];

        // Add (+) prev week's visitors to group total
        $template_data['group_totals'][$group]['prev_visitors'] += $prev_visitors;

        // Add (+) visitors to group total
        $template_data['group_totals'][$group]['visitors'] += $profile['visitors'];
    }

    // Calculate change
    $template_data['group_totals'][$group]['visitors_change'] = \FA\Util::percent_change(
        $template_data['group_totals'][$group]['prev_visitors'],
        $template_data['group_totals'][$group]['visitors']
    );

}

// Create abs totals entry

// Get total visits for the two dates
$visits_yesterday = $api->get_total_visits($dates['yesterday']);
$visits_last_week = $api->get_total_visits($dates['yesterday_last_week']);

$template_data['totals'] = array(

    'visitors'                 => $api->get_total_visitors($dates['yesterday']),
    'visitors_change'          => \FA\Util::percent_change( $visits_last_week, $visits_yesterday ),
    'page_views'               => $api->get_total_page_views($dates['yesterday'])
);

// -------------------------------------------------------

// Use https://github.com/christiaan/InlineStyle to convert email template files to a single email template
// All stylesheets are applied inline, so there's no need to be linked in the template directly. ( prob won't make much of a diff though )

// Get html template
$html = file_get_contents( __DIR__ . '/email_template/template.html' );

// Process as underscore temlpate
// Important this is done prior to inlining as template tags will be converted to special characters
$html = __::template($html, array(

        'config'  => $config,
        'date'    => $dates['yesterday'],
        'data'    => $template_data
));


// Create new inline instance
$htmldoc = new \InlineStyle\InlineStyle($html);

// Apply all .css stylesheets in template directory
foreach ( glob( __DIR__ . '/email_template/*.css') as $css ) {

    $htmldoc->applyStylesheet(file_get_contents($css));
}

// Process
$email_html = $htmldoc->getHTML();

// Minify html
$email_html = \zz\Html\HTMLMinify::minify($email_html);

// Write report to archives directory
$archive_path = dirname(__DIR__) . '/../../http/archives/daily/' . $dates['yesterday'] . '.html';
file_put_contents($archive_path, $email_html);

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

// Form subject
$subject  = 'Fubra Analytics: ';
$subject .= number_format($template_data['totals']['visitors']);
$subject .= ' (';
// Add percentage change
$subject .= $template_data['totals']['visitors_change'];
// Add percent sign
$subject .= '%) ';
// Add date
$subject .= date('D, M jS', strtotime('yesterday'));
$mail->Subject = $subject;

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

