<?php

namespace App\Utils;

class NumberUtil
{
    /**
     * Format a nominal/currency number with 2 decimal places.
     * Use Indonesian format (dot for thousands, comma for decimals).
     *
     * @param mixed $value
     * @param int $maxDecimals
     * @param bool $trimZeros
     * @return string
     */
    public static function format($value, $maxDecimals = 2, $trimZeros = false)
    {
        if ($value === null || $value === '') {
            return '';
        }

        $value = (float) $value;
        
        // Round to max decimals
        $rounded = round($value, $maxDecimals);
        
        // Format with separators
        $formatted = number_format($rounded, $maxDecimals, ',', '.');
        
        // Remove trailing zeros and possible decimal separator if requested
        if ($trimZeros && strpos($formatted, ',') !== false) {
            $formatted = rtrim(rtrim($formatted, '0'), ',');
        }
        
        return $formatted;
    }

    /**
     * Format quantity / stock / count / percentage (non-nominal numbers).
     * If integer, displays without decimals (e.g. 5, 1.000).
     * If decimal, limits to max decimals without trailing zeros (e.g. 2,5).
     *
     * @param mixed $value
     * @param int $maxDecimals
     * @return string
     */
    public static function formatQty($value, $maxDecimals = 2)
    {
        if ($value === null || $value === '') {
            return '0';
        }

        $val = (float) $value;
        if (floor($val) == $val) {
            return number_format($val, 0, ',', '.');
        }

        $formatted = number_format(round($val, $maxDecimals), $maxDecimals, ',', '.');
        return rtrim(rtrim($formatted, '0'), ',');
    }

    /**
     * Parse a formatted number string into a float.
     * Supports both Indonesian standard (1.234,56) and US standard (1,234.56),
     * as well as standard unformatted floats (1234.56).
     *
     * @param mixed $value
     * @return float
     */
    public static function parse($value)
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $str = trim((string) $value);
        
        // Remove common currency symbols, NBSP and spaces
        $str = str_replace(['Rp', 'rp', ' ', 'IDR', "\xC2\xA0"], '', $str);

        // Case 1: Contains BOTH dot and comma (e.g. 1.234,56 or 1,234.56)
        if (strpos($str, '.') !== false && strpos($str, ',') !== false) {
            $lastDot = strrpos($str, '.');
            $lastComma = strrpos($str, ',');
            if ($lastComma > $lastDot) {
                // Indonesian format: 1.234,56 -> 1234.56
                $clean = str_replace('.', '', $str);
                $clean = str_replace(',', '.', $clean);
                return (float) $clean;
            } else {
                // US format: 1,234.56 -> 1234.56
                $clean = str_replace(',', '', $str);
                return (float) $clean;
            }
        }

        // Case 2: Contains ONLY comma (e.g. 1234,56 or 1,234,567)
        if (strpos($str, ',') !== false) {
            $parts = explode(',', $str);
            if (count($parts) > 2) {
                // Multiple commas -> thousands separator
                return (float) str_replace(',', '', $str);
            }
            // Single comma -> decimal in Indonesian format
            return (float) str_replace(',', '.', $str);
        }

        // Case 3: Contains ONLY dot (e.g. 1.234.567 or 1.234 or 1234.56)
        if (strpos($str, '.') !== false) {
            $lastDotIdx = strrpos($str, '.');
            $parts = explode('.', $str);
            if (count($parts) > 2) {
                // Multiple dots -> thousands separator in Indonesian format
                return (float) str_replace('.', '', $str);
            }
            $remainingLength = strlen($str) - $lastDotIdx - 1;
            // Indonesian thousands separator has exactly 3 digits after dot
            if ($remainingLength === 3) {
                return (float) str_replace('.', '', $str);
            }
            // Standard decimal float (e.g. 12.5, 1234.56)
            return (float) $str;
        }

        return (float) $str;
    }

    public static function terbilang($nilai)
    {
        $nilai = abs($nilai);
        $huruf = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
        $temp = "";
        if ($nilai < 12) {
            $temp = " " . $huruf[$nilai];
        } else if ($nilai < 20) {
            $temp = self::terbilang($nilai - 10) . " Belas";
        } else if ($nilai < 100) {
            $temp = self::terbilang($nilai / 10) . " Puluh" . self::terbilang($nilai % 10);
        } else if ($nilai < 200) {
            $temp = " Seratus" . self::terbilang($nilai - 100);
        } else if ($nilai < 1000) {
            $temp = self::terbilang($nilai / 100) . " Ratus" . self::terbilang($nilai % 100);
        } else if ($nilai < 2000) {
            $temp = " Seribu" . self::terbilang($nilai - 1000);
        } else if ($nilai < 1000000) {
            $temp = self::terbilang($nilai / 1000) . " Ribu" . self::terbilang($nilai % 1000);
        } else if ($nilai < 1000000000) {
            $temp = self::terbilang($nilai / 1000000) . " Juta" . self::terbilang($nilai % 1000000);
        } else if ($nilai < 1000000000000) {
            $temp = self::terbilang($nilai / 1000000000) . " Milyar" . self::terbilang(fmod($nilai, 1000000000));
        } else if ($nilai < 1000000000000000) {
            $temp = self::terbilang($nilai / 1000000000000) . " Trilyun" . self::terbilang(fmod($nilai, 1000000000000));
        }
        return trim($temp);
    }
}