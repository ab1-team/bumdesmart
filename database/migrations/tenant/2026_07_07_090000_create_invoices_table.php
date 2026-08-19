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
        if (Schema::hasTable('invoices')) {
            return;
        }

        Schema::create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('business_id')->constrained('businesses');
            $table->unsignedBigInteger('no');
            $table->string('jenis_pembayaran', 100);
            $table->date('tanggal_invoice');
            $table->decimal('tagihan', 20, 2)->default(0);
            $table->decimal('saldo', 20, 2)->default(0);
            $table->enum('status', ['UNPAID', 'PAID', 'PARTIAL'])->default('UNPAID');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
