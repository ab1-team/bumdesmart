<?php

namespace App\Utils;

class NumberUtil
{
    /**
     * Format a number with maximum decimal places, removing unnecessary trailing zeros.
     * Use Indonesian format (dot for thousands, comma for decimals).
     *
     * @param mixed $value
     * @param int $maxDecimals
     * @return string
     */
    public static function format($value, $maxDecimals = 2)
    {
        if ($value === null || $value === '') {
            return '';
        }

        $value = (float) $value;
        
        // Round to max decimals
        $rounded = round($value, $maxDecimals);
        
        // Format with separators
        $formatted = number_format($rounded, $maxDecimals, ',', '.');
        
        // Remove trailing zeros and possible decimal separator if not needed
        if (strpos($formatted, ',') !== false) {
            $formatted = rtrim(rtrim($formatted, '0'), ',');
        }
        
        return $formatted;
    }
}
