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
            ],
            '2' => [
                'nama' => 'Pendapatan Lain Lain',
                'jumlah' => 0,
                'total' => 0,
            ],
            '3' => [
                'nama' => 'Beban Operasional',
                'jumlah' => 0,
                'total' => 0,
            ],
            '4' => [
                'nama' => 'Pendapatan Non Usaha',
                'jumlah' => 0,
                'total' => 0,
            ],
            '5' => [
                'nama' => 'Beban Non Usaha',
                'jumlah' => 0,
                'total' => 0,
            ],
            '6' => [
                'nama' => 'Beban Pajak',
                'jumlah' => 0,
                'total' => 0,
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
                            if ($kode == '4.1.01.04') {
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
            if ($key == '1.1.03.01') continue; // Skip raw inventory in summary list
            
            $child = [];
            $totalSaldo = 0;
            $totalPendapatan = 0;
            $totalBebanPokok = 0;

            foreach ($value['kode'] as $index => $kode) {
                $kode1 = explode('.', $kode['kode'])[0];
                
                // For Group 1 (Laba Kotor), we distinguish between Sales and HPP
                if ($key == '1') {
                    if ($kode1 == '4') {
                        $totalPendapatan += $kode['saldo_bulan_ini'];
                    } elseif ($kode1 == '5' || $kode['kode'] == '1.1.03.01') {
                        // Include HPP account and any inventory adjustments as COGS
                        $totalBebanPokok += abs($kode['saldo_bulan_ini']);
                    }
                }
                
                $child[] = $kode;
            }

            if ($key == '1') {
                $totalSaldo = $totalPendapatan - $totalBebanPokok;
                
                $child[] = [
                    'kode' => '',
                    'nama' => 'Penjualan Bersih',
                    'saldo_bulan_ini' => $totalPendapatan,
                    'saldo_bulan_lalu' => 0,
                    'saldo_tahun_lalu' => 0,
                ];
                $child[] = [
                    'kode' => '',
                    'nama' => 'Harga Pokok Penjualan (HPP)',
                    'saldo_bulan_ini' => $totalBebanPokok,
                    'saldo_bulan_lalu' => 0,
                    'saldo_tahun_lalu' => 0,
                ];
                $child[] = [
                    'kode' => '',
                    'nama' => 'Laba Kotor',
                    'saldo_bulan_ini' => $totalSaldo,
                    'saldo_bulan_lalu' => 0,
                    'saldo_tahun_lalu' => 0,
                ];
            } else if ($key > 1) {
                // For other groups (Operational expenses, etc)
                $totalSaldo = 0;
                foreach ($child as $ch) {
                    $totalSaldo += $ch['saldo_bulan_ini'];
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
