<?php

namespace FA;

use \ORM;
use \GA\Auth;
use \GA\API;

class Init extends \Slim\Middleware
{

    /**
     * Parsed Config.ini values
     * @var array
     */
    private $config;

    /**
     * Constructor references configuration array and initialises Idiorm DB connection
     */
    public function __construct() {

        // Ref configuration
        $this->config = new Config();

        // Initialise database
        $this->initIdiorm();

    }

    /**
     * Slim Middleware hook
     */
    public function call() {

        // Add configuration to app instance
        // This allows config vars to be easily accessible in templates etc.
        $this->app->config = $this->config;

        // Add instance of \FA\Options to app instance
        $this->app->option = new Options();

        // Check app is authenticated
        $this->app->auth = new Auth();

        // Create new api instance and pass auth client
        // If auth cliet isn't passed to api a second auth instance will be created by the api which can lead to some proeblems
        $this->app->api = new API($this->app->auth->client);

        // Continue app routing
        $this->next->call();
    }

    /**
     * Configures Idiorm ORM.
     * Depends on config array with following fields:
     * debug, hostname, username, password, database, deftable
     *
     * @param Array   $config Database configuration options
     */
    private function initIdiorm() {

        // Configure connection
        ORM::configure( "mysql:host={$this->config->database['hostname']};dbname={$this->config->database['database']}" );
        ORM::configure( 'username', $this->config->database['username'] );
        ORM::configure( 'password', $this->config->database['password'] );

        // Configure error mode
        ORM::configure( 'driver_options', array( \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8' ) );
        ORM::configure( 'error_mode', ( $this->config->debug ? \PDO::ERRMODE_WARNING : false ) );

        // Idiorm caching can cause stale results, so keep it off
        ORM::configure( 'caching', false );

        // Enable result sets (objects)
        ORM::configure( 'return_result_sets', true );

        // Debugging exsposes:
        // ORM::get_last_query()
        // ORM::get_query_log()
        ORM::configure( 'logging', $this->config->debug );
    }
}