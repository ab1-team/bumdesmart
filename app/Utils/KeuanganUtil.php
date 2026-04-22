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
        $akunLevel1s = AkunLevel1::where([
            ['id', '>=', '4'],
        ])->with([
            'akunLevel2.akunLevel3.accounts' => function ($query) {
                $query->where('business_id', auth()->user()->business_id);
            },
            'akunLevel2.akunLevel3.accounts.balance' => function ($query) use ($tahun) {
                $query->where('tahun', $tahun);
            },
        ])->get();

        $akunPersediaan = Account::where([
            ['business_id', auth()->user()->business_id],
            ['kode', '1.1.03.01'],
        ])->with([
            'balance' => function ($query) use ($tahun) {
                $query->where('tahun', $tahun);
            },
        ])->first();

        $group = [
            '1' => [
                'nama' => 'Laba Kotor',
                'jumlah' => 0,
                'total' => 0,
                'kode' => [],
            ],
            '2' => [
                'nama' => 'Pendapatan Lain Lain',
                'jumlah' => 0,
                'total' => 0,
                'kode' => [],
            ],
            '3' => [
                'nama' => 'Beban Operasional',
                'jumlah' => 0,
                'total' => 0,
                'kode' => [],
            ],
            '4' => [
                'nama' => 'Pendapatan Non Usaha',
                'jumlah' => 0,
                'total' => 0,
                'kode' => [],
            ],
            '5' => [
                'nama' => 'Beban Non Usaha',
                'jumlah' => 0,
                'total' => 0,
                'kode' => [],
            ],
            '6' => [
                'nama' => 'Beban Pajak',
                'jumlah' => 0,
                'total' => 0,
                'kode' => [],
            ],
        ];

        $group[$akunPersediaan->kode] = [
            'kode' => $akunPersediaan->kode,
            'nama' => $akunPersediaan->nama,
            'saldo_bulan_ini' => self::sumSaldo($akunPersediaan, $bulan),
            'saldo_bulan_lalu' => self::sumSaldo($akunPersediaan, $bulan - 1),
            'saldo_tahun_lalu' => self::sumSaldo($akunPersediaan, '00'),
        ];

        foreach ($akunLevel1s as $akunLevel1) {
            foreach ($akunLevel1->akunLevel2 as $akunLevel2) {
                foreach ($akunLevel2->akunLevel3 as $akunLevel3) {
                    foreach ($akunLevel3->accounts as $account) {
                        $kode = $account->kode;
                        $kode1 = explode('.', $account->kode)[0];
                        $kode2 = explode('.', $account->kode)[1];
                        $kode3 = explode('.', $account->kode)[2];
                        $kode4 = explode('.', $account->kode)[3];

                        $saldo_bulan_ini = self::sumSaldo($account, $bulan);
                        $saldo_bulan_lalu = self::sumSaldo($account, $bulan - 1);
                        $saldo_tahun_lalu = self::sumSaldo($account, '00');

                        $saldo = [
                            'kode' => $account->kode,
                            'nama' => $account->nama,
                            'saldo_bulan_ini' => $saldo_bulan_ini,
                            'saldo_bulan_lalu' => $saldo_bulan_lalu,
                            'saldo_tahun_lalu' => $saldo_tahun_lalu,
                        ];

                        if ($kode1 <= '5' && $kode != '4.1.01.05') {
                            if ($kode == '4.1.01.04' || $kode == '5.1.01.01') {
                                continue;
                            }

                            if ($kode == '5.1.01.02') {
                                $group['1']['kode'][] = $group['1.1.03.01'];
                                unset($group['1.1.03.01']);
                            }

                            $group['1']['kode'][] = $saldo;
                        }

                        if ($kode1 == '6') {
                            $group['3']['kode'][] = $saldo;
                        }

                        if ($kode1 == '7' && $kode2 <= '2') {
                            $group['4']['kode'][] = $saldo;
                        }

                        if ($kode1 == '7' && $kode2 == '3') {
                            $group['5']['kode'][] = $saldo;
                        }

                        if ($kode1 == '7' && $kode2 == '4') {
                            $group['6']['kode'][] = $saldo;
                        }

                        if ($kode == '4.1.01.05') {
                            $group['2']['kode'][] = $saldo;
                        }
                    }
                }
            }
        }

        $labaRugi = [];
        foreach ($group as $key => $value) {

            $child = [];
            $kelompokAkun = [];
            $penjualanBersihBulanIni = 0;
            $totalSaldo = 0;
            foreach ($value['kode'] as $index => $kode) {
                if ($kode['kode'] == '1.1.03.01') {
                    $saldoPenjualanBersih = [
                        'saldo_bulan_ini' => '0',
                        'saldo_bulan_lalu' => '0',
                        'saldo_tahun_lalu' => '0',
                    ];
                    foreach ($child as $ch) {
                        $saldoPenjualanBersih['saldo_bulan_ini'] += $ch['saldo_bulan_ini'];
                        $saldoPenjualanBersih['saldo_bulan_lalu'] += $ch['saldo_bulan_lalu'];
                        $saldoPenjualanBersih['saldo_tahun_lalu'] += $ch['saldo_tahun_lalu'];
                    }

                    $penjualanBersihBulanIni = $saldoPenjualanBersih['saldo_bulan_ini'];
                    $child[] = [
                        'kode' => '',
                        'nama' => 'Penjualan Bersih',
                        'saldo_bulan_ini' => $saldoPenjualanBersih['saldo_bulan_ini'],
                        'saldo_bulan_lalu' => $saldoPenjualanBersih['saldo_bulan_lalu'],
                        'saldo_tahun_lalu' => $saldoPenjualanBersih['saldo_tahun_lalu'],
                    ];

                    $kelompokAkun = [];
                    $persediaanAwal = [
                        'kode' => '',
                        'nama' => 'Persediaan Awal',
                        'saldo_bulan_ini' => $kode['saldo_bulan_lalu'],
                        'saldo_bulan_lalu' => '0',
                        'saldo_tahun_lalu' => '0',
                    ];

                    $kode['saldo_bulan_ini'] -= $kode['saldo_bulan_lalu'];

                    $child[] = $persediaanAwal;
                    $kelompokAkun[] = $persediaanAwal;
                }

                $child[] = $kode;
                $kelompokAkun[] = $kode;

                if ($kode['kode'] == '5.1.01.06') {

                    $persediaanAwalBulanIni = 0;
                    $returPembelian = 0;
                    $totalPembelian = 0;
                    $persediaan = 0;
                    foreach ($kelompokAkun as $kelompok) {
                        if ($kelompok['kode'] == '' || $kelompok['kode'] == '5.1.01.03') {
                            if ($kelompok['kode'] == '') {
                                $persediaanAwalBulanIni += $kelompok['saldo_bulan_ini'];
                            }

                            if ($kelompok['kode'] == '5.1.01.03') {
                                $returPembelian += $kelompok['saldo_bulan_ini'];
                            }

                            continue;
                        }

                        if ($kelompok['kode'] == '1.1.03.01') {
                            $persediaan += $kelompok['saldo_bulan_ini'];
                            continue; // FIX: Don't add inventory change to "Total Pembelian"
                        }

                        $totalPembelian += $kelompok['saldo_bulan_ini'];
                    }

                    $child[] = [
                        'kode' => '',
                        'nama' => 'Total Pembelian',
                        'saldo_bulan_ini' => $totalPembelian,
                        'saldo_bulan_lalu' => '0',
                        'saldo_tahun_lalu' => '0',
                    ];

                    $child[] = [
                        'kode' => '',
                        'nama' => 'Total Persediaan',
                        'saldo_bulan_ini' => $totalPembelian + $persediaanAwalBulanIni,
                        'saldo_bulan_lalu' => '0',
                        'saldo_tahun_lalu' => '0',
                    ];

                    $child[] = [
                        'kode' => '',
                        'nama' => 'Persediaan Akhir',
                        'saldo_bulan_ini' => $persediaanAwalBulanIni + $persediaan + $returPembelian,
                        'saldo_bulan_lalu' => '0',
                        'saldo_tahun_lalu' => '0',
                    ];

                    $hpp = (($totalPembelian + $persediaanAwalBulanIni) - ($persediaanAwalBulanIni + $persediaan + $returPembelian));
                    $child[] = [
                        'kode' => '',
                        'nama' => 'Harga Pokok Penjualan',
                        'saldo_bulan_ini' => $hpp,
                        'saldo_bulan_lalu' => '0',
                        'saldo_tahun_lalu' => '0',
                    ];

                    $child[] = [
                        'kode' => '',
                        'nama' => 'Laba Kotor',
                        'saldo_bulan_ini' => $penjualanBersihBulanIni - $hpp,
                        'saldo_bulan_lalu' => '0',
                        'saldo_tahun_lalu' => '0',
                    ];

                    $totalSaldo += ($penjualanBersihBulanIni - $hpp);
                }

                if ($key > 1) {
                    $totalSaldo += $kode['saldo_bulan_ini'];
                }
            }

            $group[$key]['jumlah'] = $totalSaldo;
            $group[$key]['total'] = $totalSaldo;
            if ($key > 1) {
                $group[$key]['total'] += $group[$key - 1]['total'];
            }

            $group[$key]['kode'] = $child;
            $labaRugi[] = $group[$key];
        }

        return $labaRugi;
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
