<?php

namespace FA;

// Add dependancies
use \ORM;

/**
 * Class depends on Idiorm being loaded in the app/global namespace
 * Constructor requires database settings as array: debug, hostname, username, password, database, deftable
 */
class DB
{

    /**
     * Configures Idiorm ORM.
     * Depends on config array with following fields:
     * debug, hostname, username, password, database, deftable
     *
     * @param Array   $config Database configuration options
     */
    public function __construct( $config ) {

        // Configure connection
        ORM::configure( "mysql:host={$config['db_hostname']};dbname={$config['db_database']}" );
        ORM::configure( 'username', $config['db_username'] );
        ORM::configure( 'password', $config['db_password'] );

        // Configure error mode
        ORM::configure( 'driver_options', array( \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8' ) );
        ORM::configure( 'error_mode', ( $config['debug'] ? \PDO::ERRMODE_WARNING : false ) );

        // API consists of reads only, so enable simple memory cache
        ORM::configure( 'caching', $config['debug'] );

        // Enable result sets
        ORM::configure( 'return_result_sets', true );

        // Debugging exsposes:
        // ORM::get_last_query()
        // ORM::get_query_log()
        ORM::configure( 'logging', $config['debug'] );

    }

}