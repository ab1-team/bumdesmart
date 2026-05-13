<?php

namespace App\Utils;

class ReferenceUtil
{
    /**
     * Generate a unique sequential reference number.
     * Format: PREFIX/YYYY/MM/0001
     *
     * @param string $modelClass The model class (e.g., Sale::class)
     * @param string $prefix The prefix (e.g., 'INV' or 'PO')
     * @param string $column The database column for the reference number
     * @param string $dateColumn The database column for the transaction date
     * @param string|null $dateValue The specific date to base the sequence on
     * @return string
     */
    public static function generate($modelClass, $prefix, $column = 'no_invoice', $dateColumn = 'tanggal_transaksi', $dateValue = null)
    {
        $businessId = auth()->user()->business_id;
        $timestamp = $dateValue ? strtotime($dateValue) : time();
        
        $yearMonth = date('Y/m', $timestamp);
        $month = date('m', $timestamp);
        $year = date('Y', $timestamp);
        
        // Count records in the same month for a monthly sequence (as implied by YYYY/MM format)
        $count = $modelClass::where('business_id', $businessId)
            ->whereYear($dateColumn, $year)
            ->whereMonth($dateColumn, $month)
            ->count() + 1;

        $number = $prefix . '/' . $yearMonth . '/' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
        
        // Robustness: Check if the generated number already exists and increment until unique.
        // This check MUST be global (no business_id scoping) because the database unique index is global.
        while ($modelClass::where($column, $number)->exists()) {
            $count++;
            $number = $prefix . '/' . $yearMonth . '/' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
        }

        return $number;
    }
}
