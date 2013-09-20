<?php

namespace FA;

// Add dependancies
use \ORM;

/**
 * Simple static class for managing options
 */
class Options {

    /**
     * Updates or creates option in options table
     * @param string $key Option name
     * @param string $val Option value
     */
    public static function set($key, $val) {

        // Get option
        $option = ORM::for_table('options')->where('key', $key)->find_one();

        // If nothing is returned create record
        if ( !$option ) {

            $option = ORM::for_table('options')->create();
            $option->key = $key;
        }

        // Set value
        $option->value = $val;

        // Store
        return $option->save();
    }

    /**
     * Return option from options table
     * @param string $key Option name
     * @return string Option value or false if not found
     */
    public static function get($key) {

        // Return option or false
        return ORM::for_table('options')->where('key', $key)->find_one()->value;
    }
    
    /**
     * Deletes option from options table
     * @param string $key Option name
     * @return BOOL Whether option was deleted
     */
    public static function delete($key) {

        // Get option & delete if set
        $option = ORM::for_table('options')->where('key', $key)->find_one();
        
        return $option ? $option->delete() : false;
    }

    /**
     * Return time option was last modified
     * @param string $key Option name
     * @return datetime Last modified time or false if not found
     */
    public static function modified($key) {

        // Get option time last modified
        return ORM::for_table('options')->where('key', $key)->find_one()->modified;
    }

}