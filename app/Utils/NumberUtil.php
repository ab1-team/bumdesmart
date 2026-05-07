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
     * Parse an Indonesian formatted number string into a float.
     * Removes dots (thousands separator) and replaces commas with dots (decimal separator).
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
        
        // Remove common currency symbols and spaces
        $str = str_replace(['Rp', 'rp', ' ', 'IDR'], '', $str);

        // If it contains a comma, it's definitely Indonesian format or has decimals
        if (strpos($str, ',') !== false) {
            $clean = str_replace('.', '', $str); // Remove thousands
            $clean = str_replace(',', '.', $clean); // Change decimal to dot

            return (float) $clean;
        }

        // If it only contains a dot:
        if (strpos($str, '.') !== false) {
            $lastDotIdx = strrpos($str, '.');
            $remainingLength = strlen($str) - $lastDotIdx - 1;

            // In Indonesian, thousands dots are followed by exactly 3 digits.
            // If there's another dot, it's definitely thousands (e.g. 1.000.000)
            if ($remainingLength === 3 || strpos($str, '.') !== $lastDotIdx) {
                return (float) str_replace('.', '', $str);
            }

            // Otherwise treat as decimal (e.g. 1.5)
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
