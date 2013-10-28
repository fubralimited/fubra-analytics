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

// Get api instance
$api = new \GA\API();

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
$template_data = array();

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
    return ($val < 0) ? "rgb(255,{$remain},{$remain})" : "rgb({$remain},255,{$remain})";

}


// Loop groups
foreach ($yesterday['data'] as $group => $date_data) {
    
    // Loop first value (date) in group only as only one date was requested
    foreach ( __::first($date_data) as $profile => $metrics) {

        // Get visitors for last week
        $prev_visitors = __::first($yesterday_last_week['data'][$group]);
        $prev_visitors = floatval($prev_visitors[$profile]['visitors']);

        // Get current visitors and visitors chnage from prev week
        $visitors = round(floatval($metrics['visitors']), 2 );

        // Get difference
        $percent_change = \FA\Util::percent_change($prev_visitors, $visitors);

        // Format floats
        $avg_server_response_time = round(floatval($metrics['avg_server_response_time']), 1 );
        $avg_page_load_time = round(floatval($metrics['avg_page_load_time']), 1 );
        $avg_views_per_visit = round(floatval($metrics['avg_views_per_visit']), 1 );

        // Create teplate data
        $template_data['profiles'][$group][] = array(

                'profile'                  => $profile,
                'url'                      => $metrics['url'],
                'avg_server_response_time' => $avg_server_response_time,
                'avg_page_load_time'       => $avg_page_load_time,
                'visitors'                 => $visitors,
                'percent_change'           => $percent_change,
                'avg_views_per_visit'      => $avg_views_per_visit
            );
    }
}

// Add group totals
foreach ($template_data['profiles'] as $group => $profiles) {

    // Create totals entry
    $template_data['group_totals'][$group] = array(
    
        'visitors'                 => 0,
        'percent_change'           => 0
    );

    // Counter
    $i = 0;
    
    foreach ($profiles as $profile) {
        
        $template_data['group_totals'][$group]['visitors'] += $profile['visitors'];
        
        // Check a change is available. (Not infinty)
        if ( $profile['percent_change'] != '&#8734;' ) {
            
            $template_data['group_totals'][$group]['percent_change'] += $profile['percent_change'];

        // Increment
        ++$i;

        }
    }

    // Devide averages by number of profiles (not visits)
    if($i) $template_data['group_totals'][$group]['percent_change'] /= $i;

    // Round avgs
    $template_data['group_totals'][$group]['percent_change'] = (int)$template_data['group_totals'][$group]['percent_change'];
}

// Create abs totals entry

// Get total visits for the two dates
$visits_yesterday = $api->get_total_visits($dates['yesterday']);
$visits_last_week = $api->get_total_visits($dates['yesterday_last_week']);

$template_data['totals'] = array(

    'visitors'                 => $api->get_total_visitors($dates['yesterday']),
    'percent_change'           => \FA\Util::percent_change( $visits_last_week, $visits_yesterday ),
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
echo $email_html = $htmldoc->getHTML();



// // New PHPMailer object
// $mail = new PHPMailer;

// // Set mailer to use php mail()
// $mail->isMail();

// // Add sender
// $mail->From = 'analytics@fubra.com';
// $mail->FromName = 'Fubra Analytics';

//   // Add a recipient
// $mail->addAddress($config->report['email']);

// // Set email format to HTML
// $mail->isHTML(true);

// // Set encoding to utf-8
// $mail->AddCustomHeader("Content-Type: text/html; charset=UTF-8");

// // Form subject
// $subject  = 'Fubra Analytics (';
// // Check if + or -
// $subject .= ($template_data['totals']['percent_change'] > 0) ? '+' : '-';
// // Add percentage change
// $subject .= $template_data['totals']['percent_change'];
// // Add percent sign
// $subject .= '%) ';
// // Add date
// $subject .= date('D, M jS', strtotime('yesterday'));
// $mail->Subject = $subject;

// // Set message body
// $mail->Body = $email_html;

// // Send and check for failure
// if( ! $mail->send() ) {
    
//     // Send mail to owner if daily mail failed
//     mail(

//         $config->admin,
//         $config->product_name . ' daily cron failed',
//         'Mailer Error: ' . $mail->ErrorInfo
//     );
// }

