<?php

// Get configuration.ini file
$config = parse_ini_file( '../config.ini' );

// Autoload Composer modules
require dirname(__DIR__) . '/composer/vendor/autoload.php';

// Create new Slim instance
$api = new \Slim\Slim( array(

		// Toggle debug
		'debug' => $config['debug'],
		'log.enabled' => $config['debug']
	) );

// -------------------------------------------------------------------
// 				  Load middleware in order to execute
// -------------------------------------------------------------------

// Use ORM Middleware
// $api->add( new \FA\DB( $config ) );


// -------------------------------------------------------------------
//        					   Routes
// -------------------------------------------------------------------

/**
 * Route: 404
 * Not found handler
 */
$api->notFound( function () {

		// Respond with empty/invalid data
		echo 'Oops. Not Found.';

	} );

// -------------------------------------------------------------------

/**
 * Route: /airport/code/$code
 * Returns airport by code
 */
$api->get( '/', function () {

		// Respond with custom error
		echo 'Welcome!';

	} );

// -------------------------------------------------------------------

// Run Slim app
$api->run();
