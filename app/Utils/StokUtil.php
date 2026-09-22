<?php

namespace App\Utils;

use App\Models\Product;
use App\Models\ProductBatch;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class StokUtil
{
    /**
     * Hitung posisi stok satu produk untuk periode [startDate, endDate].
     *
     * Aturan laporan (spec):
     * - Stok Awal  = Stok Awal Migrasi (saldo awal saat import/migrasi) + mutasi sistem sebelum periode.
     * - Masuk      = mutasi positif dalam periode (pembelian, retur penjualan, penyesuaian naik).
     * - Keluar     = mutasi negatif dalam periode (penjualan, retur pembelian, penyesuaian turun).
     * - Stok Akhir = Stok Awal + Masuk - Keluar.
     *
     * Perlakuan khusus data migrasi (reference_type = 'migration'):
     * - Movement migrasi TIDAK dihitung sebagai Masuk; ia adalah komponen Stok Awal.
     * - Mutasi non-migrasi (termasuk riwayat impor yang tanggalnya backdate) dihitung
     *   kronologis normal — di dataset production terbukti konsisten:
     *   stok_aktual = migrasi + seluruh mutasi non-migrasi (deviasi 1 unit).
     * - Untuk periode yang berakhir SEBELUM tanggal migrasi, stok migrasi tetap
     *   tampil sebagai Stok Awal/Stok Akhir (stok fisik memang sudah ada).
     *
     * Produk tanpa movement migrasi dihitung kumulatif normal dari seluruh mutasinya.
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
        $fifo = self::hitungFifoNilaiStok($product, $stokAkhir, $endDate);

        return [
            'stok_awal' => $stokAwal,
            'masuk' => $masuk,
            'keluar' => $keluar,
            'stok_akhir' => $stokAkhir,
            'hpp' => $fifo['hpp'],
            'nilai_stok' => $fifo['nilai_stok'],
        ];
    }

    /**
     * Hitung nilai stok akhir dan HPP per unit menurut metode costing produk.
     *
     * FIFO: unit stok akhir dialokasikan mundur ke batch pembelian paling baru
     * sampai endDate. Batch yang lebih lama dipakai hanya jika kuota batch terbaru
     * tidak cukup. Kuantitas di luar histori batch memakai fallback harga beli.
     */
    public static function hitungFifoNilaiStok(Product $product, int $stokAkhir, Carbon $endDate): array
    {
        $fallbackCost = (float) ($product->harga_beli > 0 ? $product->harga_beli : ($product->biaya_rata_rata ?? 0));

        if ($stokAkhir <= 0) {
            return [
                'hpp' => $fallbackCost,
                'nilai_stok' => 0.0,
            ];
        }

        if ($product->metode_biaya === 'AVERAGE' && (float) $product->biaya_rata_rata > 0) {
            $hpp = (float) $product->biaya_rata_rata;

            return [
                'hpp' => $hpp,
                'nilai_stok' => round($stokAkhir * $hpp, 2),
            ];
        }

        if (! Schema::hasTable('product_batches')) {
            return [
                'hpp' => $fallbackCost,
                'nilai_stok' => round($stokAkhir * $fallbackCost, 2),
            ];
        }

        $batches = ProductBatch::where('product_id', $product->id)
            ->where('tanggal_pembelian', '<=', $endDate->copy()->endOfDay())
            ->orderBy('tanggal_pembelian', 'desc')
            ->orderBy('id', 'desc')
            ->get(['tanggal_pembelian', 'harga_satuan', 'jumlah_awal']);

        if ($batches->isEmpty()) {
            return [
                'hpp' => $fallbackCost,
                'nilai_stok' => round($stokAkhir * $fallbackCost, 2),
            ];
        }

        $needed = $stokAkhir;
        $totalNilai = 0.0;

        foreach ($batches as $batch) {
            $batchQty = max(0, (int) $batch->jumlah_awal);
            $take = min($needed, $batchQty);
            $unitCost = (float) $batch->harga_satuan > 0 ? (float) $batch->harga_satuan : $fallbackCost;

            $totalNilai += ($take * $unitCost);
            $needed -= $take;

            if ($needed <= 0) {
                break;
            }
        }

        if ($needed > 0) {
            $totalNilai += ($needed * $fallbackCost);
        }

        $nilaiStok = round($totalNilai, 2);

        return [
            'hpp' => $stokAkhir > 0 ? round($nilaiStok / $stokAkhir, 2) : $fallbackCost,
            'nilai_stok' => $nilaiStok,
        ];
    }

    private static function isMigration($movement): bool
    {
        return ($movement->reference_type ?? '') === 'migration';
    }
}
