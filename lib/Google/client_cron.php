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

// Get Google Auth class
$ga_auth = new \GA\Auth();

// Record data update attempt in options
$options->set('data_updated', date("Y-m-d H:i:s") );

// Check if api authenticated succesfully
if ( ! $ga_auth->success) {

    // Set update status to false
    $options->set('data_success', 'false' );
    
    // Send mail to owner if authentication failed
    mail(
        $config->owner,
        $config->app_name . ' auth failure',
        'Google APIs could not authenticate for offline mode. Please visit site now to update analytics stats.'
    );

    die();
}

// As authentication passed create new service using the authenticated client
$service = new \GA\Data( $ga_auth->client );

// Update all accounts & profiles
$service->update_visits();

// Set update status to true
$options->set('data_success', 'true' );