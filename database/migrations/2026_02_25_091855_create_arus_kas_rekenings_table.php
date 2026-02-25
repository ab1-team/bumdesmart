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
        Schema::create('arus_kas_rekenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arus_kas_id')->constrained('arus_kas')->cascadeOnDelete();
            $table->string('rekening_debit', 50)->nullable();
            $table->string('rekening_kredit', 50)->nullable();
            $table->timestamps();

            $table->index(['arus_kas_id', 'rekening_debit', 'rekening_kredit'], 'idx_rekening');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arus_kas_rekenings');
    }
};
