<?php

namespace GA;

use \ORM;

/**
* Main api data handler class
*/
class API extends Data {

    /**
     * Get sorted array of analytics between (and including) two dates.
     * @param  string $from First date (Y-m-d)
     * @param  string $to   Second optional date (Y-m-d)
     * @return array        Analytics data
     */
    public function get( $from, $to = NULL ) {

        // Get raw data from data class
        $data_raw = $this->get_data($from, $to);

        // Sort data into new array
        $data = array();

        // Add errors
        $data['errors'] = $data_raw['errors'];

        // Loop data and sort into ' day => profile => data '
        foreach ($data_raw['data'] as $day => $profiles) {

            foreach ($profiles as $metrics) {

                // Get profile name
                $name = self::get_profile_name($metrics['profile_id']);

                // Store data to profile name
                $data['data'][$day][$name] = $metrics;
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
        return $name->name;
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
        return $name->name;
    }

    public function update_groups( $profile_groups ) {
        
        foreach ($profile_groups as $profile_id => $group_id) {

            // Convert group_id to int or NULL
            $group_id = $group_id ? (int)$group_id : NULL;
            
            $profile = ORM::for_table('profiles')
                ->where('id', $profile_id)
                ->find_one();

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
