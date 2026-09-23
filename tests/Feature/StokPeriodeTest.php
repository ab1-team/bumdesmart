<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use App\Utils\StokUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StokPeriodeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('products')) {
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
        }

        if (! Schema::hasTable('stock_movements')) {
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
        }

        if (! Schema::hasTable('product_batches')) {
            Schema::create('product_batches', function ($table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->default(1);
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('purchase_detail_id')->nullable();
                $table->string('no_batch')->nullable();
                $table->dateTime('tanggal_pembelian');
                $table->decimal('harga_satuan', 20, 2);
                $table->integer('jumlah_awal');
                $table->integer('jumlah_saat_ini')->default(0);
                $table->date('tanggal_kadaluarsa')->nullable();
                $table->string('status', 20)->default('ACTIVE');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('purchases')) {
            Schema::create('purchases', function ($table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->default(1);
                $table->date('tanggal_pembelian');
                $table->decimal('subtotal', 20, 2)->default(0);
                $table->decimal('total', 20, 2)->default(0);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('purchase_details')) {
            Schema::create('purchase_details', function ($table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('purchase_id');
                $table->unsignedBigInteger('product_id');
                $table->integer('jumlah')->default(0);
                $table->decimal('harga_satuan', 20, 2)->default(0);
                $table->decimal('subtotal', 20, 2)->default(0);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sales')) {
            Schema::create('sales', function ($table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->default(1);
                $table->dateTime('tanggal_transaksi');
                $table->decimal('subtotal', 20, 2)->default(0);
                $table->decimal('total', 20, 2)->default(0);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sale_details')) {
            Schema::create('sale_details', function ($table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('sale_id');
                $table->unsignedBigInteger('product_id');
                $table->integer('jumlah')->default(0);
                $table->decimal('harga_satuan', 20, 2)->default(0);
                $table->decimal('subtotal', 20, 2)->default(0);
                $table->decimal('hpp', 20, 2)->default(0);
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    private function buatProduk(int $stokAktual): Product
    {
        return Product::create([
            'business_id' => 1, 'category_id' => 1, 'brand_id' => 1, 'unit_id' => 1,
            'sku' => 'SKU-'.uniqid(), 'nama_produk' => 'Produk Uji',
            'harga_beli' => 10000, 'harga_jual' => 15000,
            'stok_aktual' => $stokAktual, 'biaya_rata_rata' => 10000, 'is_active' => 1,
        ]);
    }

    private function mutasi(Product $p, string $tanggal, int $jumlah, string $jenis, string $ref = 'purchase'): void
    {
        StockMovement::create([
            'business_id' => 1, 'product_id' => $p->id,
            'tanggal_perubahan_stok' => Carbon::parse($tanggal),
            'jenis_perubahan' => $jenis, 'jumlah_perubahan' => $jumlah,
            'reference_id' => 0, 'reference_type' => $ref,
        ]);
    }

    private function batch(Product $p, string $tanggal, int $jumlah, float $harga): ProductBatch
    {
        return ProductBatch::create([
            'business_id' => 1,
            'product_id' => $p->id,
            'purchase_detail_id' => null,
            'no_batch' => 'BATCH-'.uniqid(),
            'tanggal_pembelian' => Carbon::parse($tanggal),
            'harga_satuan' => $harga,
            'jumlah_awal' => $jumlah,
            'jumlah_saat_ini' => $jumlah,
            'tanggal_kadaluarsa' => null,
            'status' => 'ACTIVE',
        ]);
    }

    /** Buat purchase + detail untuk perhitungan Total Beli. */
    private function pembelian(Product $p, string $tanggal, int $jumlah, float $harga): void
    {
        $id = DB::table('purchases')->insertGetId([
            'business_id' => 1,
            'tanggal_pembelian' => Carbon::parse($tanggal)->toDateString(),
            'subtotal' => $jumlah * $harga,
            'total' => $jumlah * $harga,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('purchase_details')->insert([
            'purchase_id' => $id,
            'product_id' => $p->id,
            'jumlah' => $jumlah,
            'harga_satuan' => $harga,
            'subtotal' => $jumlah * $harga,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** Buat sale + detail untuk perhitungan Total Jual (memakai sd.hpp). */
    private function penjualan(Product $p, string $tanggal, int $jumlah, float $harga, ?float $hpp = null): void
    {
        $subtotal = $jumlah * $harga;
        // Total Jual dihitung dari SUM(sale_details.hpp), bukan subtotal (harga jual).
        // Default hpp = harga_beli master produk bila tidak diberikan.
        $hppTotal = $hpp ?? ($jumlah * (float) ($p->harga_beli ?: 0));

        $id = DB::table('sales')->insertGetId([
            'business_id' => 1,
            'tanggal_transaksi' => Carbon::parse($tanggal),
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('sale_details')->insert([
            'sale_id' => $id,
            'product_id' => $p->id,
            'jumlah' => $jumlah,
            'harga_satuan' => $harga,
            'subtotal' => $subtotal,
            'hpp' => $hppTotal,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** Spec: Awal=migrasi, Masuk=pembelian periode, Keluar=penjualan periode. */
    public function test_stok_awal_migrasi_bukan_masuk(): void
    {
        $p = $this->buatProduk(12); // master = migrasi 4 + beli 10 - jual 2
        // Snapshot migrasi 20 Aug +4 (bukan Masuk)
        $this->mutasi($p, '2026-08-20 09:24:17', 4, 'adjustment', 'migration');
        // Pembelian & penjualan September
        $this->mutasi($p, '2026-09-02 00:00:00', 10, 'purchase', 'purchase');
        $this->mutasi($p, '2026-09-04 16:57:45', -2, 'sale', 'sale');
        $this->pembelian($p, '2026-09-02', 10, 10000);
        $this->penjualan($p, '2026-09-04', 2, 15000);

        // Periode September
        $hasil = StokUtil::stokPeriode($p, new Carbon('2026-09-01'), new Carbon('2026-09-30'));
        $this->assertSame(4, $hasil['stok_awal'], 'Stok Awal = migrasi 4');
        $this->assertSame(10, $hasil['masuk'], 'Masuk = pembelian September saja, migrasi TIDAK dihitung Masuk');
        $this->assertSame(2, $hasil['keluar']);
        $this->assertSame(12, $hasil['stok_akhir']);
        $this->assertSame(12, $hasil['stok_awal'] + $hasil['masuk'] - $hasil['keluar']);

        // HPP = harga beli terakhir (batch terakhir s.d. September).
        $this->batch($p, '2026-09-02 10:00:00', 10, 11000);
        $hasil = StokUtil::stokPeriode($p, new Carbon('2026-09-01'), new Carbon('2026-09-30'));
        $this->assertSame(11000.0, $hasil['hpp'], 'HPP = harga batch terakhir s.d. periode');

        // Nilai Stok = Total Beli - Total Jual HPP (s.d. September).
        // Total Beli = migrasi 4*10000 + beli 10*10000 = 140000; Total Jual HPP = 2*10000 = 20000
        // => 120000
        $this->assertSame(120000.0, $hasil['nilai_stok']);

        // Periode Juli (sebelum semua): stok migrasi tetap tampil sebagai saldo
        $hasilJuli = StokUtil::stokPeriode($p, new Carbon('2026-07-01'), new Carbon('2026-07-31'));
        $this->assertSame(4, $hasilJuli['stok_awal'], 'Migrasi tetap Stok Awal meski periode sebelum tanggal migrasi');
        $this->assertSame(0, $hasilJuli['masuk']);
        $this->assertSame(0, $hasilJuli['keluar']);
        $this->assertSame(4, $hasilJuli['stok_akhir']);
        // HPP fallback ke harga_beli master (belum ada batch s.d. Juli).
        $this->assertSame(10000.0, $hasilJuli['hpp']);
        // Total Beli = migrasi 4*10000 = 40000; Total Jual = 0
        $this->assertSame(40000.0, $hasilJuli['nilai_stok']);
    }

    /** Kecuali migrasi, mutasi lama (kronologis) dihitung normal. */
    public function test_produk_tanpa_migrasi_kumulatif_normal(): void
    {
        $p = $this->buatProduk(9);
        $this->mutasi($p, '2026-07-31 00:00:00', 10, 'purchase', 'purchase');
        $this->mutasi($p, '2026-08-02 00:00:00', -3, 'sale', 'sale');
        $this->mutasi($p, '2026-09-05 00:00:00', -2, 'sale', 'sale');
        $this->pembelian($p, '2026-07-31', 10, 10000);
        $this->penjualan($p, '2026-08-02', 3, 15000);
        $this->penjualan($p, '2026-09-05', 2, 15000);

        $hasilAgustus = StokUtil::stokPeriode($p, new Carbon('2026-08-01'), new Carbon('2026-08-31'));
        $this->assertSame(10, $hasilAgustus['stok_awal']);
        $this->assertSame(0, $hasilAgustus['masuk']);
        $this->assertSame(3, $hasilAgustus['keluar']);
        $this->assertSame(7, $hasilAgustus['stok_akhir']);
        // Total Beli 100000 - Total Jual HPP 30000 = 70000
        $this->assertSame(70000.0, $hasilAgustus['nilai_stok']);

        $hasilJuli = StokUtil::stokPeriode($p, new Carbon('2026-07-01'), new Carbon('2026-07-31'));
        $this->assertSame(0, $hasilJuli['stok_awal']);
        $this->assertSame(10, $hasilJuli['masuk']);
        $this->assertSame(0, $hasilJuli['keluar']);
        $this->assertSame(10, $hasilJuli['stok_akhir']);
        // Total Beli 100000 - Total Jual 0 = 100000
        $this->assertSame(100000.0, $hasilJuli['nilai_stok']);
    }

    /** Riwayat impor backdate dihitung kronologis normal (tidak diabaikan). */
    public function test_riwayat_backdate_dihitung_kronologis(): void
    {
        $p = $this->buatProduk(9); // master = migrasi 6 - riwayat 1 + beli 10 - jual 4 - jual 2
        // Riwayat impor backdate (dibuat setelah migrasi, tanggal Agustus awal)
        $this->mutasi($p, '2026-08-10 00:00:00', -1, 'sale', 'sale');
        // Snapshot migrasi 20 Aug
        $this->mutasi($p, '2026-08-20 09:24:17', 6, 'adjustment', 'migration');
        // Pasca migrasi
        $this->mutasi($p, '2026-08-21 00:00:00', 10, 'purchase', 'purchase');
        $this->mutasi($p, '2026-08-23 00:00:00', -4, 'sale', 'sale');
        $this->mutasi($p, '2026-09-02 00:00:00', -2, 'sale', 'sale');
        $this->penjualan($p, '2026-08-10', 1, 15000);
        $this->pembelian($p, '2026-08-21', 10, 10000);
        $this->penjualan($p, '2026-08-23', 4, 15000);
        $this->penjualan($p, '2026-09-02', 2, 15000);

        // Agustus: awal = migrasi 6; keluar = backdate 1 + jual 4 = 5; masuk 10 -> akhir 11
        $hasil = StokUtil::stokPeriode($p, new Carbon('2026-08-01'), new Carbon('2026-08-31'));
        $this->assertSame(6, $hasil['stok_awal']);
        $this->assertSame(10, $hasil['masuk']);
        $this->assertSame(5, $hasil['keluar']);
        $this->assertSame(11, $hasil['stok_akhir']);
        // Total Beli = migrasi 6*10000 + beli 10*10000 = 160000; Total Jual HPP = 5*10000 = 50000
        $this->assertSame(110000.0, $hasil['nilai_stok']);

        // September: awal = 11, keluar 2 -> akhir 9 = master
        $hasilSep = StokUtil::stokPeriode($p, new Carbon('2026-09-01'), new Carbon('2026-09-30'));
        $this->assertSame(11, $hasilSep['stok_awal']);
        $this->assertSame(2, $hasilSep['keluar']);
        $this->assertSame(9, $hasilSep['stok_akhir']);
        $this->assertSame((int) $p->stok_aktual, $hasilSep['stok_akhir']);
        // Total Beli 160000 - Total Jual HPP (5+2)*10000 = 70000 => 90000
        $this->assertSame(90000.0, $hasilSep['nilai_stok']);
    }

    /** Produk tanpa movement sama sekali. */
    public function test_produk_tanpa_movement(): void
    {
        $p = $this->buatProduk(15);
        $hasil = StokUtil::stokPeriode($p, new Carbon('2026-09-01'), new Carbon('2026-09-30'));
        $this->assertSame(0, $hasil['stok_awal']);
        $this->assertSame(0, $hasil['masuk']);
        $this->assertSame(0, $hasil['keluar']);
        $this->assertSame(0, $hasil['stok_akhir']);
        // HPP fallback ke harga_beli master.
        $this->assertSame(10000.0, $hasil['hpp']);
        $this->assertSame(0.0, $hasil['nilai_stok']);
    }

    /** Boundary: mutasi tepat tanggal batas masuk periode. */
    public function test_batas_periode_inklusif(): void
    {
        $p = $this->buatProduk(5);
        $this->mutasi($p, '2026-09-01 00:00:00', 5, 'purchase', 'purchase');
        $this->mutasi($p, '2026-09-30 23:59:59', -2, 'sale', 'sale');
        $this->mutasi($p, '2026-10-01 00:00:00', -1, 'sale', 'sale');
        $this->pembelian($p, '2026-09-01', 5, 10000);
        $this->penjualan($p, '2026-09-30', 2, 15000);
        $this->penjualan($p, '2026-10-01', 1, 15000);

        $hasil = StokUtil::stokPeriode($p, new Carbon('2026-09-01'), new Carbon('2026-09-30'));
        $this->assertSame(0, $hasil['stok_awal']);
        $this->assertSame(5, $hasil['masuk']);
        $this->assertSame(2, $hasil['keluar']);
        $this->assertSame(3, $hasil['stok_akhir']);
        // Total Beli 50000 - Total Jual HPP 20000 = 30000
        $this->assertSame(30000.0, $hasil['nilai_stok']);

        // Penjualan 1 Oktober tidak boleh ikut periode September.
        $hasilOkt = StokUtil::stokPeriode($p, new Carbon('2026-10-01'), new Carbon('2026-10-31'));
        $this->assertSame(3, $hasilOkt['stok_awal']);
        $this->assertSame(1, $hasilOkt['keluar']);
        // Total Beli 50000 - Total Jual HPP (2+1)*10000 = 30000 => 20000
        $this->assertSame(20000.0, $hasilOkt['nilai_stok']);
    }

    /** HPP: harga satuan batch PALING TERAKHIR s.d. periode. */
    public function test_hpp_diambil_dari_batch_terakhir(): void
    {
        $p = $this->buatProduk(8);
        $p->update(['metode_biaya' => 'FIFO']);

        $this->batch($p, '2026-09-01 10:00:00', 10, 1000);
        $this->batch($p, '2026-09-05 10:00:00', 10, 2000);

        $hasil = StokUtil::stokPeriode(
            $p,
            Carbon::parse('2026-09-01')->startOfDay(),
            Carbon::parse('2026-09-30')->endOfDay()
        );

        $this->assertSame(2000.0, $hasil['hpp'], 'HPP = batch terbaru (2000)');
    }

    /** HPP: batch setelah endDate tidak boleh dipakai. */
    public function test_hpp_abaikan_batch_setelah_periode(): void
    {
        $p = $this->buatProduk(5);
        $this->batch($p, '2026-09-05 10:00:00', 5, 2000);
        $this->batch($p, '2026-10-05 10:00:00', 5, 5000);

        $hasil = StokUtil::stokPeriode(
            $p,
            Carbon::parse('2026-09-01')->startOfDay(),
            Carbon::parse('2026-09-30')->endOfDay()
        );

        $this->assertSame(2000.0, $hasil['hpp'], 'Batch Oktober diabaikan untuk periode September');
    }

    /** HPP: tanpa batch sama sekali -> fallback harga_beli master. */
    public function test_hpp_fallback_ke_harga_beli(): void
    {
        $p = $this->buatProduk(5);
        $this->mutasi($p, '2026-09-01 10:00:00', 5, 'purchase', 'purchase');

        $hasil = StokUtil::stokPeriode(
            $p,
            Carbon::parse('2026-09-01')->startOfDay(),
            Carbon::parse('2026-09-30')->endOfDay()
        );

        $this->assertSame(10000.0, $hasil['hpp']);
    }

    /** Nilai Stok = Total Beli - Total Jual s.d. periode; bisa 0 ketika habis. */
    public function test_nilai_stok_total_beli_minus_total_jual(): void
    {
        $p = $this->buatProduk(0);
        $this->mutasi($p, '2026-09-01 10:00:00', 10, 'purchase', 'purchase');
        $this->mutasi($p, '2026-09-10 10:00:00', -10, 'sale', 'sale');
        $this->pembelian($p, '2026-09-01', 10, 10000);
        $this->penjualan($p, '2026-09-10', 10, 15000);

        $hasil = StokUtil::stokPeriode(
            $p,
            Carbon::parse('2026-09-01')->startOfDay(),
            Carbon::parse('2026-09-30')->endOfDay()
        );

        $this->assertSame(0, $hasil['stok_akhir']);
        // Total Beli 100000 - Total Jual HPP 100000 = 0 (stok habis)
        $this->assertSame(0.0, $hasil['nilai_stok']);
    }

    /** Skenario migrasi: batch MIGRATION bertanggal setelah periode Juli; harga master berubah. */
    public function test_migrasi_batch_harga_saat_migrasi(): void
    {
        $p = $this->buatProduk(10);
        $p->update(['harga_beli' => 9000, 'biaya_rata_rata' => 9000]); // harga master saat ini berubah

        $this->mutasi($p, '2026-08-20 09:24:17', 10, 'adjustment', 'migration');

        ProductBatch::create([
            'business_id' => 1, 'product_id' => $p->id, 'purchase_detail_id' => null,
            'no_batch' => 'MIGRATION-20260820',
            'tanggal_pembelian' => Carbon::parse('2026-08-20 09:24:17'),
            'harga_satuan' => 11000, 'jumlah_awal' => 10, 'jumlah_saat_ini' => 10,
            'tanggal_kadaluarsa' => null, 'status' => 'ACTIVE',
        ]);

        $juli = StokUtil::stokPeriode($p, new Carbon('2026-07-01'), new Carbon('2026-07-31'));
        $this->assertSame(11000.0, $juli['hpp'], 'HPP Juli pakai harga batch migrasi');
        $this->assertSame(110000.0, $juli['nilai_stok'], 'Nilai stok Juli pakai harga batch migrasi');

        // Pasca migrasi (September) tanpa batch lain: tetap harga migrasi.
        $sep = StokUtil::stokPeriode($p, new Carbon('2026-09-01'), new Carbon('2026-09-30'));
        $this->assertSame(11000.0, $sep['hpp']);
        $this->assertSame(110000.0, $sep['nilai_stok']);
    }

    /**
     * Contoh manual user (produk APT00007):
     * Beli 1: 10 pcs @ 5895.35 = 58953.50
     * Beli 2: 10 pcs @ 5244.80 = 52448.00
     * Total Beli = 111401.50
     * Jual: 16 pcs dengan HPP total = 90422.30
     * Nilai Stok Akhir = 111401.50 - 90422.30 = 20979.20
     */
    public function test_hitung_manual_user_apt00007(): void
    {
        $p = $this->buatProduk(4); // 20 beli - 16 jual

        // Mutasi stok (masuk 20, keluar 16).
        $this->mutasi($p, '2026-09-01 09:00:00', 10, 'purchase', 'purchase');
        $this->mutasi($p, '2026-09-02 09:00:00', 10, 'purchase', 'purchase');
        $this->mutasi($p, '2026-09-10 10:00:00', -16, 'sale', 'sale');

        // Dua pembelian dengan harga berbeda.
        $this->pembelian($p, '2026-09-01', 10, 5895.35);
        $this->pembelian($p, '2026-09-02', 10, 5244.80);

        // Penjualan 16 pcs; Total Jual memakai HPP (bukan harga jual).
        $this->penjualan($p, '2026-09-10', 16, 7000, 90422.30);

        $hasil = StokUtil::stokPeriode($p, new Carbon('2026-09-01'), new Carbon('2026-09-30'));

        $this->assertSame(0, $hasil['stok_awal']);
        $this->assertSame(20, $hasil['masuk']);
        $this->assertSame(16, $hasil['keluar']);
        $this->assertSame(4, $hasil['stok_akhir']);

        // Total Beli = 10*5895.35 + 10*5244.80 = 111401.50
        $this->assertSame(111401.50, StokUtil::totalBeliSampai($p, new Carbon('2026-09-30')));
        // Total Jual (HPP) = 90422.30
        $this->assertSame(90422.30, StokUtil::totalJualSampai($p, new Carbon('2026-09-30')));
        // Nilai Stok Akhir = 111401.50 - 90422.30 = 20979.20
        $this->assertSame(20979.20, $hasil['nilai_stok']);
    }
}
