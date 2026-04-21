<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('stok_minimal', 15, 4)->default(0)->change();
            $table->decimal('stok_aktual', 15, 4)->default(0)->change();
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->decimal('jumlah', 15, 4)->change();
        });

        Schema::table('purchase_details', function (Blueprint $table) {
            $table->decimal('jumlah', 15, 4)->change();
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->decimal('jumlah_stok', 15, 4)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('jumlah', 15, 4)->change();
        });

        Schema::table('batch_movements', function (Blueprint $table) {
            $table->decimal('jumlah', 15, 4)->change();
        });

        Schema::table('stock_opname_details', function (Blueprint $table) {
            $table->decimal('stok_fisik', 15, 4)->change();
            $table->decimal('selisih', 15, 4)->change();
        });

        Schema::table('stock_adjustment_details', function (Blueprint $table) {
            $table->decimal('jumlah', 15, 4)->change();
        });

        Schema::table('sales_return_details', function (Blueprint $table) {
            $table->decimal('jumlah', 15, 4)->change();
        });

        Schema::table('purchases_return_details', function (Blueprint $table) {
            $table->decimal('jumlah', 15, 4)->change();
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->decimal('jumlah', 15, 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // To reverse, we would change back to integer, but let's keep it simple for now
    }
};
