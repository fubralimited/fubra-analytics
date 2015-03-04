#!/usr/bin/env php
<?php

// Autoload Composer modules
require dirname(__DIR__) . '/composer/vendor/autoload.php';

// Create crontab instance
$crontab = new \Crontab\Crontab();

// Get new config instance
$config = new \FA\Config();

// Parse config file cron time.
// All crons will be run at this time
$cron_time = date_parse($config->report['time']);

// Set up a jobs array
$jobs = array();

// ---------------------------- Install hourly crons ----------------------------
foreach (glob(dirname(__DIR__) . '/lib/Cron/hourly/*.php') as $cmd) {

    // Create new crontab job
    $job = new \Crontab\Job();
    // Configure
    $job
        ->setMinute('0')
        ->setHour('*')
        ->setDayOfMonth('*')
        ->setMonth('*')
        ->setDayOfWeek('*')
        ->setCommand('php ' . $cmd)
    ;

    // Add job
    $jobs[] = $job;
}

// ---------------------------- Install daily crons ----------------------------
foreach (glob(dirname(__DIR__) . '/lib/Cron/daily/*.php') as $cmd) {

    // Create new crontab job
    $job = new \Crontab\Job();
    // Configure
    $job
        ->setMinute($cron_time['minute'])
        ->setHour($cron_time['hour'])
        ->setDayOfMonth('*')
        ->setMonth('*')
        ->setDayOfWeek('*')
        ->setCommand('php ' . $cmd)
    ;

    // Add job
    $jobs[] = $job;

    // Increment next job by one hour
    $cron_time['hour'] = intval($cron_time['hour']) + 1;
}

// ---------------------------- Install daily crons ----------------------------
foreach (glob(dirname(__DIR__) . '/lib/Cron/weekly/*.php') as $cmd) {

    // Create new crontab job
    $job = new \Crontab\Job();
    // Configure
    $job
        ->setMinute($cron_time['minute'])
        ->setHour($cron_time['hour'])
        ->setDayOfMonth('*')
        ->setMonth('*')
        ->setDayOfWeek($config->report['weekly_day'])
        ->setCommand('php ' . $cmd)
    ;

    // Add job
    $jobs[] = $job;
}

// ---------------------------- Install monthly crons ----------------------------
foreach (glob(dirname(__DIR__) . '/lib/Cron/monthly/*.php') as $cmd) {

    // Create new crontab job
    $job = new \Crontab\Job();
    // Configure
    $job
        ->setMinute($cron_time['minute'])
        ->setHour($cron_time['hour'])
        ->setDayOfMonth($config->report['monthly_day'])
        ->setMonth('*')
        ->setDayOfWeek('*')
        ->setCommand('php ' . $cmd)
    ;

    // Add job
    $jobs[] = $job;
}

// -------------------------------------------------------------------------------


// Remove all existing (related) cron jobs
foreach ( $crontab->getJobs() as $job) {

    // Get job command
    $entries = $job->getEntries();
    $cmd = $entries[5];

    // Check job is related and remove. 
    if ( strpos($cmd, dirname(__DIR__) . '/lib/Cron' ) !== FALSE ) $crontab->removeJob($job);

}

// Install all new jobs
foreach ($jobs as $newJob) $crontab->addJob($newJob);

// Install crontab
$crontab->write();

// Render new crontab file
echo $crontab->render();
echo "\n";
