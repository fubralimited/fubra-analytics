<?php

// Autoload Composer modules
require dirname(__DIR__) . '/composer/vendor/autoload.php';

// Get configuration.ini file
$config = parse_ini_file( '../config.ini' );

// Create new Slim instance
$app = new \Slim\Slim( array(

		// Toggle debug
		'debug' => $config['debug'],
		'log.enabled' => $config['debug']
	) );

// Configure idiorm DB instance
new \FA\DB( $config );

// -------------------------------------------------------------------
//        					   Routes
// -------------------------------------------------------------------

/**
 * Route: 404
 * Not found handler
 */
$app->notFound( function () {

		// Respond with empty/invalid data
		echo 'Oops. Not Found.';

	} );

// -------------------------------------------------------------------

/**
 * Route: /airport/code/$code
 * Returns airport by code
 */
$app->get( '/', function () use ( $app ) {

		echo 'Welcome';

	} );

// -------------------------------------------------------------------

// Run Slim app
$app->run();
