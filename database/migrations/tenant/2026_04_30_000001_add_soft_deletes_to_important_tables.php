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
        $tables = [
            'products',
            'suppliers',
            'customers',
            'users',
            'sales',
            'sale_details',
            'purchases',
            'purchase_details',
            'payments',
            'jurnals',
            'accounts'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'products',
            'suppliers',
            'customers',
            'users',
            'sales',
            'sale_details',
            'purchases',
            'purchase_details',
            'payments',
            'jurnals',
            'accounts'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
