<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockMovement;
use App\Utils\StokUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class BackfillStokAwalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['products', 'stock_movements', 'product_batches'] as $t) {
            Schema::dropIfExists($t);
        }

        Schema::create('products', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->default(1);
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->unsignedBigInteger('shelf_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->string('nama_produk')->nullable();
            $table->decimal('harga_beli', 20, 2)->default(0);
            $table->decimal('harga_jual', 20, 2)->default(0);
            $table->integer('stok_minimal')->default(0);
            $table->integer('stok_aktual')->default(0);
            $table->string('metode_biaya')->default('SYSTEM');
            $table->decimal('biaya_rata_rata', 20, 2)->default(0);
            $table->string('gambar')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('stock_movements', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->default(1);
            $table->unsignedBigInteger('product_id');
            $table->dateTime('tanggal_perubahan_stok');
            $table->string('jenis_perubahan', 20)->nullable();
            $table->integer('jumlah_perubahan');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type', 20)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('product_batches', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->default(1);
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('purchase_detail_id')->nullable();
            $table->string('no_batch');
            $table->dateTime('tanggal_pembelian');
            $table->decimal('harga_satuan', 20, 2);
            $table->integer('jumlah_awal');
            $table->integer('jumlah_saat_ini');
            $table->date('tanggal_kadaluarsa')->nullable();
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamps();
        });
    }

    private function jalankanBackfill(): string
    {
        $cmd = new \App\Console\Commands\BackfillStokAwal();
        $cmd->setLaravel($this->app);
        $input = new ArrayInput([], $cmd->getDefinition());
        $buffered = new BufferedOutput();
        $cmd->setInput($input);
        $cmd->setOutput(new \Illuminate\Console\OutputStyle($input, $buffered));

        $m = new \ReflectionMethod($cmd, 'processCurrentTenant');
        $m->setAccessible(true);
        $m->invoke($cmd);

        return $buffered->fetch();
    }

    /** Produk tanpa movement mendapat "Stok Awal Migrasi"; yang sudah punya & stok 0 dilewati. */
    public function test_backfill_membuat_stok_awal_dan_laporan_tidak_lagi_nol(): void
    {
        // A: stok 25 di master, TANPA movement -> harus dibackfill
        $a = Product::create([
            'business_id' => 1, 'category_id' => 1, 'brand_id' => 1, 'unit_id' => 1,
            'sku' => 'A-001', 'nama_produk' => 'Produk A',
            'harga_beli' => 10000, 'harga_jual' => 15000,
            'stok_aktual' => 25, 'biaya_rata_rata' => 0, 'is_active' => 1,
        ]);
        // B: sudah punya movement historis -> tidak disentuh
        $b = Product::create([
            'business_id' => 1, 'category_id' => 1, 'brand_id' => 1, 'unit_id' => 1,
            'sku' => 'B-002', 'nama_produk' => 'Produk B',
            'harga_beli' => 5000, 'harga_jual' => 8000,
            'stok_aktual' => 10, 'biaya_rata_rata' => 5000, 'is_active' => 1,
        ]);
        StockMovement::create([
            'business_id' => 1, 'product_id' => $b->id,
            'tanggal_perubahan_stok' => Carbon::now()->subDays(5),
            'jenis_perubahan' => 'sale', 'jumlah_perubahan' => -2,
        ]);
        // C: stok 0 tanpa movement -> dilewati
        Product::create([
            'business_id' => 1, 'category_id' => 1, 'brand_id' => 1, 'unit_id' => 1,
            'sku' => 'C-003', 'nama_produk' => 'Produk C',
            'harga_beli' => 2000, 'harga_jual' => 3000,
            'stok_aktual' => 0, 'biaya_rata_rata' => 0, 'is_active' => 1,
        ]);

        $out = $this->jalankanBackfill();

        // Movement dibuat hanya untuk A
        $this->assertSame(1, StockMovement::where('product_id', $a->id)->count());
        $this->assertSame(25, (int) StockMovement::where('product_id', $a->id)->value('jumlah_perubahan'));
        $this->assertSame(0, StockMovement::where('product_id', $b->id)->where('jenis_perubahan', 'adjustment')->count());
        $this->assertSame(0, StockMovement::where('product_id', $b->id)->where('reference_type', 'migration')->count());
        $this->assertSame(1, StockMovement::where('reference_type', 'migration')->count(), 'Hanya 1 movement migrasi baru (untuk produk A)');
        $this->assertSame(2, StockMovement::count(), 'Total: 1 buatan backfill (A) + 1 historis (B)');

        // Movement backfill selalu berada sebelum periode laporan mana pun
        $movement = StockMovement::where('product_id', $a->id)->first();
        $this->assertTrue(Carbon::parse($movement->tanggal_perubahan_stok)->lt(Carbon::now()->subYear()));

        // Batch migrasi ikut dibuat + HPP terisi dari harga beli
        $this->assertSame(1, DB::table('product_batches')->where('product_id', $a->id)->count());
        $this->assertEquals(10000, (float) $a->fresh()->biaya_rata_rata);

        // Laporan stok per periode: A tidak lagi 0
        $hasil = StokUtil::stokPeriode($a->fresh(), Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());
        $this->assertEquals(25, $hasil['stok_awal']);
        $this->assertEquals(0, $hasil['masuk']);
        $this->assertEquals(0, $hasil['keluar']);
        $this->assertEquals(25, $hasil['stok_akhir']);

        // Idempoten: jalan lagi tidak menambah movement
        $this->jalankanBackfill();
        $this->assertSame(1, StockMovement::where('product_id', $a->id)->count());
    }
}
