#!/usr/bin/env php
<?php

// Get configuration.ini file
$config = parse_ini_file( dirname(__DIR__) . '/config.ini' );

exit();

// Create db object
$db = new mysqli(
            $config['hostname'],
            $config['username'],
            $config['password'],
            $config['database']
        );

// Check db details worked
if($db->connect_errno > 0) throw new Exception('Unable to connect to database [' . $db->connect_error . ']', 1);

// Get table structure
$sql = file_get_contents(dirname(__DIR__) . '/install/database_structure.sql');

// Check tables where created ok
if(!$result = $db->multi_query($sql)) throw new Exception('There was an error running the query [' . $db->error . ']', 1);
