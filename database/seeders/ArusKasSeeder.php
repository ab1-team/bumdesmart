<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArusKasSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('arus_kas_rekenings')->truncate();
        DB::table('arus_kas')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // -------------------------------------------------------
        // arus_kas
        // -------------------------------------------------------
        $jsonPath = base_path('arus_kas.json');
        if (file_exists($jsonPath)) {
            $data = json_decode(file_get_contents($jsonPath), true);
            $rows = array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'nama_akun' => $item['nama_akun'],
                    'urutan' => $item['urutan'] ?? 0,
                    'sub' => $item['sub'] ?? 0,
                    'super_sub' => $item['super_sub'] ?? 0,
                    'status' => $item['status'] ?? 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $data);
            DB::table('arus_kas')->insert($rows);
        } else {
            DB::table('arus_kas')->insert([
                ['id' => 1, 'nama_akun' => 'SALDO KAS SETARA KAS AWAL BULAN', 'urutan' => 0, 'sub' => 0, 'super_sub' => 1, 'status' => 'A'],
                ['id' => 2, 'nama_akun' => 'A.  Arus Kas dari Aktivitas Operasi', 'urutan' => 0, 'sub' => 0, 'super_sub' => 2, 'status' => 'A'],
                ['id' => 3, 'nama_akun' => 'Penerimaan Operasi', 'urutan' => 0, 'sub' => 0, 'super_sub' => 3, 'status' => 'A'],
                ['id' => 4, 'nama_akun' => 'Penerimaan Penjualan', 'urutan' => 0, 'sub' => 3, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 5, 'nama_akun' => 'Penerimaan Diskon Penjualan', 'urutan' => 0, 'sub' => 3, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 6, 'nama_akun' => 'Penerimaan Retur Penjualan', 'urutan' => 0, 'sub' => 3, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 7, 'nama_akun' => 'Penerimaan Pendapatan Sewa Ruang/Rak', 'urutan' => 0, 'sub' => 3, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 8, 'nama_akun' => 'Penerimaan Pendapatan Lain-lain', 'urutan' => 0, 'sub' => 3, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 9, 'nama_akun' => 'Penerimaan Cashback Penjualan', 'urutan' => 0, 'sub' => 3, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 10, 'nama_akun' => 'Persediaan', 'urutan' => 0, 'sub' => 3, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 11, 'nama_akun' => 'Penerimaan Piutang Dagang', 'urutan' => 0, 'sub' => 3, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 12, 'nama_akun' => 'Penerimaan Piutang Karyawan', 'urutan' => 0, 'sub' => 3, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 13, 'nama_akun' => 'Penerimaan Piutang Lain-lain', 'urutan' => 0, 'sub' => 3, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 14, 'nama_akun' => 'Penerimaan Utang Pembelian', 'urutan' => 0, 'sub' => 3, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 15, 'nama_akun' => 'Penerimaan Utang DP', 'urutan' => 0, 'sub' => 3, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 16, 'nama_akun' => 'Penerimaan Utang Operasional Lainnya', 'urutan' => 0, 'sub' => 3, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 17, 'nama_akun' => 'Penerimaan Bunga Bank', 'urutan' => 0, 'sub' => 3, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 18, 'nama_akun' => 'Penerimaan Lain-lain', 'urutan' => 0, 'sub' => 3, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 19, 'nama_akun' => 'B. Pengeluaran Operasi', 'urutan' => 0, 'sub' => 0, 'super_sub' => 4, 'status' => 'A'],
                ['id' => 20, 'nama_akun' => 'Bayar Beban Pokok Pendapatan', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 21, 'nama_akun' => 'Bayar Diskon Pembelian', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 22, 'nama_akun' => 'Bayar Retur Pembelian', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 23, 'nama_akun' => 'Bayar Beban Produksi', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 24, 'nama_akun' => 'Bayar Beban Transport Produk', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 25, 'nama_akun' => 'Bayar Cashback Pembelian', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 26, 'nama_akun' => 'Bayar Beban Gaji', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 27, 'nama_akun' => 'Bayar Beban Tunjangan', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 28, 'nama_akun' => 'Bayar Beban Administrasi dan Umum', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 29, 'nama_akun' => 'Bayar Beban Promosi & Pemasaran', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 30, 'nama_akun' => 'Bayar Beban Transport Operasional', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 31, 'nama_akun' => 'Bayar Beban Training & Rapat', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 32, 'nama_akun' => 'Beban Perawatan dan Perbaikan Aset', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 33, 'nama_akun' => 'Bayar Beban Usaha Lain-lain', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 34, 'nama_akun' => 'Persediaan', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 35, 'nama_akun' => 'Bayar Piutang Dagang', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 36, 'nama_akun' => 'Bayar Piutang Karyawan', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 37, 'nama_akun' => 'Bayar Piutang Lain-lain', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 38, 'nama_akun' => 'Bayar Utang Pembelian', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 39, 'nama_akun' => 'Bayar Utang DP', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 40, 'nama_akun' => 'Bayar Utang Operasional', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 41, 'nama_akun' => 'Beban Bunga Utang', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 42, 'nama_akun' => 'Beban Admin Bank', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 43, 'nama_akun' => 'Beban Pajak Bunga Bank', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 44, 'nama_akun' => 'Beban Non Usaha Lain-lain', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 45, 'nama_akun' => 'Beban PPh', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 46, 'nama_akun' => 'Beban PPN', 'urutan' => 0, 'sub' => 4, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 47, 'nama_akun' => '2. Arus Kas dari Aktivitas Investasi', 'urutan' => 0, 'sub' => 0, 'super_sub' => 5, 'status' => 'A'],
                ['id' => 48, 'nama_akun' => 'A. Penerimaan Investasi', 'urutan' => 0, 'sub' => 0, 'super_sub' => 6, 'status' => 'A'],
                ['id' => 49, 'nama_akun' => 'Penjualan Aset Tetap dan Inventaris', 'urutan' => 0, 'sub' => 6, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 50, 'nama_akun' => 'Penerimaan Konstruksi dalam Proses & Uang Muka', 'urutan' => 0, 'sub' => 6, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 51, 'nama_akun' => 'Penerimaan Aset Lain-lain', 'urutan' => 0, 'sub' => 6, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 52, 'nama_akun' => 'B. Pengeluaran Investasi', 'urutan' => 0, 'sub' => 0, 'super_sub' => 7, 'status' => 'A'],
                ['id' => 53, 'nama_akun' => 'Pembelian Tanah', 'urutan' => 0, 'sub' => 7, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 54, 'nama_akun' => 'Pembelian Gedung', 'urutan' => 0, 'sub' => 7, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 55, 'nama_akun' => 'Pembelian Kendaraan/Mesin', 'urutan' => 0, 'sub' => 7, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 56, 'nama_akun' => 'Pembelian Peralatan/Inventaris', 'urutan' => 0, 'sub' => 7, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 57, 'nama_akun' => 'Pembelian Aset Tak Berwujud', 'urutan' => 0, 'sub' => 7, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 58, 'nama_akun' => 'Konstruksi dalam Proses', 'urutan' => 0, 'sub' => 7, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 59, 'nama_akun' => 'Bayar Aset Lain-lain', 'urutan' => 0, 'sub' => 7, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 60, 'nama_akun' => '3. Arus Kas dari Aktivitas Pendanaan', 'urutan' => 0, 'sub' => 0, 'super_sub' => 8, 'status' => 'A'],
                ['id' => 61, 'nama_akun' => 'A. Penerimaan Pendanaan', 'urutan' => 0, 'sub' => 0, 'super_sub' => 9, 'status' => 'A'],
                ['id' => 62, 'nama_akun' => 'Penerimaan Modal', 'urutan' => 0, 'sub' => 9, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 63, 'nama_akun' => 'Penerimaan Pinjaman Bank', 'urutan' => 0, 'sub' => 9, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 64, 'nama_akun' => 'Penerimaan Utang Dividen', 'urutan' => 0, 'sub' => 9, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 65, 'nama_akun' => 'Penerimaan Utang Pajak', 'urutan' => 0, 'sub' => 9, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 66, 'nama_akun' => 'Penerimaan Utang Lainnya', 'urutan' => 0, 'sub' => 9, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 67, 'nama_akun' => 'B. Pengeluaran Pendanaan', 'urutan' => 0, 'sub' => 0, 'super_sub' => 10, 'status' => 'A'],
                ['id' => 68, 'nama_akun' => 'Penarikan Modal', 'urutan' => 0, 'sub' => 10, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 69, 'nama_akun' => 'Pembayaran Pokok Pinjaman Bank', 'urutan' => 0, 'sub' => 10, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 70, 'nama_akun' => 'Pembayaran Dividen', 'urutan' => 0, 'sub' => 10, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 71, 'nama_akun' => 'Pembayaran Utang Pajak', 'urutan' => 0, 'sub' => 10, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 72, 'nama_akun' => 'Pembayaran Utang Lainnya', 'urutan' => 0, 'sub' => 10, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 73, 'nama_akun' => 'II.  Kenaikan/(Penurunan) Kas dan Setara Kas (Nilai 1C + 2C + 3C)', 'urutan' => 0, 'sub' => 0, 'super_sub' => 0, 'status' => 'A'],
                ['id' => 74, 'nama_akun' => 'SALDO AKHIR KAS SETARA KAS (Nilai I + II)', 'urutan' => 0, 'sub' => 0, 'super_sub' => 0, 'status' => 'A'],
            ]);
        }

        // -------------------------------------------------------
        // arus_kas_rekening
        // -------------------------------------------------------
        DB::table('arus_kas_rekenings')->insert([
            // 1. A. PENERIMAAN OPERASI (super_sub: 3)
            // 4. Penerimaan Penjualan (4.1.01.01)
            ['arus_kas_id' => 4, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '4.1.01.01'],
            ['arus_kas_id' => 4, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '4.1.01.01'],

            // 5. Penerimaan Diskon Penjualan (4.1.01.02)
            ['arus_kas_id' => 5, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '4.1.01.02'],
            ['arus_kas_id' => 5, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '4.1.01.02'],

            // 6. Penerimaan Retur Penjualan (4.1.01.03)
            ['arus_kas_id' => 6, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '4.1.01.03'],
            ['arus_kas_id' => 6, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '4.1.01.03'],

            // 7. Penerimaan Pendapatan Sewa Ruang/Rak (4.1.01.04)
            ['arus_kas_id' => 7, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '4.1.01.04'],
            ['arus_kas_id' => 7, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '4.1.01.04'],

            // 8. Penerimaan Pendapatan Lain-lain (4.1.01.05)
            ['arus_kas_id' => 8, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '4.1.01.05'],
            ['arus_kas_id' => 8, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '4.1.01.05'],

            // 9. Penerimaan Cashback Penjualan (4.1.01.06)
            ['arus_kas_id' => 9, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '4.1.01.06'],
            ['arus_kas_id' => 9, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '4.1.01.06'],

            // 10. Persediaan (1.1.03.01)
            ['arus_kas_id' => 10, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '1.1.03%'],
            ['arus_kas_id' => 10, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '1.1.03%'],

            // 11. Penerimaan Piutang Dagang (1.1.04.01)
            ['arus_kas_id' => 11, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '1.1.04.01'],
            ['arus_kas_id' => 11, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '1.1.04.01'],

            // 12. Penerimaan Piutang Karyawan (1.1.04.02)
            ['arus_kas_id' => 12, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '1.1.04.02'],
            ['arus_kas_id' => 12, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '1.1.04.02'],

            // 13. Penerimaan Piutang Lain-lain (1.1.04.03)
            ['arus_kas_id' => 13, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '1.1.04.03'],
            ['arus_kas_id' => 13, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '1.1.04.03'],

            // 14. Penerimaan Utang Pembelian (2.1.01.01)
            ['arus_kas_id' => 14, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '2.1.01.01'],
            ['arus_kas_id' => 14, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '2.1.01.01'],

            // 15. Penerimaan Utang DP (2.1.01.02)
            ['arus_kas_id' => 15, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '2.1.01.02'],
            ['arus_kas_id' => 15, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '2.1.01.02'],

            // 16. Penerimaan Utang Operasional Lainnya (2.1.01.03, 2.1.01.04, 2.1.04%)
            ['arus_kas_id' => 16, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '2.1.01.03'],
            ['arus_kas_id' => 16, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '2.1.01.04'],
            ['arus_kas_id' => 16, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '2.1.04%'],
            ['arus_kas_id' => 16, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '2.1.01.03'],
            ['arus_kas_id' => 16, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '2.1.01.04'],
            ['arus_kas_id' => 16, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '2.1.04%'],

            // 17. Penerimaan Bunga Bank (7.1.01.01, 7.1.01.02)
            ['arus_kas_id' => 17, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '7.1.01%'],
            ['arus_kas_id' => 17, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '7.1.01%'],

            // 18. Penerimaan Lain-lain (1.1.05%)
            ['arus_kas_id' => 18, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '1.1.05%'],
            ['arus_kas_id' => 18, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '1.1.05%'],

            // 1. B. PENGELUARAN OPERASI (super_sub: 4)
            // 20. Bayar Beban Pokok Pendapatan (5.1.01.01)
            ['arus_kas_id' => 20, 'rekening_debit' => '5.1.01.01', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 20, 'rekening_debit' => '5.1.01.01', 'rekening_kredit' => '1.1.02%'],

            // 21. Bayar Diskon Pembelian (5.1.01.02)
            ['arus_kas_id' => 21, 'rekening_debit' => '5.1.01.02', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 21, 'rekening_debit' => '5.1.01.02', 'rekening_kredit' => '1.1.02%'],

            // 22. Bayar Retur Pembelian (5.1.01.03)
            ['arus_kas_id' => 22, 'rekening_debit' => '5.1.01.03', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 22, 'rekening_debit' => '5.1.01.03', 'rekening_kredit' => '1.1.02%'],

            // 23. Bayar Beban Produksi (5.1.01.04)
            ['arus_kas_id' => 23, 'rekening_debit' => '5.1.01.04', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 23, 'rekening_debit' => '5.1.01.04', 'rekening_kredit' => '1.1.02%'],

            // 24. Bayar Beban Transport Produk (5.1.01.05)
            ['arus_kas_id' => 24, 'rekening_debit' => '5.1.01.05', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 24, 'rekening_debit' => '5.1.01.05', 'rekening_kredit' => '1.1.02%'],

            // 25. Bayar Cashback Pembelian (5.1.01.06)
            ['arus_kas_id' => 25, 'rekening_debit' => '5.1.01.06', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 25, 'rekening_debit' => '5.1.01.06', 'rekening_kredit' => '1.1.02%'],

            // 26. Bayar Beban Gaji (6.1.01.01)
            ['arus_kas_id' => 26, 'rekening_debit' => '6.1.01%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 26, 'rekening_debit' => '6.1.01%', 'rekening_kredit' => '1.1.02%'],

            // 27. Bayar Beban Tunjangan (6.1.02.01)
            ['arus_kas_id' => 27, 'rekening_debit' => '6.1.02%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 27, 'rekening_debit' => '6.1.02%', 'rekening_kredit' => '1.1.02%'],

            // 28. Bayar Beban Administrasi dan Umum (6.1.03.01)
            ['arus_kas_id' => 28, 'rekening_debit' => '6.1.03%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 28, 'rekening_debit' => '6.1.03%', 'rekening_kredit' => '1.1.02%'],

            // 29. Bayar Beban Promosi & Pemasaran (6.1.04.01)
            ['arus_kas_id' => 29, 'rekening_debit' => '6.1.04%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 29, 'rekening_debit' => '6.1.04%', 'rekening_kredit' => '1.1.02%'],

            // 30. Bayar Beban Transport Operasional (6.1.05.01)
            ['arus_kas_id' => 30, 'rekening_debit' => '6.1.05%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 30, 'rekening_debit' => '6.1.05%', 'rekening_kredit' => '1.1.02%'],

            // 31. Bayar Beban Training & Rapat (6.1.06.01)
            ['arus_kas_id' => 31, 'rekening_debit' => '6.1.06%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 31, 'rekening_debit' => '6.1.06%', 'rekening_kredit' => '1.1.02%'],

            // 32. Beban Perawatan dan Perbaikan Aset (6.1.07.01)
            ['arus_kas_id' => 32, 'rekening_debit' => '6.1.07%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 32, 'rekening_debit' => '6.1.07%', 'rekening_kredit' => '1.1.02%'],

            // 33. Bayar Beban Usaha Lain-lain (6.1.10.01)
            ['arus_kas_id' => 33, 'rekening_debit' => '6.1.10%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 33, 'rekening_debit' => '6.1.10%', 'rekening_kredit' => '1.1.02%'],

            // 34. Persediaan (1.1.03.01)
            ['arus_kas_id' => 34, 'rekening_debit' => '1.1.03%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 34, 'rekening_debit' => '1.1.03%', 'rekening_kredit' => '1.1.02%'],

            // 35. Bayar Piutang Dagang (1.1.04.01)
            ['arus_kas_id' => 35, 'rekening_debit' => '1.1.04.01', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 35, 'rekening_debit' => '1.1.04.01', 'rekening_kredit' => '1.1.02%'],

            // 36. Bayar Piutang Karyawan (1.1.04.02)
            ['arus_kas_id' => 36, 'rekening_debit' => '1.1.04.02', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 36, 'rekening_debit' => '1.1.04.02', 'rekening_kredit' => '1.1.02%'],

            // 37. Bayar Piutang Lain-lain (1.1.04.03)
            ['arus_kas_id' => 37, 'rekening_debit' => '1.1.04.03', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 37, 'rekening_debit' => '1.1.04.03', 'rekening_kredit' => '1.1.02%'],

            // 38. Bayar Utang Pembelian (2.1.01.01)
            ['arus_kas_id' => 38, 'rekening_debit' => '2.1.01.01', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 38, 'rekening_debit' => '2.1.01.01', 'rekening_kredit' => '1.1.02%'],

            // 39. Bayar Utang DP (2.1.01.02)
            ['arus_kas_id' => 39, 'rekening_debit' => '2.1.01.02', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 39, 'rekening_debit' => '2.1.01.02', 'rekening_kredit' => '1.1.02%'],

            // 40. Bayar Utang Operasional (2.1.01.03, 2.1.01.04, 2.1.04%)
            ['arus_kas_id' => 40, 'rekening_debit' => '2.1.01.03', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 40, 'rekening_debit' => '2.1.01.04', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 40, 'rekening_debit' => '2.1.04%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 40, 'rekening_debit' => '2.1.01.03', 'rekening_kredit' => '1.1.02%'],
            ['arus_kas_id' => 40, 'rekening_debit' => '2.1.01.04', 'rekening_kredit' => '1.1.02%'],
            ['arus_kas_id' => 40, 'rekening_debit' => '2.1.04%', 'rekening_kredit' => '1.1.02%'],

            // 41. Beban Bunga Utang (7.2.01.01)
            ['arus_kas_id' => 41, 'rekening_debit' => '7.2.01%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 41, 'rekening_debit' => '7.2.01%', 'rekening_kredit' => '1.1.02%'],

            // 42. Beban Admin Bank (7.3.01.01)
            ['arus_kas_id' => 42, 'rekening_debit' => '7.3.01.01', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 42, 'rekening_debit' => '7.3.01.01', 'rekening_kredit' => '1.1.02%'],

            // 43. Beban Pajak Bunga Bank (7.3.01.02)
            ['arus_kas_id' => 43, 'rekening_debit' => '7.3.01.02', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 43, 'rekening_debit' => '7.3.01.02', 'rekening_kredit' => '1.1.02%'],

            // 44. Beban Non Usaha Lain-lain (7.3.02.01)
            ['arus_kas_id' => 44, 'rekening_debit' => '7.3.02%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 44, 'rekening_debit' => '7.3.02%', 'rekening_kredit' => '1.1.02%'],

            // 45. Beban PPh (7.4.01.02)
            ['arus_kas_id' => 45, 'rekening_debit' => '7.4.01.02', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 45, 'rekening_debit' => '7.4.01.02', 'rekening_kredit' => '1.1.02%'],

            // 46. Beban PPN (7.4.01.01, 1.1.07%)
            ['arus_kas_id' => 46, 'rekening_debit' => '7.4.01.01', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 46, 'rekening_debit' => '1.1.07%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 46, 'rekening_debit' => '7.4.01.01', 'rekening_kredit' => '1.1.02%'],
            ['arus_kas_id' => 46, 'rekening_debit' => '1.1.07%', 'rekening_kredit' => '1.1.02%'],

            // 2. A. PENERIMAAN INVESTASI (super_sub: 6)
            // 49. Penjualan Aset Tetap dan Inventaris (1.2.01%)
            ['arus_kas_id' => 49, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '1.2.01%'],
            ['arus_kas_id' => 49, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '1.2.01%'],

            // 50. Penerimaan Konstruksi dalam Proses & Uang Muka (1.2.05%)
            ['arus_kas_id' => 50, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '1.2.05%'],
            ['arus_kas_id' => 50, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '1.2.05%'],

            // 51. Penerimaan Aset Lain-lain (1.3.01%)
            ['arus_kas_id' => 51, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '1.3.01%'],
            ['arus_kas_id' => 51, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '1.3.01%'],

            // 2. B. PENGELUARAN INVESTASI (super_sub: 7)
            // 53. Pembelian Tanah (1.2.01.01)
            ['arus_kas_id' => 53, 'rekening_debit' => '1.2.01.01', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 53, 'rekening_debit' => '1.2.01.01', 'rekening_kredit' => '1.1.02%'],

            // 54. Pembelian Gedung (1.2.01.02)
            ['arus_kas_id' => 54, 'rekening_debit' => '1.2.01.02', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 54, 'rekening_debit' => '1.2.01.02', 'rekening_kredit' => '1.1.02%'],

            // 55. Pembelian Kendaraan/Mesin (1.2.01.03)
            ['arus_kas_id' => 55, 'rekening_debit' => '1.2.01.03', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 55, 'rekening_debit' => '1.2.01.03', 'rekening_kredit' => '1.1.02%'],

            // 56. Pembelian Peralatan/Inventaris (1.2.01.04)
            ['arus_kas_id' => 56, 'rekening_debit' => '1.2.01.04', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 56, 'rekening_debit' => '1.2.01.04', 'rekening_kredit' => '1.1.02%'],

            // 57. Pembelian Aset Tak Berwujud (1.2.03%)
            ['arus_kas_id' => 57, 'rekening_debit' => '1.2.03%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 57, 'rekening_debit' => '1.2.03%', 'rekening_kredit' => '1.1.02%'],

            // 58. Konstruksi dalam Proses (1.2.05%)
            ['arus_kas_id' => 58, 'rekening_debit' => '1.2.05%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 58, 'rekening_debit' => '1.2.05%', 'rekening_kredit' => '1.1.02%'],

            // 59. Bayar Aset Lain-lain (1.3.01%)
            ['arus_kas_id' => 59, 'rekening_debit' => '1.3.01%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 59, 'rekening_debit' => '1.3.01%', 'rekening_kredit' => '1.1.02%'],

            // 3. A. PENERIMAAN PENDANAAN (super_sub: 9)
            // 62. Penerimaan Modal (3.1%)
            ['arus_kas_id' => 62, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '3.1%'],
            ['arus_kas_id' => 62, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '3.1%'],

            // 63. Penerimaan Pinjaman Bank (2.2.01.01)
            ['arus_kas_id' => 63, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '2.2.01.01'],
            ['arus_kas_id' => 63, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '2.2.01.01'],

            // 64. Penerimaan Utang Dividen (2.1.03%)
            ['arus_kas_id' => 64, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '2.1.03%'],
            ['arus_kas_id' => 64, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '2.1.03%'],

            // 65. Penerimaan Utang Pajak (2.1.02%)
            ['arus_kas_id' => 65, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '2.1.02%'],
            ['arus_kas_id' => 65, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '2.1.02%'],

            // 66. Penerimaan Utang Lainnya (2.1.04%, 2.2.01.02)
            ['arus_kas_id' => 66, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '2.1.04%'],
            ['arus_kas_id' => 66, 'rekening_debit' => '1.1.01%', 'rekening_kredit' => '2.2.01.02'],
            ['arus_kas_id' => 66, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '2.1.04%'],
            ['arus_kas_id' => 66, 'rekening_debit' => '1.1.02%', 'rekening_kredit' => '2.2.01.02'],

            // 3. B. PENGELUARAN PENDANAAN (super_sub: 10)
            // 68. Penarikan Modal (3.1%)
            ['arus_kas_id' => 68, 'rekening_debit' => '3.1%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 68, 'rekening_debit' => '3.1%', 'rekening_kredit' => '1.1.02%'],

            // 69. Pembayaran Pokok Pinjaman Bank (2.2.01.01)
            ['arus_kas_id' => 69, 'rekening_debit' => '2.2.01.01', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 69, 'rekening_debit' => '2.2.01.01', 'rekening_kredit' => '1.1.02%'],

            // 70. Pembayaran Dividen (2.1.03%)
            ['arus_kas_id' => 70, 'rekening_debit' => '2.1.03%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 70, 'rekening_debit' => '2.1.03%', 'rekening_kredit' => '1.1.02%'],

            // 71. Pembayaran Utang Pajak (2.1.02%)
            ['arus_kas_id' => 71, 'rekening_debit' => '2.1.02%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 71, 'rekening_debit' => '2.1.02%', 'rekening_kredit' => '1.1.02%'],

            // 72. Pembayaran Utang Lainnya (2.1.04%, 2.2.01.02)
            ['arus_kas_id' => 72, 'rekening_debit' => '2.1.04%', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 72, 'rekening_debit' => '2.2.01.02', 'rekening_kredit' => '1.1.01%'],
            ['arus_kas_id' => 72, 'rekening_debit' => '2.1.04%', 'rekening_kredit' => '1.1.02%'],
            ['arus_kas_id' => 72, 'rekening_debit' => '2.2.01.02', 'rekening_kredit' => '1.1.02%'],
        ]);
    }
}
