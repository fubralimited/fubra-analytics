<?php

namespace GA;

use \ORM;
use \FA\Options;
use \FA\Config;

/**
* Google API oAuth class
*/
class Data {

    /**
     * Google service object
     * @var object
     */
    private $service;

    /**
     * API metrics to include in data.
     * Max of 10 allowed.
     * @var array
     */
    private $metrics = array(

        'ga:bounces',
        'ga:visits',
        'ga:visitBounceRate',
        'ga:avgTimeOnSite',
        'ga:visitors',
        'ga:percentNewVisits',
        'ga:avgPageLoadTime',
        'ga:avgServerResponseTime',
        'ga:avgDomContentLoadedTime',
        'ga:pageviewsPerVisitoo'
    );

    /**
     * Creates a new service object from the authorised GA client
     */
    function __construct($client) {

        $this->service = new \Google_AnalyticsService ( $client );
    }

    /**
     * Retreives all accounts for the current user
     * @return array Array of accounts
     */
    private function update_accounts() {

        // Get array of all accounts
        $accounts = $this->service->management_accounts->listManagementAccounts ();

        // Add all accounts to database
        foreach ($accounts['items'] as $account) {
            
            // Check if account exists
            if ( ! $entry = ORM::for_table('accounts')->where( 'id', $account['id'] )->find_one() ) {
                
                // Create if not already in db
                $entry = ORM::for_table('accounts')->create();
                $entry->id = $account['id'];
                
            }

            // Update entry
            $entry->name = $account['name'];
            
            // Update time in case nothing changed (likely)
            $entry->set_expr('updated', 'CURRENT_TIMESTAMP');

            // Save
            $entry->save();
        }

        // Return account items
        return $accounts['items'];
    }

    /**
     * Retreives all profiles for the current user
     * @return array Array of profiles
     */
    private function update_profiles() {

        // Get array of all profiles
        $profiles = $this->service->management_profiles->listManagementProfiles ( "~all", "~all");

        // Add all profiles to database
        foreach ($profiles['items'] as $profile) {
            
            // Check if profile exists
            if ( ! $entry = ORM::for_table('profiles')->where( 'id', $profile['id'] )->find_one() ) {
                
                // Create if not already in db
                $entry = ORM::for_table('profiles')->create();
                $entry->id = $profile['id'];
                
            }

            // Update entry
            $entry->name = $profile['name'];
            $entry->account_id = $profile['accountId'];
            $entry->web_property_id = $profile['webPropertyId'];
            $entry->name = $profile['name'];
            $entry->website_url = $profile['websiteUrl'];
            $entry->type = $profile['type'];

            // Update time in case nothing changed (likely)
            $entry->set_expr('updated', 'CURRENT_TIMESTAMP');

            // Save
            $entry->save();
        }

        // Return profile items
        return $profiles['items'];
    }

    /**
     * Returns the last x number of api errors
     * @param  int $num Number of latest errors to return
     * @return array Api error messages and time
     */
    public function get_api_errors( $num ) {

        if ($num) {

            return ORM::for_table('api_errors')
                ->order_by_desc('id')
                ->limit($num)->find_array();
        }
    }

    /**
     * Requests data from API.
     * Data can be requested for a day or a month only.
     * All sites are updated.
     * Arguments include
     *     - year
     *     - month
     *     - day ( in which case only that day's data will be retreived )
     *     - mobile ( bool. Will only return mobile platform data )
     *     
     * @param  array $args Arguments
     * @return int Return false if all api calls was succesfull or an int for the number of api errors.
     *                    API errors can be retreived with get_api_errors()
     */
    private function get_data( $args ) {

        // Update accounts
        $this->update_accounts();

        // Update and get current user's profiles
        $profiles = $this->update_profiles();

        // Check if daily data or monthly
        if ( ! isset($args['day']) ) {
            
            // Default to start day as the 1st
            $day_start = '01';

            // Set end day to last day of the month
            $day_end = date( "t", strtotime("{$args['year']}-{$args['month']}") );

            // Change table to monthly
            $table = 'metrics_monthly';
        
        } else {
            
            // Set table name to daily
            $table = 'metrics_daily';

            // Set start & finish days as the same (daily)
            $day_start = $args['day'];
            $day_end = $args['day'];
        }
        
        // Form dates after determining daily or monthly
        // Passing dates through strtotime to ensure leading 0's are added as api is very strict
        
        $start_date = date( 'Y-m-d',
            strtotime( "{$args['year']}-{$args['month']}-{$day_start}" ));
        
        $end_date = date( 'Y-m-d',
            strtotime( "{$args['year']}-{$args['month']}-{$day_end}" ));

        // Loop profiles and get metrics
        // foreach ( $profiles as $profile ) {

        //     // Form id
        //     $id = "ga:{$profile['id']}";

        //     // Get data from api
        //     $data = $this->service->data_ga->get ( $id, $start_date, $end_date, $this->get_metrics() );

        // }

        ######################################################################## LOOP
        

        // Form id
        $id = "ga:{$profiles[0]['id']}";

        // Api resonse data
        $metrics = NULL;

        // Track number of api errors
        $api_errors = 0;

        // Try tp get data from api and store to in db
        try {

            $metrics = $this->service->data_ga->get( $id, $start_date, $end_date, $this->get_metrics() );
        
        // NOTE: Exception is not a standard php exception, but a google_service exception hence the getMessage()
        } catch ( \Exception $e ) {

            // Write to db
            $this->log_api_error( $e->getMessage() );

            // Increment api error
            ++$api_errors;
        }

        // Store metrics in database if avaialble
        if ($metrics) $this->store_metrics( $metrics, $table );

        ######################################################################## LOOP

        // Return only number of errors or false if none
        return $api_errors ? $api_errors : false;

    }

    /**
     * Creates a monthly or daily entry on the database
     * @param  array $metrics API response metrics
     * @param  string $table  Table name to store metrics daily/monthly
     */
    private function store_metrics( $metrics, $table ) {

        // Sort data
        $date    = $metrics['query']['start-date'];
        $profile = $metrics['profileInfo'];
        $data    = $metrics['totalsForAllResults'];

        // Create entry
        $row = ORM::for_table($table)->create();

        // Set profile data
        $row->profile_id = $profile['profileId'];
        $row->account_id = $profile['accountId'];

        // Set rows data
        $row->visits                      = $data['ga:visits'];
        $row->unique_visits               = $data['ga:visitors'];
        $row->bounces                     = $data['ga:bounces'];
        $row->bounce_rate                 = $data['ga:visitBounceRate'];
        $row->avg_time_on_site            = $data['ga:avgTimeOnSite'];
        $row->avg_page_views              = $data['ga:pageviewsPerVisit'];
        $row->percent_new_visits          = $data['ga:percentNewVisits'];
        $row->avg_page_load_time          = $data['ga:avgPageLoadTime'];
        $row->avg_server_response_time    = $data['ga:avgServerResponseTime'];
        $row->avg_dom_content_loaded_time = $data['ga:avgDomContentLoadedTime'];

        // Add metrics day
        $row->date = $date;

        // Store metrics
        $row->save();

    }

    /**
     * Writes a google service exception to the database
     * @param  Google_Service exceptino $message Message returned from service exception
     */
    private function log_api_error( $message ) {

        // Write API error
        $error = ORM::for_table('api_errors')->create();
        $error->error = $message;
        $error->save();
    }

    /**
     * Forms metrics string
     * Bit unnecasary, but makes writing metrics bit nicer in array format
     * @return [type] [description]
     */
    private function get_metrics() {

        return implode(',', $this->metrics);
    }


    /**
     * Get all site visits for available profiles
     * @return [type] [description]
     */
    public function update_report_visits() {
        
        $this->get_data( array( 'month' => '08', 'year' => '2013', 'day' => '22') );
    }

}
