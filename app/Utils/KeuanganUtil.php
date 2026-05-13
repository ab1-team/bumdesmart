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

        $getS = function ($kode, $bln) use ($accounts) {
            $acc = $accounts->get($kode);
            if (!$acc) return 0;
            return (float)self::sumSaldo($acc, $bln);
        };

        $getV = function ($kode) use ($getS, $bulanInt) {
            $sd_lalu = $getS($kode, $bulanInt - 1);
            $sd_ini = $getS($kode, $bulanInt);
            return [
                'lalu' => $sd_lalu,
                'ini' => $sd_ini - $sd_lalu,
                'sd' => $sd_ini
            ];
        };

        // --- 1. LABA KOTOR SECTION ---
        $vPenjualan = $getV('4.1.01.01');
        $vDiskonPenj = $getV('4.1.01.02');
        $vReturPenj = $getV('4.1.01.03');
        $vCashbackPenj = $getV('4.1.01.06');

        $penjualanBersih = [
            'lalu' => $vPenjualan['lalu'] - $vDiskonPenj['lalu'] - $vReturPenj['lalu'] - $vCashbackPenj['lalu'],
            'ini' => $vPenjualan['ini'] - $vDiskonPenj['ini'] - $vReturPenj['ini'] - $vCashbackPenj['ini'],
            'sd' => $vPenjualan['sd'] - $vDiskonPenj['sd'] - $vReturPenj['sd'] - $vCashbackPenj['sd'],
        ];

        $vPersediaanAwal = [
            'lalu' => $getS('1.1.03.01', $bulanInt - 2), // SD month-2
            'ini' => $getS('1.1.03.01', $bulanInt - 1) - $getS('1.1.03.01', $bulanInt - 2),
            'sd' => $getS('1.1.03.01', $bulanInt - 1),
        ];
        
        $vPembelian = $getV('1.1.03.01');
        $vDiskonPemb = $getV('5.1.01.02');
        $vReturPemb = $getV('5.1.01.03');
        $vCashbackPemb = $getV('5.1.01.06');
        $vBebanProd = $getV('5.1.01.04');
        $vBebanTrans = $getV('5.1.01.05');

        $pembelianBersih = [
            'lalu' => $vPembelian['lalu'] - ($vDiskonPemb['lalu'] + $vReturPemb['lalu'] + $vCashbackPemb['lalu']) + $vBebanProd['lalu'] + $vBebanTrans['lalu'],
            'ini' => $vPembelian['ini'] - ($vDiskonPemb['ini'] + $vReturPemb['ini'] + $vCashbackPemb['ini']) + $vBebanProd['ini'] + $vBebanTrans['ini'],
            'sd' => $vPembelian['sd'] - ($vDiskonPemb['sd'] + $vReturPemb['sd'] + $vCashbackPemb['sd']) + $vBebanProd['sd'] + $vBebanTrans['sd'],
        ];

        $totalPersediaan = [
            'lalu' => $vPersediaanAwal['lalu'] + $pembelianBersih['lalu'],
            'ini' => $vPersediaanAwal['ini'] + $pembelianBersih['ini'],
            'sd' => $vPersediaanAwal['sd'] + $pembelianBersih['sd'],
        ];

        $vPersediaanAkhir = $getV('1.1.03.01');
        $hpp = [
            'lalu' => $totalPersediaan['lalu'] - $vPersediaanAkhir['lalu'],
            'ini' => $totalPersediaan['ini'] - $vPersediaanAkhir['ini'],
            'sd' => $totalPersediaan['sd'] - $vPersediaanAkhir['sd'],
        ];

        $group1_kode = [
            ['kode' => '4.1.01.01', 'nama' => 'Penjualan', 'saldo_sd_lalu' => $vPenjualan['lalu'], 'saldo_bulan_ini' => $vPenjualan['ini'], 'saldo_sd_ini' => $vPenjualan['sd']],
            ['kode' => '4.1.01.02', 'nama' => 'Diskon Penjualan', 'saldo_sd_lalu' => $vDiskonPenj['lalu'], 'saldo_bulan_ini' => $vDiskonPenj['ini'], 'saldo_sd_ini' => $vDiskonPenj['sd']],
            ['kode' => '4.1.01.03', 'nama' => 'Retur Penjualan', 'saldo_sd_lalu' => $vReturPenj['lalu'], 'saldo_bulan_ini' => $vReturPenj['ini'], 'saldo_sd_ini' => $vReturPenj['sd']],
            ['kode' => '4.1.01.06', 'nama' => 'Cashback Penjualan', 'saldo_sd_lalu' => $vCashbackPenj['lalu'], 'saldo_bulan_ini' => $vCashbackPenj['ini'], 'saldo_sd_ini' => $vCashbackPenj['sd']],
            ['kode' => '', 'nama' => 'Penjualan Bersih', 'saldo_sd_lalu' => $penjualanBersih['lalu'], 'saldo_bulan_ini' => $penjualanBersih['ini'], 'saldo_sd_ini' => $penjualanBersih['sd'], 'is_bold' => true],
        ];

        $group2_kode = [
            ['kode' => '', 'nama' => 'Persediaan Awal', 'saldo_sd_lalu' => $vPersediaanAwal['lalu'], 'saldo_bulan_ini' => $vPersediaanAwal['ini'], 'saldo_sd_ini' => $vPersediaanAwal['sd']],
            ['kode' => '1.1.03.01', 'nama' => 'Pembelian', 'saldo_sd_lalu' => $vPembelian['lalu'], 'saldo_bulan_ini' => $vPembelian['ini'], 'saldo_sd_ini' => $vPembelian['sd']],
            ['kode' => '5.1.01.02', 'nama' => 'Diskon Pembelian', 'saldo_sd_lalu' => $vDiskonPemb['lalu'], 'saldo_bulan_ini' => $vDiskonPemb['ini'], 'saldo_sd_ini' => $vDiskonPemb['sd']],
            ['kode' => '5.1.01.03', 'nama' => 'Retur Pembelian', 'saldo_sd_lalu' => $vReturPemb['lalu'], 'saldo_bulan_ini' => $vReturPemb['ini'], 'saldo_sd_ini' => $vReturPemb['sd']],
            ['kode' => '5.1.01.04', 'nama' => 'Beban Produksi', 'saldo_sd_lalu' => $vBebanProd['lalu'], 'saldo_bulan_ini' => $vBebanProd['ini'], 'saldo_sd_ini' => $vBebanProd['sd']],
            ['kode' => '5.1.01.05', 'nama' => 'Beban Transport Produk', 'saldo_sd_lalu' => $vBebanTrans['lalu'], 'saldo_bulan_ini' => $vBebanTrans['ini'], 'saldo_sd_ini' => $vBebanTrans['sd']],
            ['kode' => '5.1.01.06', 'nama' => 'Cashback Pembelian', 'saldo_sd_lalu' => $vCashbackPemb['lalu'], 'saldo_bulan_ini' => $vCashbackPemb['ini'], 'saldo_sd_ini' => $vCashbackPemb['sd']],
            ['kode' => '', 'nama' => 'Total Pembelian', 'saldo_sd_lalu' => $pembelianBersih['lalu'], 'saldo_bulan_ini' => $pembelianBersih['ini'], 'saldo_sd_ini' => $pembelianBersih['sd'], 'is_bold' => true],
            ['kode' => '', 'nama' => 'Total Persediaan', 'saldo_sd_lalu' => $totalPersediaan['lalu'], 'saldo_bulan_ini' => $totalPersediaan['ini'], 'saldo_sd_ini' => $totalPersediaan['sd'], 'is_bold' => true],
            ['kode' => '', 'nama' => 'Persediaan Akhir', 'saldo_sd_lalu' => $vPersediaanAkhir['lalu'], 'saldo_bulan_ini' => $vPersediaanAkhir['ini'], 'saldo_sd_ini' => $vPersediaanAkhir['sd']],
            ['kode' => '', 'nama' => 'Harga Pokok Penjualan', 'saldo_sd_lalu' => $hpp['lalu'], 'saldo_bulan_ini' => $hpp['ini'], 'saldo_sd_ini' => $hpp['sd'], 'is_bold' => true],
        ];

        $group = [
            '1' => [
                'nama' => 'Pendapatan',
                'saldo_sd_lalu' => $penjualanBersih['lalu'],
                'saldo_bulan_ini' => $penjualanBersih['ini'],
                'saldo_sd_ini' => $penjualanBersih['sd'],
                'kode' => $group1_kode,
            ],
            '2' => [
                'nama' => 'Beban',
                'saldo_sd_lalu' => $hpp['lalu'],
                'saldo_bulan_ini' => $hpp['ini'],
                'saldo_sd_ini' => $hpp['sd'],
                'kode' => $group2_kode,
            ],
            '3' => [
                'nama' => 'Beban',
                'saldo_sd_lalu' => 0,
                'saldo_bulan_ini' => 0,
                'saldo_sd_ini' => 0,
                'kode' => [],
            ],
            '4' => [
                'nama' => 'Pajak',
                'saldo_sd_lalu' => 0,
                'saldo_bulan_ini' => 0,
                'saldo_sd_ini' => 0,
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

            $vals = $getV($kode);
            $saldoData = [
                'kode' => $kode,
                'nama' => $account->nama,
                'saldo_sd_lalu' => $vals['lalu'],
                'saldo_bulan_ini' => $vals['ini'],
                'saldo_sd_ini' => $vals['sd'],
            ];

            if ($kode1 == '4') { // Other Income
                 $group['1']['kode'][] = $saldoData;
                 $group['1']['saldo_sd_lalu'] += $vals['lalu'];
                 $group['1']['saldo_bulan_ini'] += $vals['ini'];
                 $group['1']['saldo_sd_ini'] += $vals['sd'];
            } elseif (in_array($kode1, ['5', '6', '7']) && ($kode1 != '7' || $kode2 != '4')) { // Other Expenses
                 $group['3']['kode'][] = $saldoData;
                 $group['3']['saldo_sd_lalu'] -= $vals['lalu'];
                 $group['3']['saldo_bulan_ini'] -= $vals['ini'];
                 $group['3']['saldo_sd_ini'] -= $vals['sd'];
            } elseif ($kode1 == '7' && $kode2 == '4') { // Tax
                 $group['4']['kode'][] = $saldoData;
                 $group['4']['saldo_sd_lalu'] -= $vals['lalu'];
                 $group['4']['saldo_bulan_ini'] -= $vals['ini'];
                 $group['4']['saldo_sd_ini'] -= $vals['sd'];
            }
        }

        // Calculate Totals and Labas
        $resGroup = [];
        
        // Pendapatan
        $resGroup[0] = $group['1'];
        $resGroup[0]['jumlah_sd_lalu'] = $group['1']['saldo_sd_lalu'];
        $resGroup[0]['jumlah_bulan_ini'] = $group['1']['saldo_bulan_ini'];
        $resGroup[0]['jumlah_sd_ini'] = $group['1']['saldo_sd_ini'];
        $resGroup[0]['total_sd_lalu'] = $group['1']['saldo_sd_lalu'];
        $resGroup[0]['total_bulan_ini'] = $group['1']['saldo_bulan_ini'];
        $resGroup[0]['total_sd_ini'] = $group['1']['saldo_sd_ini'];

        // Beban (HPP) -> LABA KOTOR
        $resGroup[1] = $group['2'];
        $resGroup[1]['jumlah_sd_lalu'] = $group['2']['saldo_sd_lalu'];
        $resGroup[1]['jumlah_bulan_ini'] = $group['2']['saldo_bulan_ini'];
        $resGroup[1]['jumlah_sd_ini'] = $group['2']['saldo_sd_ini'];
        $resGroup[1]['total_sd_lalu'] = $resGroup[0]['total_sd_lalu'] - $group['2']['saldo_sd_lalu'];
        $resGroup[1]['total_bulan_ini'] = $resGroup[0]['total_bulan_ini'] - $group['2']['saldo_bulan_ini'];
        $resGroup[1]['total_sd_ini'] = $resGroup[0]['total_sd_ini'] - $group['2']['saldo_sd_ini'];

        // Beban Lainnya -> LABA SEBELUM PAJAK
        $resGroup[2] = $group['3'];
        $resGroup[2]['jumlah_sd_lalu'] = $group['3']['saldo_sd_lalu'];
        $resGroup[2]['jumlah_bulan_ini'] = $group['3']['saldo_bulan_ini'];
        $resGroup[2]['jumlah_sd_ini'] = $group['3']['saldo_sd_ini'];
        $resGroup[2]['total_sd_lalu'] = $resGroup[1]['total_sd_lalu'] + $group['3']['saldo_sd_lalu'];
        $resGroup[2]['total_bulan_ini'] = $resGroup[1]['total_bulan_ini'] + $group['3']['saldo_bulan_ini'];
        $resGroup[2]['total_sd_ini'] = $resGroup[1]['total_sd_ini'] + $group['3']['saldo_sd_ini'];

        // Pajak -> LABA BERSIH
        $resGroup[3] = $group['4'];
        $resGroup[3]['jumlah_sd_lalu'] = $group['4']['saldo_sd_lalu'];
        $resGroup[3]['jumlah_bulan_ini'] = $group['4']['saldo_bulan_ini'];
        $resGroup[3]['jumlah_sd_ini'] = $group['4']['saldo_sd_ini'];
        $resGroup[3]['total_sd_lalu'] = $resGroup[2]['total_sd_lalu'] + $group['4']['saldo_sd_lalu'];
        $resGroup[3]['total_bulan_ini'] = $resGroup[2]['total_bulan_ini'] + $group['4']['saldo_bulan_ini'];
        $resGroup[3]['total_sd_ini'] = $resGroup[2]['total_sd_ini'] + $group['4']['saldo_sd_ini'];

        return [
            'groups' => $resGroup,
            'metrics' => [
                'margin_kotor' => $penjualanBersih['sd'] > 0 ? ($resGroup[1]['total_sd_ini'] / $penjualanBersih['sd']) * 100 : 0,
                'margin_bersih' => $penjualanBersih['sd'] > 0 ? ($resGroup[3]['total_sd_ini'] / $penjualanBersih['sd']) * 100 : 0,
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
