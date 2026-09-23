<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class SyncBatchMovementPricesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('batch_movements');
        Schema::dropIfExists('product_batches');

        Schema::create('product_batches', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->default(1);
            $table->unsignedBigInteger('product_id');
            $table->string('no_batch')->nullable();
            $table->dateTime('tanggal_pembelian')->nullable();
            $table->decimal('harga_satuan', 20, 2)->default(0);
            $table->integer('jumlah_awal')->default(0);
            $table->integer('jumlah_saat_ini')->default(0);
            $table->timestamps();
        });

        Schema::create('batch_movements', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->default(1);
            $table->unsignedBigInteger('batch_id');
            $table->unsignedBigInteger('stock_movement_id')->default(0);
            $table->dateTime('tanggal_perubahan')->nullable();
            $table->string('jenis_transaksi', 20)->nullable();
            $table->unsignedBigInteger('transaction_detail_id')->nullable();
            $table->integer('jumlah')->default(0);
            $table->decimal('harga_satuan', 20, 2)->default(0);
            $table->timestamps();
        });
    }

    private function jalankanSyncPrices(): int
    {
        $cmd = new \App\Console\Commands\SyncBatchMovementPrices;
        $cmd->setLaravel($this->app);
        $input = new ArrayInput([], $cmd->getDefinition());
        $buffered = new BufferedOutput;
        $cmd->setInput($input);
        $cmd->setOutput(new \Illuminate\Console\OutputStyle($input, $buffered));

        $m = new \ReflectionMethod($cmd, 'syncPrices');
        $m->setAccessible(true);

        return (int) $m->invoke($cmd);
    }

    private function buatBatch(float $harga): int
    {
        return DB::table('product_batches')->insertGetId([
            'business_id' => 1,
            'product_id' => 1,
            'no_batch' => 'B-'.uniqid(),
            'tanggal_pembelian' => now(),
            'harga_satuan' => $harga,
            'jumlah_awal' => 10,
            'jumlah_saat_ini' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function buatMovement(int $batchId, float $harga): int
    {
        return DB::table('batch_movements')->insertGetId([
            'business_id' => 1,
            'batch_id' => $batchId,
            'stock_movement_id' => 0,
            'tanggal_perubahan' => now(),
            'jenis_transaksi' => 'purchase',
            'jumlah' => 10,
            'harga_satuan' => $harga,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** Harga yang berbeda jauh dari batch ikut diperbaiki; hanya baris menyimpang yang tersentuh. */
    public function test_menyelaraskan_harga_satuan_dengan_batch(): void
    {
        $batchNormal = $this->buatBatch(12000);
        $batchInflated = $this->buatBatch(9000);

        $movementNormal = $this->buatMovement($batchNormal, 12000);   // sudah sinkron -> tidak disentuh
        $movementInflated = $this->buatMovement($batchInflated, 155000000000); // terinflasi -> diperbaiki

        $affected = $this->jalankanSyncPrices();

        $this->assertSame(1, $affected, 'Hanya 1 baris yang menyimpang');
        $this->assertEquals(9000, (float) DB::table('batch_movements')->where('id', $movementInflated)->value('harga_satuan'));
        $this->assertEquals(12000, (float) DB::table('batch_movements')->where('id', $movementNormal)->value('harga_satuan'));
    }

    /** Selisih di bawah toleransi 0.01 tidak diperbarui. */
    public function test_mengabaikan_selisih_dalam_toleransi(): void
    {
        $batch = $this->buatBatch(10000);
        $movement = $this->buatMovement($batch, 10000.005);

        $affected = $this->jalankanSyncPrices();

        $this->assertSame(0, $affected);
        $this->assertEquals(10000.005, (float) DB::table('batch_movements')->where('id', $movement)->value('harga_satuan'));
    }

    /** Idempoten: menjalankan ulang tidak mengubah apa pun. */
    public function test_idempoten(): void
    {
        $batch = $this->buatBatch(7500);
        $this->buatMovement($batch, 9999999);

        $this->assertSame(1, $this->jalankanSyncPrices());
        $this->assertSame(0, $this->jalankanSyncPrices());
    }
}
