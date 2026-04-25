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
