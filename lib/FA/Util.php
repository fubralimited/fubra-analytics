<?php

namespace FA;

/**
 * Utility class
 */
class Util {

    /**
     * Calculates the percentage difference between 2 values
     * @param int $val_a Base value
     * @param int $val_b Changed to value
     */
    public static function percent_change($val_a, $val_b) {

        // Convert to ints
        $val_a = (int)$val_a;
        $val_b = (int)$val_b;

        // Check if zero change
        if ($val_a === $val_b) return 0;

        // Else check for infinite difference
        if ($val_a === 0) return 'INF';

        // Finally calculate a valid difference
        $difference = $val_b - $val_a;
        $change = ($difference / $val_a)*100;

        return round($change);
        
    }

    /**
     * Get hex color value for percentage change
     * @param  int  $val   Percentage change that occured
     * @param  int  $point Zero point to base calculation on
     * @param  bool $point Rever green & red
     * @return string   Hex string
     */
    public static function percentage_color($val, $point = 0, $reverse = false) {

        // Get absolute value
        $abs = abs($val - $point);

        // Base value
        $base = 100 - $point;

        // Set no more than base value
        if ($abs >= $base) $abs = $base;

        // Calculate percentage of 255
        $var_c = round( ($abs/$base) * 255 );

        // Get remaing value of 255
        $remain = round( (255 - $var_c) );

        // Check which side the value needs to be
        if ($reverse) {

            $rgb = ($val > $point) ? array( 255, $remain, $remain ) : array( $remain , 255, $remain );
        
        } else {

            $rgb = ($val < $point) ? array( 255, $remain, $remain ) : array( $remain , 255, $remain );
        }

        return self::rgb_to_hx($rgb);
    }


    /**
     * Converts rgb to a hex string
     * @param  [type] $r [description]
     * @param  [type] $g [description]
     * @param  [type] $b [description]
     * @return [type]    [description]
     */
    public static function rgb_to_hx($rgb) {

        $hex = "#";
        $hex .= str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

        return $hex;
    }


}