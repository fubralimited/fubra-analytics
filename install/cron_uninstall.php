#!/usr/bin/env php
<?php

// Autoload Composer modules
require dirname(__DIR__) . '/composer/vendor/autoload.php';

// Create crontab instance
$crontab = new \Crontab\Crontab();

// Render current crontab file
echo $crontab->render();

// Remove all existing (related) cron jobs
foreach ( $crontab->getJobs() as $job) {

    // Get job command
    $entries = $job->getEntries();
    $cmd = $entries[5];

    // Check job is related and remove. 
    if ( strpos($cmd, dirname(__DIR__) . '/lib/Cron' ) !== FALSE ) $crontab->removeJob($job);
}

// Install crontab
$crontab->write();