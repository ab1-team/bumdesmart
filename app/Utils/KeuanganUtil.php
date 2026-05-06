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
        $labaRugi = self::labaRugi($tahun, $bulan);
        if (isset($labaRugi['groups']) && isset($labaRugi['groups'][3]['total'])) {
            return (string) $labaRugi['groups'][3]['total'];
        }

        return '0';
    }

    public static function labaRugi($tahun, $bulan = '00'): array
    {
        $business_id = auth()->user()->business_id;
        $bulanInt = intval($bulan);

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

        $getM = function ($kode) use ($accounts, $bulan) {
            $acc = $accounts->get($kode);
            if (!$acc || !$acc->balance) return ['debit' => 0, 'kredit' => 0];
            $b = str_pad(intval($bulan), 2, '0', STR_PAD_LEFT);
            return [
                'debit' => (float)($acc->balance->{"debit_$b"} ?? 0),
                'kredit' => (float)($acc->balance->{"kredit_$b"} ?? 0)
            ];
        };

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
            ['kode' => '5.1.01.02', 'nama' => 'Diskon Pembelian', 'saldo_bulan_ini' => $diskonPembelian, 'saldo_bulan_lalu' => $getS('5.1.01.02', $bulanInt - 1), 'saldo_tahun_lalu' => $getS('5.1.01.02', '00')],
            ['kode' => '5.1.01.03', 'nama' => 'Retur Pembelian', 'saldo_bulan_ini' => $returPembelian, 'saldo_bulan_lalu' => $getS('5.1.01.03', $bulanInt - 1), 'saldo_tahun_lalu' => $getS('5.1.01.03', '00')],
            ['kode' => '5.1.01.04', 'nama' => 'Beban Produksi', 'saldo_bulan_ini' => $bebanProduksi, 'saldo_bulan_lalu' => $getS('5.1.01.04', $bulanInt - 1), 'saldo_tahun_lalu' => $getS('5.1.01.04', '00')],
            ['kode' => '5.1.01.05', 'nama' => 'Beban Transport Produk', 'saldo_bulan_ini' => $bebanTransport, 'saldo_bulan_lalu' => $getS('5.1.01.05', $bulanInt - 1), 'saldo_tahun_lalu' => $getS('5.1.01.05', '00')],
            ['kode' => '5.1.01.06', 'nama' => 'Cashback Pembelian', 'saldo_bulan_ini' => $cashbackPembelian, 'saldo_bulan_lalu' => $getS('5.1.01.06', $bulanInt - 1), 'saldo_tahun_lalu' => $getS('5.1.01.06', '00')],
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

        $group['1']['total'] = $group['1']['jumlah'];
        $group['3']['total'] = $group['2']['total'] + $group['3']['jumlah'];
        $group['4']['total'] = $group['3']['total'] + $group['4']['jumlah'];

        return [
            'groups' => array_values($group),
            'metrics' => [
                'margin_kotor' => $penjualanBersih > 0 ? ($labaKotor / $penjualanBersih) * 100 : 0,
                'margin_bersih' => $penjualanBersih > 0 ? ($group['4']['total'] / $penjualanBersih) * 100 : 0,
            ],
        ];
    }

    public static function arusKas($tahun, $bulan): array
    {
        $bulanInt = intval($bulan);
        $business_id = auth()->user()->business_id;

        $rootNodes = ArusKas::whereNull('parent_id')->with('children.rekenings')->get();

        $data = [];
        foreach ($rootNodes as $root) {
            $totalRoot = 0;
            $childrenData = [];

            foreach ($root->children as $child) {
                $totalChild = 0;
                $leafNodes = $child->children->count() > 0 ? $child->children : collect([$child]);

                foreach ($leafNodes as $arusKas) {
                    $saldo = 0;
                    foreach ($arusKas->rekenings as $rek) {
                        $payments = Payment::where('business_id', $business_id)
                            ->whereYear('tanggal', $tahun)
                            ->whereMonth('tanggal', $bulan)
                            ->where(function ($q) use ($rek) {
                                $q->where([
                                    ['rekening_debit', 'LIKE', $rek->rekening_debit],
                                    ['rekening_kredit', 'LIKE', $rek->rekening_kredit],
                                ])->orWhere([
                                    ['rekening_debit', 'LIKE', $rek->rekening_kredit],
                                    ['rekening_kredit', 'LIKE', $rek->rekening_debit],
                                ]);
                            })->get();

                        foreach ($payments as $p) {
                            if (str_starts_with($p->rekening_debit, explode('%', $rek->rekening_debit)[0])) {
                                $saldo += $p->nominal;
                            } else {
                                $saldo -= $p->nominal;
                            }
                        }
                    }
                    $totalChild += $saldo;
                }
                $totalRoot += $totalChild;
                $childrenData[] = [
                    'nama' => $child->nama,
                    'total' => $totalChild,
                ];
            }

            $data[] = [
                'nama' => $root->nama,
                'total' => $totalRoot,
                'children' => $childrenData,
            ];
        }

        return $data;
    }
}
