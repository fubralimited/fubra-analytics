<?php

namespace GA;

use \ORM;

/**
* Main api data handler class
*/
class API extends Data {

    public function get( $from, $to = NULL ) {

        // Get raw data from data class
        $data_raw = $this->get_data($from, $to);

        // Sort data into new arra
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

}
