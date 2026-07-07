<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('tanggal_diterima')->nullable()->after('tagihan');
            $table->string('metode_pembayaran')->nullable()->after('saldo');
            $table->text('keterangan')->nullable()->after('metode_pembayaran');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['tanggal_diterima', 'metode_pembayaran', 'keterangan']);
        });
    }
};