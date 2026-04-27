<?php

namespace App\Utils;

use App\Models\Account;
use App\Models\AkunLevel1;
use App\Models\ArusKas;
use App\Models\Payment;

class KeuanganUtil
{
    public static function sumSaldo($account, $bulan = '00'): string
    {
        $saldo = 0;
        if ($account->balance) {
            $bulan = intval($bulan);
            for ($i = 0; $i <= $bulan; $i++) {
                $kolomDebit = 'debit_' . str_pad($i, 2, '0', STR_PAD_LEFT);
                $kolomKredit = 'kredit_' . str_pad($i, 2, '0', STR_PAD_LEFT);

                $saldoAkun = $account->balance->$kolomDebit - $account->balance->$kolomKredit;
                if ($account->jenis_mutasi == 'kredit') {
                    $saldoAkun = $account->balance->$kolomKredit - $account->balance->$kolomDebit;
                }

                $saldo += $saldoAkun;
            }
        }

        return $saldo;
    }

    public static function saldoKas($tahun, $bulan): string
    {
        $accounts = Account::where([
            ['business_id', auth()->user()->business_id],
            ['kode', 'LIKE', '1.1.01.%'],
        ])->with([
            'balance' => function ($query) use ($tahun) {
                $query->where('tahun', $tahun);
            },
        ])->get();

        $saldo = 0;
        foreach ($accounts as $account) {
            $saldo += self::sumSaldo($account, $bulan);
        }

        return $saldo;
    }

    public static function saldoLabaRugi($tahun, $bulan = '00'): string
    {
        $return = 0;
        $labaRugi = self::labaRugi($tahun, $bulan);
        foreach ($labaRugi as $lr) {
            $return = $lr['total'];
        }

        return $return;
    }

    public static function labaRugi($tahun, $bulan = '00'): array
    {
        $business_id = auth()->user()->business_id;
        $bulanInt = intval($bulan);

        // Fetch all relevant accounts in one go
        $accounts = Account::where('business_id', $business_id)
            ->where(function ($q) {
                $q->where('kode', 'LIKE', '4.%')
                    ->orWhere('kode', 'LIKE', '5.%')
                    ->orWhere('kode', 'LIKE', '6.%')
                    ->orWhere('kode', 'LIKE', '7.%')
                    ->orWhere('kode', '1.1.03.01');
            })
            ->with(['balance' => function ($query) use ($tahun) {
                $query->where('tahun', $tahun);
            }])
            ->get()
            ->keyBy('kode');

        // Helper to get monthly movement
        $getM = function ($kode) use ($accounts, $bulan) {
            $acc = $accounts->get($kode);
            if (!$acc || !$acc->balance) return ['debit' => 0, 'kredit' => 0];
            $b = str_pad(intval($bulan), 2, '0', STR_PAD_LEFT);
            return [
                'debit' => (float)($acc->balance->{"debit_$b"} ?? 0),
                'kredit' => (float)($acc->balance->{"kredit_$b"} ?? 0)
            ];
        };

        // Helper to get balance at end of month
        $getS = function ($kode, $bln) use ($accounts) {
            $acc = $accounts->get($kode);
            if (!$acc) return 0;
            return (float)self::sumSaldo($acc, $bln);
        };

        // --- 1. LABA KOTOR SECTION ---
        $mPenjualan = $getM('4.1.01.01');
        $penjualanGross = $mPenjualan['kredit'] - $mPenjualan['debit'];
        $diskonPenjualan = $getM('4.1.01.02')['debit'];
        $returPenjualan = $getM('4.1.01.03')['debit'];
        $cashbackPenjualan = $getM('4.1.01.06')['debit'];
        $penjualanBersih = $penjualanGross - $diskonPenjualan - $returPenjualan - $cashbackPenjualan;

        $persediaanAwal = $getS('1.1.03.01', $bulanInt - 1);
        $pembelianGross = $getM('1.1.03.01')['debit'];
        
        $diskonPembelian = $getM('5.1.01.02')['kredit'];
        $returPembelian = $getM('5.1.01.03')['kredit'];
        $cashbackPembelian = $getM('5.1.01.06')['kredit'];
        $bebanProduksi = $getM('5.1.01.04')['debit']; 
        $bebanTransport = $getM('5.1.01.05')['debit'];

        $pembelianBersih = $pembelianGross - ($diskonPembelian + $returPembelian + $cashbackPembelian) + $bebanProduksi + $bebanTransport;
        $totalPersediaan = $persediaanAwal + $pembelianBersih;
        
        $persediaanAkhir = $getS('1.1.03.01', $bulan);
        $hpp = $totalPersediaan - $persediaanAkhir;
        $labaKotor = $penjualanBersih - $hpp;

        $group1_kode = [
            ['kode' => '4.1.01.01', 'nama' => 'Penjualan', 'saldo_bulan_ini' => $penjualanGross, 'saldo_bulan_lalu' => $getS('4.1.01.01', $bulanInt - 1), 'saldo_tahun_lalu' => $getS('4.1.01.01', '00')],
            ['kode' => '4.1.01.02', 'nama' => 'Diskon Penjualan', 'saldo_bulan_ini' => $diskonPenjualan, 'saldo_bulan_lalu' => $getS('4.1.01.02', $bulanInt - 1), 'saldo_tahun_lalu' => $getS('4.1.01.02', '00')],
            ['kode' => '4.1.01.03', 'nama' => 'Retur Penjualan', 'saldo_bulan_ini' => $returPenjualan, 'saldo_bulan_lalu' => $getS('4.1.01.03', $bulanInt - 1), 'saldo_tahun_lalu' => $getS('4.1.01.03', '00')],
            ['kode' => '4.1.01.06', 'nama' => 'Cashback Penjualan', 'saldo_bulan_ini' => $cashbackPenjualan, 'saldo_bulan_lalu' => $getS('4.1.01.06', $bulanInt - 1), 'saldo_tahun_lalu' => $getS('4.1.01.06', '00')],
            ['kode' => '', 'nama' => 'Penjualan Bersih', 'saldo_bulan_ini' => $penjualanBersih, 'saldo_bulan_lalu' => 0, 'saldo_tahun_lalu' => 0, 'is_bold' => true],
        ];

        $group2_kode = [
            ['kode' => '', 'nama' => 'Persediaan Awal', 'saldo_bulan_ini' => $persediaanAwal, 'saldo_bulan_lalu' => 0, 'saldo_tahun_lalu' => 0],
            ['kode' => '1.1.03.01', 'nama' => 'Pembelian', 'saldo_bulan_ini' => $pembelianGross, 'saldo_bulan_lalu' => $getS('1.1.03.01', $bulanInt - 1), 'saldo_tahun_lalu' => $getS('1.1.03.01', '00')],
            ['kode' => '5.1.01.02', 'nama' => 'Diskon Pembelian', 'saldo_bulan_ini' => $diskonPenjualan, 'saldo_bulan_lalu' => $getS('5.1.01.02', $bulanInt - 1), 'saldo_tahun_lalu' => $getS('5.1.01.02', '00')],
            ['kode' => '5.1.01.03', 'nama' => 'Retur Pembelian', 'saldo_bulan_ini' => $returPenjualan, 'saldo_bulan_lalu' => $getS('5.1.01.03', $bulanInt - 1), 'saldo_tahun_lalu' => $getS('5.1.01.03', '00')],
            ['kode' => '5.1.01.04', 'nama' => 'Beban Produksi', 'saldo_bulan_ini' => $bebanProduksi, 'saldo_bulan_lalu' => $getS('5.1.01.04', $bulanInt - 1), 'saldo_tahun_lalu' => $getS('5.1.01.04', '00')],
            ['kode' => '5.1.01.05', 'nama' => 'Beban Transport Produk', 'saldo_bulan_ini' => $bebanTransport, 'saldo_bulan_lalu' => $getS('5.1.01.05', $bulanInt - 1), 'saldo_tahun_lalu' => $getS('5.1.01.05', '00')],
            ['kode' => '5.1.01.06', 'nama' => 'Cashback Pembelian', 'saldo_bulan_ini' => $cashbackPenjualan, 'saldo_bulan_lalu' => $getS('5.1.01.06', $bulanInt - 1), 'saldo_tahun_lalu' => $getS('5.1.01.06', '00')],
            ['kode' => '', 'nama' => 'Total Pembelian', 'saldo_bulan_ini' => $pembelianBersih, 'saldo_bulan_lalu' => 0, 'saldo_tahun_lalu' => 0, 'is_bold' => true],
            ['kode' => '', 'nama' => 'Total Persediaan', 'saldo_bulan_ini' => $totalPersediaan, 'saldo_bulan_lalu' => 0, 'saldo_tahun_lalu' => 0, 'is_bold' => true],
            ['kode' => '', 'nama' => 'Persediaan Akhir', 'saldo_bulan_ini' => $persediaanAkhir, 'saldo_bulan_lalu' => 0, 'saldo_tahun_lalu' => 0],
            ['kode' => '', 'nama' => 'Harga Pokok Penjualan', 'saldo_bulan_ini' => $hpp, 'saldo_bulan_lalu' => $getS('5.1.01.01', $bulanInt - 1), 'saldo_tahun_lalu' => $getS('5.1.01.01', '00'), 'is_bold' => true],
        ];

        $group = [
            '1' => [
                'nama' => 'Pendapatan',
                'jumlah' => $penjualanBersih,
                'total' => $penjualanBersih,
                'kode' => $group1_kode,
            ],
            '2' => [
                'nama' => 'Beban',
                'jumlah' => $hpp,
                'total' => $labaKotor,
                'kode' => $group2_kode,
            ],
            '3' => [
                'nama' => 'Beban',
                'jumlah' => 0,
                'total' => 0,
                'kode' => [],
            ],
            '4' => [
                'nama' => 'Pajak',
                'jumlah' => 0,
                'total' => 0,
                'kode' => [],
            ],
        ];

        // --- 2. OTHER SECTIONS ---
        foreach ($accounts as $account) {
            $kode = $account->kode;
            $kode1 = explode('.', $kode)[0];
            $kode2 = explode('.', $kode)[1];
            
            if ($kode == '4.1.01.01' || $kode == '4.1.01.02' || $kode == '4.1.01.03' || $kode == '4.1.01.06' ||
                $kode == '5.1.01.01' || $kode == '5.1.01.02' || $kode == '5.1.01.03' || $kode == '5.1.01.04' ||
                $kode == '5.1.01.05' || $kode == '5.1.01.06' || $kode == '1.1.03.01') {
                continue;
            }

            $saldo_bulan_ini = (float)self::sumSaldo($account, $bulan);
            $saldoData = [
                'kode' => $kode,
                'nama' => $account->nama,
                'saldo_bulan_ini' => $saldo_bulan_ini,
            ];

            if ($kode1 == '4') { // Other Income
                 $group['1']['kode'][] = $saldoData;
                 $group['1']['jumlah'] += $saldo_bulan_ini;
            } elseif (in_array($kode1, ['5', '6', '7']) && ($kode1 != '7' || $kode2 != '4')) { // Other Expenses
                 $group['3']['kode'][] = $saldoData;
                 $group['3']['jumlah'] -= $saldo_bulan_ini; 
            } elseif ($kode1 == '7' && $kode2 == '4') { // Tax
                 $group['4']['kode'][] = $saldoData;
                 $group['4']['jumlah'] -= $saldo_bulan_ini;
            }
        }

        // Final Totals
        $group['1']['total'] = $group['1']['jumlah']; // Total Pendapatan (Gambar 2)
        $labaKotorFix = $group['1']['total'] - $group['2']['jumlah']; 
        $group['2']['total'] = $labaKotorFix; // LABA KOTOR (Tambahan)
        
        $totalBebanGambar2 = $group['2']['jumlah'] - $group['3']['jumlah']; // HPP + Other Expenses
        $group['3']['jumlah_display'] = $totalBebanGambar2; // Total Beban (Gambar 2)
        $group['3']['total'] = $group['1']['total'] - $totalBebanGambar2; // Laba Sebelum Pajak (Gambar 2)
        
        $group['4']['total'] = $group['3']['total'] + $group['4']['jumlah']; // Laba Bersih (Gambar 2)

        // Add Margins
        $marginKotor = $penjualanBersih > 0 ? ($labaKotorFix / $penjualanBersih) * 100 : 0;
        $marginBersih = $penjualanBersih > 0 ? ($group['4']['total'] / $penjualanBersih) * 100 : 0;

        return [
            'groups' => array_values($group),
            'metrics' => [
                'margin_kotor' => $marginKotor,
                'margin_bersih' => $marginBersih,
            ]
        ];
    }

    public static function arusKas(string $tanggalMulai, string $tanggalAkhir)
    {
        $semuaArusKas = ArusKas::with('rekenings')->orderBy('id')->get()->keyBy('id');

        $leafNodes = $semuaArusKas->filter(fn($a) => $a->rekenings->isNotEmpty());
        $semuaArusKas->each(fn($a) => $a->total = 0);

        if ($leafNodes->isNotEmpty()) {
            $cases = 'CASE ';
            $bindings = [];

            foreach ($leafNodes as $arusKas) {
                $whens = $arusKas->rekenings->map(function ($r) use (&$bindings) {
                    $bindings[] = $r->rekening_debit;
                    $bindings[] = $r->rekening_kredit;

                    return '(rekening_debit LIKE ? AND rekening_kredit LIKE ?)';
                })->implode(' OR ');

                $cases .= "WHEN {$whens} THEN {$arusKas->id} ";
            }

            $cases .= 'END';

            $innerQuery = Payment::selectRaw("{$cases} as arus_kas_id, total_harga", $bindings)
                ->whereRaw("{$cases} IS NOT NULL", $bindings)
                ->whereBetween('tanggal_pembayaran', [$tanggalMulai, $tanggalAkhir]);

            $totals = Payment::selectRaw('arus_kas_id, SUM(total_harga) as total')
                ->fromSub($innerQuery, 'grouped')
                ->groupBy('arus_kas_id')
                ->pluck('total', 'arus_kas_id');

            foreach ($leafNodes as $id => $arusKas) {
                $arusKas->total = (float) ($totals->get($id) ?? 0);
            }
        }

        $visited = [];

        $aggregate = function ($node) use (&$aggregate, $semuaArusKas, &$visited) {
            if (isset($visited[$node->id])) {
                return;
            }
            $visited[$node->id] = true;

            $children = $semuaArusKas->filter(
                fn($n) => $n->sub == $node->id || $n->super_sub == $node->id
            );

            foreach ($children as $child) {
                $aggregate($child);
                $node->total += $child->total;
            }
        };

        $semuaArusKas->each(fn($node) => $aggregate($node));

        $result = collect();
        $curSection = null;
        $curGroup = null;

        foreach ($semuaArusKas->sortBy('id') as $node) {
            $isHeader = $node->sub == 0 && $node->super_sub != 0;
            $isSubHeader = $node->sub == 0 && $node->rekenings->isEmpty() && ! $isHeader;
            $isLeaf = ! $isHeader && ! $isSubHeader;

            if ($isHeader) {
                if ($curGroup !== null) {
                    $curSection['groups']->push($curGroup);
                    $curGroup = null;
                }
                if ($curSection !== null) {
                    $result->push($curSection);
                }
                $curSection = ['header' => $node, 'groups' => collect()];
            } elseif ($isSubHeader) {
                if ($curGroup !== null && $curSection !== null) {
                    $curSection['groups']->push($curGroup);
                }
                if ($curSection === null) {
                    $curSection = ['header' => null, 'groups' => collect()];
                }
                $curGroup = ['subheader' => $node, 'items' => collect()];
            } elseif ($isLeaf) {
                if ($curGroup === null) {
                    $curGroup = ['subheader' => null, 'items' => collect()];
                }
                $curGroup['items']->push($node);
            }
        }

        if ($curGroup !== null && $curSection !== null) {
            $curSection['groups']->push($curGroup);
        }
        if ($curSection !== null) {
            $result->push($curSection);
        }

        return $result;
    }
}
