<?php

namespace App\Utils;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StokUtil
{
    /**
     * Hitung posisi stok satu produk untuk periode [startDate, endDate].
     *
     * Aturan laporan (spec user):
     * - Stok Awal  = Stok Awal Migrasi (saldo awal saat import/migrasi) + mutasi sistem sebelum periode.
     * - Masuk      = mutasi positif dalam periode (pembelian, retur penjualan, penyesuaian naik).
     * - Keluar     = mutasi negatif dalam periode (penjualan, retur pembelian, penyesuaian turun).
     * - Stok Akhir = Stok Awal + Masuk - Keluar.
     *
     * HPP:
     * - HPP = harga satuan pembelian/batch PALING TERAKHIR s.d. $endDate.
     * - Fallback ke products.harga_beli (lalu biaya_rata_rata) jika tidak ada batch.
     *
     * Nilai Stok:
     * - Nilai Stok = Total Beli - Total Jual (s.d. periode yang dipilih).
     * - Total Beli  = (stok awal migrasi * harga_beli) + SUM(purchase_details.subtotal <= endDate).
     * - Total Jual  = SUM(sale_details.hpp <= endDate).
     *
     * @return array{stok_awal: float, masuk: float, keluar: float, stok_akhir: float, hpp: float, nilai_stok: float}
     */
    public static function stokPeriode(Product $product, Carbon $startDate, Carbon $endDate): array
    {
        $mulai = $startDate->copy()->startOfDay();
        $selesai = $endDate->copy()->endOfDay();

        $movements = $product->stockMovements()
            ->orderBy('tanggal_perubahan_stok')
            ->get(['jumlah_perubahan', 'tanggal_perubahan_stok', 'reference_type', 'catatan']);

        $stokMigrasi = 0.0;

        foreach ($movements as $m) {
            if (self::isMigration($m)) {
                // Presisi sesuai database (decimal 15,2). Tidak dilakukan pembulatan ke int.
                $stokMigrasi += (float) $m->jumlah_perubahan;
            }
        }

        $masuk = 0.0;
        $keluar = 0.0;
        $netSebelumPeriode = 0.0;

        foreach ($movements as $m) {
            if (self::isMigration($m)) {
                continue; // migrasi = Stok Awal, bukan mutasi Masuk/Keluar
            }

            $tanggal = Carbon::parse($m->tanggal_perubahan_stok);
            // Presisi sesuai database (decimal 15,2). Tidak dilakukan pembulatan ke int.
            $jumlah = (float) $m->jumlah_perubahan;

            if ($tanggal->lt($mulai)) {
                $netSebelumPeriode += $jumlah;
            } elseif ($tanggal->lte($selesai)) {
                if ($jumlah > 0) {
                    $masuk += $jumlah;
                } else {
                    $keluar += abs($jumlah);
                }
            }
        }

        $stokAwal = $stokMigrasi + $netSebelumPeriode;
        $stokAkhir = $stokAwal + $masuk - $keluar;

        $hppTerakhir = self::hppTerakhir($product, $endDate, $stokMigrasi);

        $totalBeli = self::totalBeliSampai($product, $endDate, $stokMigrasi);
        $totalJual = self::totalJualSampai($product, $endDate);

        $nilaiStok = round($totalBeli - $totalJual, 2);

        return [
            'stok_awal' => $stokAwal,
            'masuk' => $masuk,
            'keluar' => $keluar,
            'stok_akhir' => $stokAkhir,
            'hpp' => $hppTerakhir,
            'nilai_stok' => $nilaiStok,
        ];
    }

    /**
     * Versi BULK dari stokPeriode untuk banyak produk sekaligus (menghindari N+1).
     *
     * Menjalankan 5 query agregat (WHERE IN product_id) untuk seluruh collection,
     * lalu memetakan hasilnya ke setiap produk tanpa query per produk.
     *
     * @param  \Illuminate\Support\Collection  $products
     * @return \Illuminate\Support\Collection
     */
    public static function stokPeriodeBulk($products, Carbon $startDate, Carbon $endDate)
    {
        $productIds = $products->pluck('id')->all();

        if (empty($productIds)) {
            return $products;
        }

        $mulai = $startDate->copy()->startOfDay();
        $selesai = $endDate->copy()->endOfDay();

        // a. Movements
        $movementsGroup = StockMovement::whereIn('product_id', $productIds)
            ->orderBy('tanggal_perubahan_stok')
            ->get(['product_id', 'jumlah_perubahan', 'tanggal_perubahan_stok', 'reference_type', 'catatan'])
            ->groupBy('product_id');

        // b. Latest Batches s.d. endDate
        $latestBatchMap = ProductBatch::whereIn('product_id', $productIds)
            ->where('tanggal_pembelian', '<=', $endDate->copy()->endOfDay())
            ->orderBy('tanggal_pembelian', 'desc')
            ->orderBy('id', 'desc')
            ->get(['product_id', 'harga_satuan'])
            ->unique('product_id')
            ->pluck('harga_satuan', 'product_id');

        // c. Migration Batches
        $migrasiBatchMap = ProductBatch::whereIn('product_id', $productIds)
            ->where(function ($q) {
                $q->where('no_batch', 'like', '%MIGRATION%')
                    ->orWhere('no_batch', 'like', '%INIT%');
            })
            ->orderBy('tanggal_pembelian', 'desc')
            ->orderBy('id', 'desc')
            ->get(['product_id', 'harga_satuan'])
            ->unique('product_id')
            ->pluck('harga_satuan', 'product_id');

        // d. Total Beli s.d. endDate
        $totalBeliMap = DB::table('purchase_details as pd')
            ->join('purchases as p', 'p.id', '=', 'pd.purchase_id')
            ->whereIn('pd.product_id', $productIds)
            ->where('p.tanggal_pembelian', '<=', $endDate->copy()->endOfDay())
            ->whereNull('pd.deleted_at')
            ->whereNull('p.deleted_at')
            ->groupBy('pd.product_id')
            ->select('pd.product_id', DB::raw('SUM(pd.subtotal) as total'))
            ->pluck('total', 'product_id');

        // e. Total Jual s.d. endDate
        $totalJualMap = DB::table('sale_details as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->whereIn('sd.product_id', $productIds)
            ->where('s.tanggal_transaksi', '<=', $endDate->copy()->endOfDay())
            ->whereNull('sd.deleted_at')
            ->whereNull('s.deleted_at')
            ->groupBy('sd.product_id')
            ->select('sd.product_id', DB::raw('SUM(sd.hpp) as total'))
            ->pluck('total', 'product_id');

        foreach ($products as $p) {
            $movements = $movementsGroup->get($p->id, collect());

            $stokMigrasi = 0.0;
            foreach ($movements as $m) {
                if (self::isMigration($m)) {
                    // Presisi sesuai database (decimal 15,2). Tidak dilakukan pembulatan ke int.
                    $stokMigrasi += (float) $m->jumlah_perubahan;
                }
            }

            $masuk = 0.0;
            $keluar = 0.0;
            $netSebelumPeriode = 0.0;

            foreach ($movements as $m) {
                if (self::isMigration($m)) {
                    continue; // migrasi = Stok Awal, bukan mutasi Masuk/Keluar
                }

                $tanggal = Carbon::parse($m->tanggal_perubahan_stok);
                // Presisi sesuai database (decimal 15,2). Tidak dilakukan pembulatan ke int.
                $jumlah = (float) $m->jumlah_perubahan;

                if ($tanggal->lt($mulai)) {
                    $netSebelumPeriode += $jumlah;
                } elseif ($tanggal->lte($selesai)) {
                    if ($jumlah > 0) {
                        $masuk += $jumlah;
                    } else {
                        $keluar += abs($jumlah);
                    }
                }
            }

            $stokAwal = $stokMigrasi + $netSebelumPeriode;
            $stokAkhir = $stokAwal + $masuk - $keluar;

            // HPP: batch terakhir s.d. periode -> batch migrasi -> harga_beli/biaya_rata_rata.
            $fallback = (float) ($p->harga_beli > 0 ? $p->harga_beli : ($p->biaya_rata_rata ?? 0));

            $hpp = $latestBatchMap->get($p->id);

            if ($hpp === null || (float) $hpp <= 0) {
                $hargaMigrasi = $migrasiBatchMap->get($p->id);

                if ($hargaMigrasi !== null && (float) $hargaMigrasi > 0) {
                    $hpp = (float) $hargaMigrasi;
                } else {
                    $hpp = $fallback;
                }
            } else {
                $hpp = (float) $hpp;
            }

            // Harga migrasi untuk nilai stok (fallback ke harga_beli/biaya_rata_rata).
            $hargaMigrasiNilai = $migrasiBatchMap->get($p->id);

            if ($hargaMigrasiNilai === null || (float) $hargaMigrasiNilai <= 0) {
                $hargaMigrasiNilai = $fallback;
            } else {
                $hargaMigrasiNilai = (float) $hargaMigrasiNilai;
            }

            $nilaiStok = round(
                ($stokMigrasi * $hargaMigrasiNilai)
                + (float) $totalBeliMap->get($p->id, 0)
                - (float) $totalJualMap->get($p->id, 0),
                2
            );

            $p->stok_masuk = $masuk;
            $p->stok_keluar = $keluar;
            $p->stok_awal_periode = $stokAwal;
            $p->stok_akhir = $stokAkhir;
            $p->hpp = $hpp;
            $p->nilai_stok = $nilaiStok;
        }

        return $products;
    }

    /**
     * HPP terakhir = harga satuan batch paling terakhir s.d. $endDate.
     * Jika tidak ada batch s.d. $endDate dan produk punya batch migrasi,
     * pakai harga satuan batch migrasi (bukan harga_beli master saat ini).
     * Fallback terakhir ke products.harga_beli (lalu biaya_rata_rata).
     */
    public static function hppTerakhir(Product $product, Carbon $endDate, float $stokMigrasi = 0): float
    {
        $fallback = (float) ($product->harga_beli > 0 ? $product->harga_beli : ($product->biaya_rata_rata ?? 0));

        if (! Schema::hasTable('product_batches')) {
            return $fallback;
        }

        $harga = ProductBatch::where('product_id', $product->id)
            ->where('tanggal_pembelian', '<=', $endDate->copy()->endOfDay())
            ->orderBy('tanggal_pembelian', 'desc')
            ->orderBy('id', 'desc')
            ->value('harga_satuan');

        if ($harga === null) {
            // Tidak ada batch s.d. periode: pakai harga batch migrasi bila ada.
            $hargaMigrasi = self::hargaBatchMigrasi($product);

            if ($hargaMigrasi !== null && $hargaMigrasi > 0) {
                return $hargaMigrasi;
            }

            return $fallback;
        }

        $harga = (float) $harga;

        return $harga > 0 ? $harga : $fallback;
    }

    /**
     * Harga satuan batch migrasi (no_batch LIKE '%MIGRATION%'), atau null bila tidak ada.
     */
    private static function hargaBatchMigrasi(Product $product): ?float
    {
        if (! Schema::hasTable('product_batches')) {
            return null;
        }

        $harga = ProductBatch::where('product_id', $product->id)
            ->where(function ($q) {
                $q->where('no_batch', 'like', '%MIGRATION%')
                    ->orWhere('no_batch', 'like', '%INIT%');
            })
            ->orderBy('tanggal_pembelian', 'desc')
            ->orderBy('id', 'desc')
            ->value('harga_satuan');

        if ($harga === null) {
            return null;
        }

        return (float) $harga;
    }

    /**
     * Total nilai rupiah pembelian produk s.d. $endDate.
     * = (stok awal migrasi * harga batch migrasi) + SUM(purchase_details.subtotal <= endDate).
     * Harga batch migrasi = harga_satuan ProductBatch (no_batch LIKE '%MIGRATION%'),
     * fallback ke products.harga_beli (lalu biaya_rata_rata).
     */
    public static function totalBeliSampai(Product $product, Carbon $endDate, float $stokMigrasi = 0): float
    {
        $selesai = $endDate->copy()->endOfDay();

        $total = 0.0;

        if ($stokMigrasi > 0) {
            // Harga stok migrasi diambil dari batch migrasi (harga saat migrasi),
            // bukan harga_beli master saat ini. Fallback ke harga_beli/biaya_rata_rata.
            $hargaMigrasi = self::hargaBatchMigrasi($product);

            if ($hargaMigrasi === null || $hargaMigrasi <= 0) {
                $hargaMigrasi = (float) ($product->harga_beli > 0 ? $product->harga_beli : ($product->biaya_rata_rata ?? 0));
            }

            $total += $stokMigrasi * $hargaMigrasi;
        }

        if (Schema::hasTable('purchase_details') && Schema::hasTable('purchases')) {
            $subtotal = DB::table('purchase_details as pd')
                ->join('purchases as p', 'p.id', '=', 'pd.purchase_id')
                ->where('pd.product_id', $product->id)
                ->where('p.tanggal_pembelian', '<=', $selesai)
                ->whereNull('pd.deleted_at')
                ->whereNull('p.deleted_at')
                ->sum('pd.subtotal');

            $total += (float) $subtotal;
        }

        return round($total, 2);
    }

    /**
     * Total nilai rupiah penjualan produk s.d. $endDate.
     * = SUM(sale_details.hpp <= endDate).
     */
    public static function totalJualSampai(Product $product, Carbon $endDate): float
    {
        if (! Schema::hasTable('sale_details') || ! Schema::hasTable('sales')) {
            return 0.0;
        }

        $selesai = $endDate->copy()->endOfDay();

        $subtotal = DB::table('sale_details as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->where('sd.product_id', $product->id)
            ->where('s.tanggal_transaksi', '<=', $selesai)
            ->whereNull('sd.deleted_at')
            ->whereNull('s.deleted_at')
            ->sum('sd.hpp');

        return round((float) $subtotal, 2);
    }

    private static function isMigration($movement): bool
    {
        $ref = $movement->reference_type ?? '';
        if ($ref === 'migration') {
            return true;
        }

        // Stock opname inisialisasi awal (SO-INIT).
        if ($ref === 'stock_opname') {
            $catatan = strtolower($movement->catatan ?? '');
            if (str_contains($catatan, 'so-init') || str_contains($catatan, 'penyesuaian stok awal') || str_contains($catatan, 'stok awal via impor')) {
                return true;
            }
        }

        return false;
    }
}
