<?php

namespace GA;

use \ORM;
use \__;

/**
* Main api data handler class
*/
class API extends Data {

    /**
     * Get data for a date or between 2 dates
     * Sorted into group -> date -> profile
     * @return array Data array
     */
    public function get_data_as_groups( $from, $to = NULL, $mobile = false ) {

        // Get raw data from data class
        $data_raw = $this->get_data($from, $to);

        // Sort data into new array
        $data = array();

        // Add errors
        $data['errors'] = $data_raw['errors'];

        // Get profiles
        $profile_list = self::get_profiles();

        // Loop data and sort into ' day => profile => data '
        foreach ($data_raw['data'] as $day => $profiles) {

            foreach ($profiles as $metrics) {

                // Get profile id
                $profile_id = $metrics['profile_id'];

                // Get profile name
                $name = $profile_list[$profile_id]['name'];               

                // Get group name
                $group = self::get_group_name( $profile_list[$profile_id]['group'] );

                // Check profile has a group
                if( ! $group ) $group = 'Misc';

                // Store data to profile name
                $data['data'][$group][$day][$name] = $metrics;

                // Also set profile url
                $data['data'][$group][$day][$name]['url'] = $profile_list[$profile_id]['website_url'];
            }
        }

        // Return sorted data
        return $data;        
    }


    /**
     * Get a range of data ( from - to ) as a single result with totals and averages
     * Heavily dependand on the structure of the db, so any db name changes etc. will cause it to fail badly.
     * @param  string  $from   Dates Y-m-d
     * @param  string  $to     Dates Y-m-d
     * @param  boolean $mobile Whether to include mobile data
     * @return array           Final array of totals data
     */
    public function get_data_as_totals( $from, $to, $mobile = false ) {

        // New array for sorted totals
        $new_data = array();

        // Get data
        $data = $this->get_data( $from, $to, $mobile );

        // Check number of days to calculate avgs
        $avg_del = count($data['data']);

        // Now sort data in totals

        // Loop dates
        foreach ($data['data'] as $date => $profiles) {

            // Loop each profile in date
            foreach ($profiles as $profile_id => $metrics) {

                // Loop each metric in profile
                foreach ($metrics as $metric => $value) {

                    // If value is an array, perform another iteration as this is a mobile field with it's own metrics
                    if ( is_array($value) ) {

                        // Add mobile values
                        foreach ($value as $m_metric => $m_value) {
                            $new_data[$profile_id][$metric][$m_metric] += $m_value;
                        }

                    // Else if value isn falsy, simply add as it's not a mobile field, but an absolute metrics
                    } else if ( $value ) {
                        $new_data[$profile_id][$metric] += $value;
                    }
                }
            }
        }

        // Calculate avgs in new_data array

        // Loop profiles. Dates no longer exist
        foreach ($new_data as $profile_id => $metrics) {

            // Loop each profiles metrics
            foreach ($metrics as $metric => $value) {

                // Check if metric is of an average kind
                if (in_array($metric, array('avg_views_per_visit', 'avg_time_on_site'))) {
                    
                    // Set to rounded avg by deviding by the total number of days originally requested
                    $avg = round($value / $avg_del, 1);
                    $new_data[$profile_id][$metric] = $avg;
                
                // If another array is found then it's a mobile value and needs to be iterated again
                } else if ( is_array($value) ) {
                    
                    // Same iteration as 2 levels up
                    foreach ($value as $m_metric => $m_value) {
                        
                        // Again check if mobile metric is of average type
                        if (in_array($m_metric, array('avg_views_per_visit', 'avg_time_on_site'))) {

                            // Set to rounded avg by deviding by the total number of days originally requested
                            $avg = round($m_value / $avg_del, 1);
                            $new_data[$profile_id][$metric][$m_metric] = $avg;
                        }
                    }
                }
            }
        }

        // All done! Return totals array
        return $new_data;
    }



    /**
     * Returns the highest visitors ever for the given profile
     * @param  int $profile_id
     * @param  string $prior_to Date from which to exlude y-m-d
     * @return int Number of visitors
     */
    public function get_record_visitors($profile_id, $prior_to = NULL) {
        
        // Use date passed in or dfault to future date so all data is included
        $date = $prior_to ? $prior_to : date('Y-m-d', strtotime('tomorrow'));

        $max = ORM::for_table('analytics_total')
            ->where('profile_id', $profile_id)
            ->where_lt('date', $date)
            ->max('visitors');

        return $max;
    }

    /**
     * Return the total page views for a given date
     * @param  string $date Date for which to return data
     * @return int          Total page views
     */
    public function get_total_page_views($date) {
        
        // Get total visits
        $visits = $this->get_total_visits($date);

        // If no visits simply return 0
        if ( ! $visits ) return 0;

        // Get avg page views
        // Performing raw query as idiorm avg() returns a int which makes figures inaccurate
        $totals = ORM::for_table('analytics_total')
            ->raw_query('select AVG(`avg_views_per_visit`) from `analytics_total` where `date` = :date', array( 'date' => $date ))
            ->find_one()->as_array();

        // Multiply avg visit by total visitors to get total page views
        $total = $visits * $totals['AVG(`avg_views_per_visit`)'];

        // Return rounded int total
        return (int)$total;
    }

    /**
     * Return the total visitors for a given date
     * @param  string $date Date for which to return data
     * @return int          Total visitors
     */
    public function get_total_visitors($date) {
        
        $visitors = ORM::for_table('analytics_total')
            ->where('date', $date )
            ->sum('visitors');

        // Multiply avg visit by total visitors to get total page views
        return $visitors;
    }   

    /**
     * Return the total visits for a given date
     * @param  string $date Date for which to return data
     * @return int          Total visits
     */
    public function get_total_visits($date, $group = NULL) {
        
        $visits = ORM::for_table('analytics_total')
            ->where('date', $date )
            ->sum('visits');

        // Return int total
        return $visits;
    }

    /**
     * Return a profile name provided with the profile id
     * @param  int $id Profile id
     * @return string  Profile name
     */
    public static function get_profile_name($id) {

        // Get profile from db
        $name = ORM::for_table('profiles')
            ->where('id', $id)
            ->find_one();

        // Return name value
        return $name ? $name->name : NULL;
    }

    /**
     * Return a group name provided with the group id
     * @param  int $id Group id
     * @return string  Group name
     */
    public static function get_group_name($id) {

        // Get group from db
        $name = ORM::for_table('groups')
            ->where('id', $id)
            ->find_one();

        // Return name value
        return $name ? $name->name : NULL;
    }

    /**
     * Return the toatl `visits` of a profile.
     * Duplicate/unused profiles will have 0 vists, so can be excluded
     * @param  int $profile Profile id
     * @return int          Total visits.
     */
    public function get_total_profile_visits($profile)
    {
        $visits = ORM::for_table('analytics_total')
            ->where('profile_id', $profile)
            ->sum('visits');

        return $visits;
    }

    /**
     * Updates profile groups
     * @param  array $profile_groups POST array of profile_id => group_id
     */
    public function update_groups( $profile_groups ) {
        
        foreach ($profile_groups as $profile_id => $group_id) {

            // Convert group_id to int or NULL
            $group_id = $group_id ? (int)$group_id : NULL;
            
            // Get profile
            $profile = ORM::for_table('profiles')
                ->where('id', $profile_id)
                ->find_one();

            // Check profile group is updated
            if ( $profile->group != $group_id) {
                
                $profile->group = $group_id;
                $profile->save();
            }
        }
    }

    /**
     * Creates new group in database
     * @param  name $name Group name
     * @return bool       Bool whether group was created or already exists
     */
    public function create_group($name) {

        // Check group exists
        $group = ORM::for_table('groups')
            ->where('name', $name)
            ->find_one();

        // Send message if group exists
        if ($group) return false;

        // Else create group
        $group = ORM::for_table('groups')->create();
        $group->name = $name;
        $group->save();

        // Send success message
        if ($group) return true;
    }

    /**
     * Deletes group and unsets all associatted profiles
     * @param  int $id Group id
     * @return bool    Group delete status
     */
    public function delete_group($id) {
        
        // Check group exists
        $group = ORM::for_table('groups')
            ->where('id', $id)
            ->find_one();

        // Chekc group exists then delete and return true
        if ($group) {
            
            $group->delete();
            return true;
        }

        // If no group found simply return false
        return false;
    }

    /**
     * Update profiles' ignored value
     * @param  array $profiles_ignored Array of posts to be ignored only. $id => truthy
     */
    public function update_ignored($profiles_ignored) {
        
        // Get all profiles
        $profiles = $this->get_profiles(true);

        // Loop profiles
        foreach ($profiles as $profile) {
            
            $profile = ORM::for_table('profiles')
                ->where('id', $profile['id'])
                ->find_one();

            // Check if profile is set to be ignored
            $is_ignored = isset($profiles_ignored[$profile->id]);

            // Update if changed to be ignored
            if ( ! $profile->ignored && $is_ignored ) {
                
                $profile->ignored = 1;
                $profile->save();
            
            // Change if set to no longer be ignored
            } elseif ( $profile->ignored && ! $is_ignored ) {
                
                $profile->ignored = 0;
                $profile->save();
            }
        }

    }

    /**
     * Get all groups
     * @return array Database results
     */
    public function get_groups() {

        $groups_db = ORM::for_table('groups')->find_array();

        // Sort groups in id=>name array
        $groups = array();

        foreach ($groups_db as $group) {
            $groups[$group['id']] = $group['name'];
        }

        return $groups;
    }

}
