<?php

namespace GA;

use \ORM;
use \Google_AnalyticsService;
use \FA\Options;
use \FA\Config;

/**
* Main api data handler class
*/
class Data {

    /**
     * Authenticated status indicated whether a client could be created
     * Defaults to false as only set true once authenticated.
     * Should be checked before calling any data methods
     * @var boolean
     */
    public $authenticated = false;

    /**
     * Google service object
     * @var object
     */
    private $service;

    /**
     * Client accounts
     * @var array
     */
    private $accounts;

    /**
     * FA\Config instance
     * @var object
     */
    public $config;

    /**
     * FA\Options instance
     * @var object
     */
    public $options;

    /**
     * API metrics to include in data.
     * Max of 10 allowed.
     * @var array
     */
    private $metrics = array(

        'ga:visits',
        'ga:visitors',
        'ga:newVisits',
        'ga:bounces',
        'ga:pageviewsPerVisit',
        'ga:avgTimeOnSite',
        'ga:avgPageLoadTime',
        'ga:avgServerResponseTime'
    );

    /**
     * Creates a new service object from the authorised GA client
     */
    function __construct() {

        // Get config instance
        $this->config = new Config();

        // New options instance
        $this->options = new Options();

        // Perform authentication
        if ( $client = $this->authenticate() ) {
    
            // Create new google service from authenticated client            
            $this->service = new Google_AnalyticsService ( $client );

            // Update accounts
            $this->update_accounts();

            // Update and get current user's profiles
            $this->update_profiles();
   
        }
    }

    /**
     * Performs oauth authentication and returns status bool
     * Also emails admin should login fail.
     * @return Google client object
     */
    private function authenticate() {

        // Get Google Auth class
        $ga_auth = new Auth();

        // Check if api authenticated succesfully
        if ( ! $ga_auth->success) {
            
            // Send mail to owner if authentication failed
            mail(

                $this->config->owner,
                $this->config->app_name . ' auth failure',
                'Google APIs could not authenticate for offline mode. Please visit site now to update analytics stats.'
            );

            return false;
        }

        // Set authenticated as true
        $this->authenticated = true;

        // Return auth client
        return $ga_auth->client;
    }

    /**
     * Retreives all accounts for the current user
     * @return array Array of accounts
     */
    private function update_accounts() {

        // Get array of all accounts
        $accounts = $this->service->management_accounts->listManagementAccounts ();

        // Empty accounts table if new accounts found
        if ($accounts) ORM::get_db()->exec('TRUNCATE `accounts`');

        // Add all accounts to database
        foreach ($accounts['items'] as $account) {
                
            // Create if not already in db
            $entry = ORM::for_table('accounts')->create();
            
            // Set vaues
            $entry->id = $account['id'];
            $entry->name = $account['name'];

            // Save
            $entry->save();
        }

        // Update instance var
        $this->accounts = $accounts['items'];

        // Return account items
        return $this->accounts;
    }

    /**
     * Retreives all profiles for the current user
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

            // Save
            $entry->save();
        }
    }

    /**
     * Gets metrics for a given day or range
     * If data does not exist in database, do an api call
     * @param  string  $date_start First (or only) day to return
     * @param  string  $date_end   Last day data to return
     * @return array               Metrics data
     */
    protected function get_data( $date_start, $date_end = NULL ) {

        // Set days array ( if no end date simply add start to days array)
        $days = $date_end ? self::get_days( $date_start, $date_end ) : array($date_start);

        // Empty data array
        $res = array( 'data' => array(), 'errors' => array() );

        // Loop days and get data form either databse or api
        foreach ($days as $day) {

            // Get data fro day from db
            $data = self::get_metric( $day );

            // If no data was returned, do an api call to get the data
            if ( ! $data ) {
                
                // Api returns the number of api errors if any
                $api_errors = $this->get_api_data($day);

                // Add errors if any
                if ( count($api_errors) ) $res['errors'][] = $api_errors;
                
                // Get data again
                $data = self::get_metric( $day );
            }

            // Set data
            $res['data'][$day] = $data;
        }

        // Return results
        return $res;
    }

    /**
     * Requests data from API.
     * Data can be requested for a day only.
     * All sites are updated.
     * 
     * @param  string  $date   Date of data to retreive. Ideally with php format "Y-m-d"
     * @return int Return false if all api calls was succesfull or an int for the number of api errors.
     *             API errors can be retreived with get_api_errors()
     */
    private function get_api_data( $date ) {

        // This can take long, so remove timeout
        set_time_limit(0);

        // Passing dates through strtotime to ensure leading 0's are added and generally well formed as api is very strict
        $start_date = date( 'Y-m-d', strtotime( $date ));
        $end_date = date( 'Y-m-d', strtotime( $date ));

        $data = array();

        // Crete query string from metrics array
        $metric_query = implode(',', $this->metrics);
        
        // Loop all profiles and get day data
        foreach (self::get_profiles() as $profile) {

            // Form id
            $id = "ga:{$profile['id']}";

            // Api resonse data
            $metrics = NULL;

            // Track number of api errors
            $api_errors = array();

            // Try tp get data from api and store to in db
            try {

                $metric = $this->service->data_ga->get(
                    $id, $start_date, $end_date, $metric_query,
                    array( 'dimensions' => 'ga:deviceCategory' )
                );
            
            // Exception is not a standard php exception, but a google_service exception hence the getMessage()
            } catch ( \Exception $e ) {

                // Add to errors
                $api_errors[] = $e->getMessage();

                // Write to db
                $this->log_api_error( $e->getMessage() );

            }

            // Store metrics in database if avaialble
            if ($metric) $this->store_metric( $metric );
        }

        // Return only errors if any
        return $api_errors;

    }

    /**
     * Calculates each date in between two dates
     * @param  string $date_start First date - included
     * @param  string $date_end   Last date - included
     * @return array             Array of dates between start and finish
     */
    private static function get_days ($date_start, $date_end){
    
        // Firstly, format the provided dates.
        // This function works best with YYYY-MM-DD
        // but other date formats will work thanks
        // to strtotime().
        $date_start = date("Y-m-d", strtotime($date_start));
        $date_end = date("Y-m-d", strtotime($date_end));

        // Start the variable off with the start date
        $days[] = $date_start;

        // Set a 'temp' variable, current_date, with
        // the start date - before beginning the loop
        $current_date = $date_start;

        // While the current date is less than the end date
        while($current_date < $date_end){

            // Add a day to the current date
            $current_date = date("Y-m-d", strtotime("+1 day", strtotime($current_date)));

            // Add this new day to the days array
            $days[] = $current_date;
        }

        // Once the loop has finished, return the
        // array of days.
        return $days;
    }

    /**
     * Get all profiles that has not been marked ignored
     * @return array Valid profiles
     */
    private static function get_profiles() {

        $profiles = ORM::for_table('profiles')
            ->where( 'ignored', false )
            ->find_array();

        return $profiles;
    }

    /**
     * Retreives a single day's data for all profiles
     * Skips ignored entries
     * @param  string $day Date of data to retreive. "Y-m-d"
     * @param  array  $ids Array of profile ids to retreive data for
     * @return array      Array of database results
     */
    private static function get_metric( $day ) {

        // Get valid profiles
        $profiles = self::get_profiles();

        // Sort profiles into array of ids only
        $profile_ids = array();
        foreach ($profiles as $profile) {
            $profile_ids[] = $profile['id'];
        }

        // Get metrics for all valid profiles
        $data = ORM::for_table('metrics')
            ->where( 'date', $day )
            ->where_in('profile_id', $profile_ids)
            ->find_array();

        return $data;
    }

    /**
     * Creates a monthly or daily entry on the database
     * @param  array $metrics API response metrics
     * @param  string $table  Table name to store metrics daily/monthly
     * @return array Stored data
     */
    private static function store_metric( $metric ) {

        // Sort data
        $date    = $metric['query']['start-date'];
        $profile = $metric['profileInfo'];
        $data    = $metric['totalsForAllResults'];

        // Create entry
        $row = ORM::for_table('metrics')->create();

        // Set profile data
        $row->profile_id = $profile['profileId'];
        $row->account_id = $profile['accountId'];

        // Set rows data
        $row->visits                   = $data['ga:visits'];
        $row->visitors                 = $data['ga:visitors'];
        $row->unique_visits            = $data['ga:newVisits'];
        $row->bounces                  = $data['ga:bounces'];
        $row->avg_views_per_visit      = $data['ga:pageviewsPerVisit'];
        $row->avg_time_on_site         = $data['ga:avgTimeOnSite'];
        $row->avg_page_load_time       = $data['ga:avgPageLoadTime'];
        $row->avg_server_response_time = $data['ga:avgServerResponseTime'];

        // Add metrics day
        $row->date = $date;

        // Store metrics
        $row->save();

        // Return stored data
        return $row->find_one()->as_array();
    }

    /**
     * Writes a google service exception to the database
     * @param  Google_Service exceptino $message Message returned from service exception
     */
    private static function log_api_error( $message ) {

        // Write API error
        $error = ORM::for_table('api_errors')->create();
        $error->error = $message;
        $error->save();
    }

}