<?php


// Autoload Composer modules else prompt for install
$composer_auto = dirname(__DIR__) . '/composer/vendor/autoload.php';
if (file_exists($composer_auto)) { require $composer_auto; }
else { echo 'Could not load composer modules.<br/>Run install script or see <a target="_blank" href="https://github.com/fubralimited/fubra-analytics">https://github.com/fubralimited/fubra-analytics</a> for more info.'; die(); }

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
 * Google oath redirect url.
 * Simply create a auth instance (which will handle the token) and then redirect to index.
 */
$app->get('/auth', function() use ( $app ) {

	// Redirect home
	$app->redirect('/');
});

/**
 * Google oath sign out.
 * Simply delete auth token and redirect to index.
 */
$app->get('/signout', function() use ( $app ) {

	// Sign out
	$app->auth->sign_out();

	// Redirect home
	$app->redirect('/');
});

/**
 * Single route (GET or POST) that checks first param as controller.
 * If the controller doesn't exist it simply goes to the 404 route handler
 */
$app->map( '/(:main(/(:sub(/))))', function ($main = 'dashboard', $sub = 'index') use ( $app ) {

		// Get template name
		$template = "{$main}/{$sub}.php";

		// If view doesn't exist, pass route. Next route should then be 404
		if( ! file_exists(dirname(__FILE__). "/views/{$main}/{$sub}.php") ) $app->pass();

		// Render view
		$app->render("index.php", array(

				// Pass app instance
				'app' => $app,

				// Pass route parts
				'route' => array(

				        'template' => $template,
        		        'main' => $main,
        		        'sub' => $sub
					)

		    ));
	// Set to accept both get and post
	} )->via('GET', 'POST');;

/**
 * Route: 404
 * Not found handler
 */
$app->notFound( function () use ( $app ) {
		// Render view
		$app->render("index.php", array(

				// Pass app instance
				'app' => $app,

				// Pass route parts
				'route' => array(

				        'template' => '404.php',
        		        'main' => '404',
        		        'sub' => NULL
					)

		    ));

	} );

// -------------------------------------------------------------------

// Run Slim app
$app->run();
