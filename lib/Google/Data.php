<?php

namespace GA;

use \ORM;
use \__;
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
        'ga:avgTimeOnSite'
    );

    /**
     * Creates a new service object from the authorised GA client
     */
    function __construct() {

        // Get config instance
        $this->config = new Config();

        // New options instance
        $this->options = new Options();

        // Get auth client
        $client = $this->authenticate();

        // Create service if authentication is ok
        if ( $client ) {
    
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
        $ga_auth = Auth::get_instance();

        // Check if api authenticated succesfully
        if ( ! $ga_auth->success ) return false;

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
            // Add some formatting to url. Check http isset else prepend it
            $entry->website_url = ( strpos($profile['websiteUrl'], 'http') === 0 ) ? $profile['websiteUrl'] : 'http://' . $profile['websiteUrl'];
            $entry->type = $profile['type'];

            // Save
            $entry->save();
        }
    }

    /**
     * Formats any given date into the correct Y-m-d
     * @param  mixed $date Date passed to api
     * @return string      Date converted ( or not ) into the Y-m-d format
     */
    private static function check_date($date) {

        // Check if date is in format Y-m-d
        if (preg_match('/\d{4}-\d{2}-\d{2}/', $date) ) return $date;

        // Else if date is a string format date into Y-m-d using strtotime
        if (is_string($date)) return date('Y-m-d', strtotime($date));
        
        // Finally assume timestamp, so simply format
        return date('Y-m-d', $date);
    }

    /**
     * Gets metrics for a given day or range
     * If data does not exist in database, do an api call
     * @param  string  $date_start First (or only) day to return
     * @param  string  $date_end   Last day data to return (past date)
     * @param  bool   $mobile Boolean whether to include mobile data
     * @return array               Metrics data
     */
    public function get_data( $date_start, $date_end = NULL, $mobile = false ) {

        // Format dates
        $date_start = self::check_date($date_start);
        $date_end = $date_end ? self::check_date($date_end) : NULL;

        // Get all profile ids (not ignored)
        $all_profiles = __::pluck( self::get_profiles(), 'id' );

        // Set days array ( if no end date simply add start to days array)
        $days = $date_end ? self::get_days( $date_start, $date_end ) : array($date_start);

        // Empty data array
        $res = array( 'data' => array(), 'errors' => array() );

        // Loop days and get data form either databse or api
        foreach ($days as $day) {

            // Get data from day from db
            $data = self::get_metric( $day, $mobile );

            // Get all profile ids with data
            $updated_profiles = __::pluck( $data, 'profile_id' );

            // Now check if data is missing any profiles
            $empty_profiles = __::difference( $all_profiles, $updated_profiles );

            // If any empty profiles exist, do an api call to get the data for those profiles
            if ( count($empty_profiles) ) {
                
                // Api returns the number of api errors if any
                $api_errors = $this->get_api_data($day, $empty_profiles);

                // Add errors if any
                if ( count($api_errors) ) $res['errors'][] = $api_errors;
                
                // Get data again
                $data = self::get_metric( $day, $mobile );
            }

            // Set data
            $res['data'][$day] = $data;
        }

        // Return results
        return $res;
    }

    /**
     * Requests data from API for specific url.
     * Data can be requested for a day only.
     * All sites are updated.
     * 
     * @param  string  $date   Date of data to retreive. Ideally with php format "Y-m-d"
     * @return int Return false if all api calls was succesfull or an int for the number of api errors.
     *             API errors can be retreived with get_api_errors()
     */
    private function get_api_data_for_airport_guide_paths( $date, $path ) {

        // Normalize path
        $path = trim($path);
        if ($path[0] != '/') $path = '/'.$path;

        // Set table
        $p = ORM::for_table('profiles');

        // Get ag's matches
        $p->where_like( 'name', '%airport-guide%' );

        // Filter ignored profiles unless set to be included
        $p->where( 'ignored', false );

        // Get profiles array
        $profiles = $p->select('id')->find_array();

        $profiles = __::pluck($profiles, 'id');

        // This can take long, so remove timeout
        set_time_limit(0);

        // Passing dates through strtotime to ensure leading 0's are added and generally well formed as api is very strict
        $start_date = date( 'Y-m-d', strtotime( $date ));
        $end_date = date( 'Y-m-d', strtotime( $date ));

        $data = array(
            'sessions' => 0,
            'bounces' => 0,
            'pageviews' => 0
            );

        $local_metrics = array(

            'ga:sessions',
            'ga:bounces',
            'ga:pageviews'
        );

        // Crete query string from metrics array
        $metric_query = implode(',', $local_metrics);

        // Loop all profiles and get day data
        foreach ( $profiles as $id) {

            // Api resonse data
            $metric = NULL;

            // Track number of api errors
            $api_errors = array();

            // Try tp get data from api and store to in db
            try {

                $metric = $this->service->data_ga->get(
                    "ga:{$id}", $start_date, $end_date, $metric_query,
                    array( 'filters' => 'ga:pagePath=='.$path )
                );

            // Exception is not a standard php exception, but a google_service exception hence the getMessage()
            } catch ( \Exception $e ) {

                // Add to errors
                $api_errors[] = $e->getMessage();

                // Write to db
                $this->log_api_error( $e->getMessage() );

            }

            // Add totals
            $data['pageviews'] += intval($metric['totalsForAllResults']['ga:pageviews']);
            $data['sessions'] += intval($metric['totalsForAllResults']['ga:sessions']);
            $data['bounces'] += intval($metric['totalsForAllResults']['ga:bounces']);
        }

        // Store metrics in database if avaialble

        // Create entry
        $row = ORM::for_table('airport_guides')->create();

        // Set rows data
        $row->sessions = $data['sessions'];
        $row->bounces  = $data['bounces'];
        $row->pageviews  = $data['pageviews'];
        $row->path     = $path;

        // Add metrics day
        $row->date = $date;

        // Store metrics
        $row->save();

        // Return only errors if any
        return $api_errors;
    }

    /**
     * Return all airport path data for a given date
     * @param  string $date   Date of data to retreive. "Y-m-d"
     * @return array          Array of database results
     */
    public function get_airport_path_data($date) {

        // Get paths
        $paths = explode( ',', $this->config->report['ag_paths'] );

        // Loop paths and get data
        foreach ($paths as $path) $this->get_api_data_for_airport_guide_paths( $date, $path );

        // Return collective db entry
        return ORM::for_table('airport_guides')->where('date', $date)->find_array();
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
    private function get_api_data( $date, $profiles ) {

        // This can take long, so remove timeout
        set_time_limit(0);

        // Passing dates through strtotime to ensure leading 0's are added and generally well formed as api is very strict
        $start_date = date( 'Y-m-d', strtotime( $date ));
        $end_date = date( 'Y-m-d', strtotime( $date ));

        $data = array();

        // Crete query string from metrics array
        $metric_query = implode(',', $this->metrics);
        
        // Loop all profiles and get day data
        foreach ( $profiles as $id) {

            // Form id
            $id = "ga:{$id}";

            // Api resonse data
            $metric = NULL;

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
    private static function get_days ($date_end, $date_start){
    
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
     * @param bool $ignored Bool to incude ignored profiles. Default false
     * @return array Valid profiles
     */
    public static function get_profiles($ignored = false) {

        // Set table
        $p = ORM::for_table('profiles');

        // Filter ignored profiles unless set to be included
        if ( ! $ignored ) $p->where( 'ignored', false );

        // Get profiles array
        $profiles_raw = $p->find_array();

        // Sort to have profile keys as profile id
        $profiles = array();
        foreach ($profiles_raw as $profile) {
            
            $profiles[$profile['id']] = $profile;
        }
        
        // Return sorted array
        return $profiles;

    }

    /**
     * Retreives a single day's data for all profiles
     * Skips ignored entries
     * @param  string $day    Date of data to retreive. "Y-m-d"
     * @return array          Array of database results
     */
    private static function get_metric( $date, $mobile ) {

        // Get valid profiles
        $profiles = self::get_profiles();

        // Sort profiles into array of ids only
        $profile_ids = array();
        foreach ($profiles as $profile) {
            $profile_ids[] = $profile['id'];
        }

        // Get metrics for all valid profiles
        $data = ORM::for_table('analytics_total')
            ->where( 'date', $date )
            ->where_in('profile_id', $profile_ids)
            ->find_array();

        // Check if mobile data needs adding to totals
        if ( $mobile && $data ) {

            // Get mobile data for each profile
            foreach ($data as $key => $metrics) {

                // Get mobile data
                $mobile_data = self::get_mobile_data($metrics['profile_id'], $date);

                // Merge with profile metrics
                $data[$key] = array_merge($metrics, $mobile_data);
            }
        }

        // Return metrics with indexes changed to profile ids
        return self::convert_index_to_ids($data);
    }

    /**
     * Set a data array to have prifile ids as keys instead of default indexes
     * @param  array $data get_data result
     * @return array       Sorted data array
     */
    private static function convert_index_to_ids($data) {

        // Newly sorted data
        $new_data = array();

        // Loop and change array indexes to profile ids
        foreach ($data as $key => $metrics) $new_data[$metrics['profile_id']] = $metrics;

        // Return sorted array
        return $new_data;
    }

    /**
     * Get tablet, phone and combined mobile data for a given day and profile
     * @param  int    $profile_id
     * @param  string $date       Date in format Y-m-d
     * @return array              Array of tablet, phone and combined data
     */
    private static function get_mobile_data($profile_id, $date) {

        // Create return array
        $data = array();

        // Get mobile (phone) data
        $data['mobile_phone'] = __::first( ORM::for_table('analytics_mobile')
            ->select_many('visits', 'visitors', 'unique_visits', 'bounces', 'avg_views_per_visit', 'avg_time_on_site')
            ->where('date', $date)
            ->where('profile_id', $profile_id)
            ->find_array() );

        // Get tablet data
        $data['mobile_tablet'] = __::first( ORM::for_table('analytics_tablet')
            ->select_many('visits', 'visitors', 'unique_visits', 'bounces', 'avg_views_per_visit', 'avg_time_on_site')
            ->where('date', $date)
            ->where('profile_id', $profile_id)
            ->find_array() );

        // Add a combined version of the data
        if ($data['mobile_phone'] && $data['mobile_tablet']) {
            
            // Add mobile and tablet to get totals
            $data['mobile_total']['visits']              = $data['mobile_phone']['visits'] + $data['mobile_tablet']['visits'];
            $data['mobile_total']['visitors']            = $data['mobile_phone']['visitors'] + $data['mobile_tablet']['visitors'];
            $data['mobile_total']['unique_visits']       = $data['mobile_phone']['unique_visits'] + $data['mobile_tablet']['unique_visits'];
            $data['mobile_total']['bounces']             = $data['mobile_phone']['bounces'] + $data['mobile_tablet']['bounces'];

            // Get avg totals
            $data['mobile_total']['avg_views_per_visit'] = round(($data['mobile_phone']['avg_views_per_visit'] + $data['mobile_tablet']['avg_views_per_visit'])/2 , 1);
            $data['mobile_total']['avg_time_on_site']    = round(($data['mobile_phone']['avg_time_on_site'] + $data['mobile_tablet']['avg_time_on_site'])/2 , 1);
        
        // Else simply set to the total to also be false
        } else { $data['mobile_total'] = false; }
        
        return $data;
    }

    /**
     * Creates a monthly or daily entry on the database
     * @param  array $metrics API response metrics
     * @return array Stored data
     */
    private function store_metric( $metric ) {

        // Sort data
        $date    = $metric['query']['start-date'];
        $profile = $metric['profileInfo'];
        $data    = $metric['totalsForAllResults'];

        // Create entry
        $row = ORM::for_table('analytics_total')->create();

        // Set profile id
        $row->profile_id = $profile['profileId'];

        // Set rows data
        $row->visits                   = $data['ga:visits'];
        $row->visitors                 = $data['ga:visitors'];
        $row->unique_visits            = $data['ga:newVisits'];
        $row->bounces                  = $data['ga:bounces'];
        $row->avg_views_per_visit      = round($data['ga:pageviewsPerVisit'], 2);
        $row->avg_time_on_site         = round($data['ga:avgTimeOnSite'], 2);

        // Add metrics day
        $row->date = $date;

        // Store metrics
        $row->save();

        // Store mobile and tablet data under same id as total data
        $this->store_mobile_metrics( $metric, $row->id() );

        // Return stored data
        return $row->find_one()->as_array();
    }

    /**
     * Creates both mobile and tablet entries for metrics from api
     * @param  array $metrics API response metrics
     * @param  int $id Associated `analytics_total` id
     */
    private function store_mobile_metrics($metric, $id) {

        // To get api data per device a fair bit of sorting has to happen
        // The total is broken down into a 'rows' field and is in order 'desktop', 'mobile', 'tablet'
        // The order of the metrics data for each platform is then in order which it was called,
        // so using the indexes from the $metrics ivar, makes this logical.
        // Tha said, the first index is the platform name, so this needs unsetting to line up the indexes.

        // Google sometimes doesn't return device data, or only returns partial data
        // In this case simply return and don't store any
        // Firstly make sure rows is set then make sure a 3rd index exists, meaning all 3 platform is being included
        if ( ! isset($metric['rows']) || ! isset($metric['rows'][2] ) ) return;

        // Firstly remove desktop data (index 0) from row as it's stored under totals
        array_shift($metric['rows']);

        // Now repeat inserts for both mobile and tablet which is now index 0, 1
        foreach (array('mobile', 'tablet') as $index => $platform) {

            // Sort data
            $date    = $metric['query']['start-date'];
            $profile = $metric['profileInfo'];

            // Get tablet or mobile data
            $data = $metric['rows'][$index];

            // Remove first key which simply identifies platform
            // Removing this brings array keys in line with the order the metrics where called
            // making identifying them easier
            array_shift($data);

            // Create db entries
            $row = ORM::for_table("analytics_{$platform}")->create();

            // Link id to totals row
            $row->id = $id;

            // Set profile id
            $row->profile_id = $profile['profileId'];

            // Get metrics call order by flipping metrics array
            // This means the ga:metric is the key and the value is the index which is was called
            $mco = array_flip($this->metrics);

            // Set rows data based on same order they where called in the metrics array
            $row->visits                   = $data[$mco['ga:visits']];
            $row->visitors                 = $data[$mco['ga:visitors']];
            $row->unique_visits            = $data[$mco['ga:newVisits']];
            $row->bounces                  = $data[$mco['ga:bounces']];
            $row->avg_views_per_visit      = round($data[$mco['ga:pageviewsPerVisit']], 2);
            $row->avg_time_on_site         = round($data[$mco['ga:avgTimeOnSite']], 2);

            // Add metrics day
            $row->date = $date;

            // Store metrics
            $row->save();

        }
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
