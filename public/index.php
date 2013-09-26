<?php

// Autoload Composer modules
require dirname(__DIR__) . '/composer/vendor/autoload.php';

// Get configuration.ini file
$config = new \FA\Config();

// Set timezone
date_default_timezone_set($config->timezone);

// Create new Slim instance
$app = new \Slim\Slim( array(

		// Configure slim app
		'debug'          => $config->debug,
		'log.enabled'    => $config->debug,
		'templates.path' => 'views',
	) );

// Add Init Middleware
$app->add(new \FA\Init( $config ));

/**
 * Single route that checks first param as controller.
 * If the controller doesn't exist it simply goes to the 404 route handler
 */
$app->get( '/(:main)', function ($main = 'index') use ( $app ) {

		// Form route controller name
		$routeCtrl = "routes/{$main}.php";

		// If controller exists include it else pass to 404
		file_exists($routeCtrl) ? require($routeCtrl) : $app->pass();

	} );

/**
 * Route: 404
 * Not found handler
 */
$app->notFound( function () use ( $app ) {

		// Respond with empty/invalid data
		$app->render( '404.php' );

	} );

// -------------------------------------------------------------------

// Run Slim app
$app->run();
