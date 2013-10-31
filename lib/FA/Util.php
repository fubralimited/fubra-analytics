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


}