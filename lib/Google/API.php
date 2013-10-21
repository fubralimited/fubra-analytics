<?php

namespace GA;

use \ORM;
use \__;

/**
* Main api data handler class
*/
class API extends Data {

    /**
     * Get sorted array of analytics between (and including) two dates.
     * Sorted into date -> profile
     * @param  string $from First date (Y-m-d)
     * @param  string $to   Second optional date (Y-m-d)
     * @return array        Analytics data
     */
    public function get_as_dates( $from, $to = NULL ) {

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

                // Store data to profile name
                $data['data'][$day][$name] = $metrics;

                // Also set profile url
                $data['data'][$day][$name]['url'] = $profile_list[$profile_id]['website_url'];
            }
        }

        // Return sorted data
        return $data;
    }

    /**
     * Sorted into group -> date -> profile
     * @return [type] [description]
     */
    public function get_as_groups( $from, $to = NULL ) {

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
                if( ! $group ) $group = 'No Group';

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
    public function get_total_visits($profile)
    {
        
        $visits = ORM::for_table('analytics_total')
            ->raw_query('SELECT SUM(`visits`) FROM `analytics_total` WHERE `profile_id` = :id', array( 'id' => $profile ))
            ->find_one()->as_array();

        // Check if any visits was found
        $visits_total = $visits['SUM(`visits`)'] ? $visits['SUM(`visits`)'] : 0;

        return $visits_total;
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
