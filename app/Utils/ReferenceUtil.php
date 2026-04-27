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
     * @return string
     */
    public static function generate($modelClass, $prefix, $column = 'no_invoice', $dateColumn = 'tanggal_transaksi')
    {
        $businessId = auth()->user()->business_id;
        $date = date('Y/m');
        $today = date('Y-m-d');
        
        // Initial count based on today's records
        $count = $modelClass::where('business_id', $businessId)
            ->whereDate($dateColumn, $today)
            ->count() + 1;

        $number = $prefix . '/' . $date . '/' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
        
        // Robustness: Check if the generated number already exists and increment until unique.
        // This handles cases where transactions might have been deleted or concurrent saves.
        while ($modelClass::where('business_id', $businessId)->where($column, $number)->exists()) {
            $count++;
            $number = $prefix . '/' . $date . '/' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
        }

        return $number;
    }
}
