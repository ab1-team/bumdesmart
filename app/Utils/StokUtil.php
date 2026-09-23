<?php

namespace App\Utils;

use App\Models\Product;
use App\Models\ProductBatch;
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
     * - Total Jual  = SUM(sale_details.subtotal <= endDate).
     *
     * @return array{stok_awal: int, masuk: int, keluar: int, stok_akhir: int, hpp: float, nilai_stok: float}
     */
    public static function stokPeriode(Product $product, Carbon $startDate, Carbon $endDate): array
    {
        $mulai = $startDate->copy()->startOfDay();
        $selesai = $endDate->copy()->endOfDay();

        $movements = $product->stockMovements()
            ->orderBy('tanggal_perubahan_stok')
            ->get(['jumlah_perubahan', 'tanggal_perubahan_stok', 'reference_type']);

        $stokMigrasi = 0;

        foreach ($movements as $m) {
            if (self::isMigration($m)) {
                $stokMigrasi += (int) round((float) $m->jumlah_perubahan);
            }
        }

        $masuk = 0;
        $keluar = 0;
        $netSebelumPeriode = 0;

        foreach ($movements as $m) {
            if (self::isMigration($m)) {
                continue; // migrasi = Stok Awal, bukan mutasi Masuk/Keluar
            }

            $tanggal = Carbon::parse($m->tanggal_perubahan_stok);
            $jumlah = (int) round((float) $m->jumlah_perubahan);

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
     * HPP terakhir = harga satuan batch paling terakhir s.d. $endDate.
     * Jika tidak ada batch s.d. $endDate dan produk punya batch migrasi,
     * pakai harga satuan batch migrasi (bukan harga_beli master saat ini).
     * Fallback terakhir ke products.harga_beli (lalu biaya_rata_rata).
     */
    public static function hppTerakhir(Product $product, Carbon $endDate, int $stokMigrasi = 0): float
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
            ->where('no_batch', 'like', '%MIGRATION%')
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
    public static function totalBeliSampai(Product $product, Carbon $endDate, int $stokMigrasi = 0): float
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
     * = SUM(sale_details.subtotal <= endDate).
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
            ->sum('sd.subtotal');

        return round((float) $subtotal, 2);
    }

    private static function isMigration($movement): bool
    {
        return ($movement->reference_type ?? '') === 'migration';
    }
}
