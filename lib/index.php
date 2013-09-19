<?php

// Load Google Client lib
require_once 'google-api-php-client/src/Google_Client.php';
require_once 'google-api-php-client/src/contrib/Google_AnalyticsService.php';

// Get config
$config = parse_ini_file( dirname(__DIR__) . '/config.ini' );

session_start();

$client = new Google_Client ();
$client->setApplicationName ( "Fubra Analytics" );
$client->setClientId ( "1055043447351.apps.googleusercontent.com" );
$client->setClientSecret ( "Ms3iSUA1UJNSxN2PaP6pwm8A" );
$client->setRedirectUri ( "http://localhost" );

$client->setDeveloperKey('AIzaSyCOtIwheFQp5ofVrylpPO6A5UN9DW-iuX4');
$client->setScopes(array('https://www.googleapis.com/auth/analytics.readonly'));

// Magic. Returns objects from the Analytics Service instead of associative arrays.
$client->setUseObjects(true);

if (isset($_GET['code'])) {

    $client->authenticate();
    $_SESSION['token'] = $client->getAccessToken();
    $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
}

if (isset($_SESSION['token'])) {

    $client->setAccessToken($_SESSION['token']);
}

if (!$client->getAccessToken()) {
  
    $authUrl = $client->createAuthUrl();
    print "<a class='login' href='$authUrl'>Log In</a>";

} else {
  
    $service = new \Google_AnalyticsService ( $client );

    // // Getting the profile list from the API
    // $profiles = $service->management_profiles->listManagementProfiles ( "~all", "~all" );

    // foreach ($profiles->items as $item) {
    //     echo '<pre>';
    //     print_r($item);
    //     echo '</pre>--------------------------------------------------------------------------------------------------------------';
    // }

    $profiles = $service->management_profiles->listManagementProfiles ( "~all", "~all" );
    $profileList = array ();
    
    foreach ( $profiles->items as $profile ) $profileList[] = $profile->id;

    // Getting data per website/day/page
    $analyticsData = array ();

    foreach ( $profileList as $websiteId => $profileId ) {
        
        $ids = "ga:$profileId";

        $start_date = "2013-04-01";
        $end_date = "2013-04-10";
        $metrics = "ga:visits,ga:pageviews";

        $data = $service->data_ga->get ( $ids, $start_date, $end_date, $metrics );
        foreach ( $data->rows as $row ) {

            echo '<pre>';
            print_r($row);
            echo '</pre>';
        }
    }

}

require 'view.html';
