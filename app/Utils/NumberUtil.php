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

        if (is_numeric($value)) {
            return (float) $value;
        }

        $str = trim((string) $value);

        // If it contains a comma, it's definitely Indonesian format (dot=thousands, comma=decimal)
        if (strpos($str, ',') !== false) {
            $clean = str_replace('.', '', $str);
            $clean = str_replace(',', '.', $clean);

            return (float) $clean;
        }

        // If it contains a dot:
        if (strpos($str, '.') !== false) {
            $lastDotIdx = strrpos($str, '.');
            $remainingLength = strlen($str) - $lastDotIdx - 1;

            // In Indonesian, thousands dots are ALWAYS followed by 3 digits.
            if ($remainingLength !== 3) {
                // Could be English decimal (e.g. 1.2) or just a dot not following thousand rules
                return (float) $str;
            }

            // If there's another dot, it's thousands
            if (strpos($str, '.') !== $lastDotIdx) {
                return (float) str_replace('.', '', $str);
            }

            // Ambiguous 1.250 -> Treat as 1250 for Indonesian apps
            return (float) str_replace('.', '', $str);
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
