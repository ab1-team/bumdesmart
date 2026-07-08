<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill sale_details.hpp dari avg-per-unit ke total-per-baris.
     *
     * Sebelum fix commit 4237226, hpp disimpan sebagai avg per-unit
     * ($totalHpp / $qty). Perubahan kode agar hpp = $totalHpp (total per-baris).
     * Untuk data lama, kalikan hpp dengan jumlah.
     *
     * Profit sudah benar sebelum & sesudah fix (subtotal - totalHpp),
     * jadi tidak perlu diubah.
     *
     * payments (jurnal COGS) dan balances sudah benar karena kalkulasi
     * lama menggunakan $detail->hpp * $detail->jumlah.
     */
    public function up(): void
    {
        DB::statement('UPDATE sale_details SET hpp = hpp * jumlah WHERE jumlah IS NOT NULL AND jumlah != 0');
    }

    /**
     * Reverse: bagi hpp dengan jumlah untuk mengembalikan ke avg per-unit.
     */
    public function down(): void
    {
        DB::statement('UPDATE sale_details SET hpp = hpp / jumlah WHERE jumlah IS NOT NULL AND jumlah != 0');
    }
};