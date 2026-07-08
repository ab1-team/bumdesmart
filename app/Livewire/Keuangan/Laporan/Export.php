<?php

namespace App\Livewire\Keuangan\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AkunLevel1;
use App\Models\Business;
use App\Models\cashDrawer;
use App\Models\Inventory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchasesReturn;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SalesReturn;
use App\Models\StockOpname;
use App\Utils\InventarisUtil;
use App\Utils\KeuanganUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class Export extends Controller
{
    private function rupiah($amount): string
    {
        return number_format((float) $amount, 2, '.', ',');
    }

    public function __invoke(Request $request)
    {
        $data = $request->all();

        if (! isset($data['laporan']) || ! method_exists($this, $data['laporan'])) {
            abort(404, 'Laporan tidak ditemukan');
        }

        $owner = tenant();

        if ($owner) {
            $business = Business::where('owner_id', $owner->id)->first();
        } else {
            $business = Business::find(auth()->user()?->business_id) ?? Business::first();
        }

        view()->share('business', $business);

        return $this->{$data['laporan']}($data);
    }

    private function periodeSubtitle(string $tahun, string $bulan, ?string $hari = null): string
    {
        $parts = [];
        if ($bulan != '-') {
            try {
                $parts[] = Carbon::createFromDate((int) $tahun, (int) $bulan, 1)->isoFormat('MMMM');
            } catch (\Throwable $e) {
                $parts[] = $bulan;
            }
        }
        $parts[] = $tahun;
        $sub = 'Periode: '.implode(' ', $parts);
        if ($hari !== null && $hari != '-') {
            $sub .= ' | Tanggal: '.$hari;
        }
        return $sub;
    }

    private function buildExcel(string $title, string $subtitle, array $headers, array $rows, array $totalsRow, string $filename, array $numberCols = [], array $columnWidths = [])
    {
        return $this->buildGroupedExcel($title, $subtitle, [], [
            ['title' => null, 'headers' => $headers, 'rows' => $rows, 'subtotals' => !empty($totalsRow) ? [$totalsRow] : []],
        ], $filename, $numberCols, $columnWidths);
    }

    private function buildGroupedExcel(string $title, string $subtitle, array $summaryRow, array $groups, string $filename, array $numberCols = [], array $columnWidths = [])
    {
        $maxCols = 1;
        foreach ($groups as $g) {
            $maxCols = max($maxCols, count($g['headers']));
        }

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= '<head><meta charset="UTF-8">';
        $html .= '<style>';
        $html .= 'table { border-collapse: collapse; }';
        $html .= 'td, th { border: 1px solid #CCCCCC; padding: 4px 8px; }';
        $html .= '.header { background-color: #F0F0F0; font-weight: bold; }';
        $html .= '.section { background-color: #F0F0F0; font-weight: bold; }';
        $html .= '.subtotal { background-color: #F0F0F0; font-weight: bold; }';
        $html .= '.title { font-size: 16pt; font-weight: bold; text-align: center; }';
        $html .= '.subtitle { text-align: center; }';
        $html .= '</style></head><body>';

        $html .= '<table>';

        $html .= '<tr><td colspan="'.$maxCols.'" class="title">'.e($title).'</td></tr>';
        $html .= '<tr><td colspan="'.$maxCols.'" class="subtitle">'.e($subtitle).'</td></tr>';

        if (!empty($summaryRow)) {
            foreach ($summaryRow as $sRow) {
                $html .= '<tr>';
                for ($i = 0; $i < $maxCols; $i++) {
                    $val = $sRow[$i] ?? '';
                    $html .= '<td'.(is_numeric($val) ? ' style="mso-number-format:\'\#\#\#\.\#\#0\'"' : '').'>'.e($val).'</td>';
                }
                $html .= '</tr>';
            }
        }

        foreach ($groups as $group) {
            $headers = $group['headers'];
            $groupRows = $group['rows'];
            $groupTitle = $group['title'] ?? null;
            $subtotals = $group['subtotals'] ?? [];
            $gc = count($headers);

            if ($groupTitle !== null && $groupTitle !== '') {
                $html .= '<tr><td colspan="'.$gc.'" class="section">'.e($groupTitle).'</td></tr>';
            }

            $html .= '<tr>';
            foreach ($headers as $h) {
                $html .= '<th class="header">'.e($h).'</th>';
            }
            $html .= '</tr>';

            foreach ($groupRows as $r) {
                $html .= '<tr>';
                for ($i = 0; $i < $gc; $i++) {
                    $val = $r[$i] ?? '';
                    $html .= '<td'.(is_numeric($val) ? ' style="mso-number-format:\'\#\#\#\.\#\#0\'"' : '').'>'.e($val).'</td>';
                }
                $html .= '</tr>';
            }

            foreach ($subtotals as $st) {
                $html .= '<tr>';
                for ($i = 0; $i < $gc; $i++) {
                    $val = $st[$i] ?? '';
                    $html .= '<td class="subtotal">'.e($val).'</td>';
                }
                $html .= '</tr>';
            }
        }

        $html .= '</table></body></html>';

        $filename = str_replace('.xlsx', '.xls', $filename);

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function penjualanHarian(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? '-';
        $hari = $data['periode'] ?? '-';

        $query = Sale::with(['customer', 'payments', 'user'])
            ->whereYear('tanggal_transaksi', $tahun);
        if ($bulan != '-') $query->whereMonth('tanggal_transaksi', $bulan);
        if ($hari != '-') $query->whereDay('tanggal_transaksi', $hari);

        if (isset($data['sub_laporan']) && $data['sub_laporan'] != '') {
            if (str_contains($data['sub_laporan'], ':')) {
                [$type, $id] = explode(':', $data['sub_laporan']);
                if ($type === 'user') $query->where('user_id', $id);
                elseif ($type === 'cat') {
                    $query->whereHas('saleDetails.product', function ($q) use ($id) {
                        $q->where('category_id', $id);
                    });
                } elseif ($type === 'cus') {
                    $query->where('customer_id', $id);
                }
            } else {
                $query->where('user_id', $data['sub_laporan']);
            }
        }

        $sales = $query->orderBy('tanggal_transaksi', 'desc')->get();

        $summary = [
            'total_transactions' => $sales->count(),
            'total_sales' => $sales->sum('total'),
            'avg_transaction' => $sales->count() > 0 ? $sales->avg('total') : 0,
        ];

        $groups = [
            'Cash' => ['items' => [], 'total' => 0],
            'Transfer/Qris' => ['items' => [], 'total' => 0],
            'Piutang' => ['items' => [], 'total' => 0],
        ];

        foreach ($sales as $sale) {
            $dibayar = (float) $sale->dibayar;
            $utang = (float) $sale->jumlah_utang;

            if ($dibayar > 0) {
                $metode = 'tunai';
                $payment = $sale->payments->whereIn('metode_pembayaran', ['tunai', 'transfer', 'qris', 'cash'])->first();
                if ($payment) $metode = $payment->metode_pembayaran;

                if (in_array(strtolower($metode), ['transfer', 'qris'])) {
                    $groups['Transfer/Qris']['items'][] = ['sale' => $sale, 'amount' => $dibayar, 'metode' => $metode];
                    $groups['Transfer/Qris']['total'] += $dibayar;
                } else {
                    $groups['Cash']['items'][] = ['sale' => $sale, 'amount' => $dibayar, 'metode' => 'Cash'];
                    $groups['Cash']['total'] += $dibayar;
                }
            }

            if ($utang > 0) {
                $groups['Piutang']['items'][] = ['sale' => $sale, 'amount' => $utang, 'metode' => 'Piutang'];
                $groups['Piutang']['total'] += $utang;
            }
        }

        $title = 'Laporan Penjualan Harian';
        $subtitle = $this->periodeSubtitle($tahun, $bulan, $hari);

        $summaryRow = [
            ['Rangkuman', '', ''],
            ['Total Penjualan', 'Jumlah Transaksi', 'Rata-rata'],
            [$this->rupiah($summary['total_sales']), $summary['total_transactions'], $this->rupiah($summary['avg_transaction'])],
        ];

        $tableHeaders = ['No', 'No. Invoice', 'Waktu', 'Pelanggan', 'Pembayaran', 'Inisial', 'Nominal', 'Status'];
        $excelGroups = [];
        foreach ($groups as $groupName => $groupData) {
            if (count($groupData['items']) === 0) continue;
            $rows = [];
            foreach ($groupData['items'] as $index => $item) {
                $rows[] = [
                    $index + 1,
                    $item['sale']->no_invoice ?? '-',
                    $item['sale']->tanggal_transaksi,
                    $item['sale']->customer->nama_pelanggan ?? 'Guest',
                    ucfirst($item['metode']),
                    $item['sale']->user->initial ?? '-',
                    $this->rupiah($item['amount']),
                    ucfirst($item['sale']->status ?? 'paid'),
                ];
            }
            $excelGroups[] = [
                'title' => $groupName,
                'headers' => $tableHeaders,
                'rows' => $rows,
                'subtotals' => [
                    array_merge(array_fill(0, 5, ''), ['', 'Total '.$groupName, $this->rupiah($groupData['total'])]),
                ],
            ];
        }

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            $summaryRow,
            $excelGroups,
            'laporan-penjualan-harian.xlsx',
            [7],
            [5, 22, 20, 22, 14, 10, 16, 12]
        );
    }

    public function stokMinimum(array $data)
    {
        $query = Product::with('category')
            ->whereColumn('stok_aktual', '<=', 'stok_minimal')
            ->where('is_active', true);
        if (isset($data['sub_laporan']) && str_starts_with($data['sub_laporan'], 'cat:')) {
            $catId = str_replace('cat:', '', $data['sub_laporan']);
            $query->where('category_id', $catId);
        }
        $products = $query->get()
            ->map(function ($p) {
                $p->kekurangan = $p->stok_minimal - $p->stok_aktual;
                $p->suggested_order = ($p->stok_minimal * 2) - $p->stok_aktual;
                return $p;
            })->sortByDesc('kekurangan');

        $headers = ['No', 'Kode Produk', 'Nama Produk', 'Stok Saat Ini', 'Stok Minimum', 'Defisit', 'Saran Order'];
        $rows = [];
        foreach ($products as $i => $p) {
            $rows[] = [
                $i + 1,
                $p->sku ?? ($p->product_code ?? '-'),
                $p->nama_produk ?? $p->product_name,
                (int) $p->stok_aktual,
                (int) $p->stok_minimal,
                (int) $p->kekurangan,
                (int) $p->suggested_order,
            ];
        }

        return $this->buildExcel(
            'Laporan Stok Minimum',
            'Periode: '.Carbon::now()->isoFormat('MMMM Y'),
            $headers,
            $rows,
            [],
            'laporan-stok-minimum.xlsx',
            [],
            [5, 14, 32, 14, 14, 14, 14]
        );
    }

    public function jurnalTransaksi(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $payments = Payment::where('business_id', auth()->user()->business_id)
            ->where('tanggal_pembayaran', 'LIKE', $tahun.'-'.$bulan.'-%')
            ->with(['accountDebit', 'accountKredit', 'user'])->get();

        $title = 'Jurnal Transaksi';
        $subtitle = $this->periodeSubtitle($tahun, $bulan);

        $headers = ['No', 'Tanggal', 'Ref ID.', 'Kode Akun', 'Keterangan', 'Debit', 'Kredit', 'Ins'];
        $rows = [];
        $totalDebit = 0;
        $totalKredit = 0;
        foreach ($payments as $index => $payment) {
            $totalDebit += (float) $payment->total_harga;
            $totalKredit += (float) $payment->total_harga;

            $rows[] = [
                $index + 1,
                Carbon::parse($payment->tanggal_pembayaran)->format('Y-m-d'),
                $payment->transaction_id ?? '',
                $payment->rekening_debit ?? '',
                $payment->accountDebit->nama ?? '',
                $this->rupiah($payment->total_harga),
                '0',
                $payment->user->initial ?? '',
            ];
            $rows[] = [
                '',
                '',
                '',
                $payment->rekening_kredit ?? '',
                $payment->accountKredit->nama ?? '',
                '0',
                $this->rupiah($payment->total_harga),
                '',
            ];
        }
        $totalsRow = ['', '', '', '', 'Total', $this->rupiah($totalDebit), $this->rupiah($totalKredit), ''];

        return $this->buildExcel(
            $title,
            $subtitle,
            $headers,
            $rows,
            $totalsRow,
            'laporan-jurnal-transaksi.xlsx',
            [],
            [5, 14, 12, 14, 36, 16, 16, 5]
        );
    }

    public function bukuBesar(array $data)
    {
        $business = view()->shared('business');
        $kodeAkun = $data['sub_laporan'] ?? null;
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        if (! $kodeAkun) {
            abort(404, 'Sub laporan (kode akun) wajib dipilih');
        }

        $akun = Account::where('kode', $kodeAkun)->with([
            'balance' => function ($query) use ($business, $tahun) {
                $query->where('business_id', $business->id)->where('tahun', $tahun);
            },
        ])->first();

        $payments = Payment::where([
            ['business_id', auth()->user()->business_id],
            ['tanggal_pembayaran', 'LIKE', $tahun.'-'.$bulan.'-%'],
        ])->where(function ($q) use ($kodeAkun) {
            $q->where('rekening_debit', $kodeAkun)->orWhere('rekening_kredit', $kodeAkun);
        })->orderBy('tanggal_pembayaran', 'asc')->orderBy('id', 'asc')->get();

        $saldoAwalDebit = $akun->balance->debit_00 ?? 0;
        $saldoAwalKredit = $akun->balance->kredit_00 ?? 0;

        if ($akun->jenis_mutasi == 'debit') {
            $saldoAwal = $saldoAwalDebit - $saldoAwalKredit;
        } else {
            $saldoAwal = $saldoAwalKredit - $saldoAwalDebit;
        }

        $saldoBulanLaluDebit = 0;
        $saldoBulanLaluKredit = 0;
        for ($i = 1; $i < $bulan; $i++) {
            $colDebit = 'debit_'.str_pad($i, 2, '0', STR_PAD_LEFT);
            $colKredit = 'kredit_'.str_pad($i, 2, '0', STR_PAD_LEFT);
            $saldoBulanLaluDebit += $akun->balance->$colDebit ?? 0;
            $saldoBulanLaluKredit += $akun->balance->$colKredit ?? 0;
        }

        if ($akun->jenis_mutasi == 'debit') {
            $saldoBulanLalu = $saldoBulanLaluDebit - $saldoBulanLaluKredit;
        } else {
            $saldoBulanLalu = $saldoBulanLaluKredit - $saldoBulanLaluDebit;
        }

        $totalDebit = 0;
        $totalKredit = 0;
        $totalSaldo = $saldoAwal + $saldoBulanLalu;

        $title = 'Buku Besar '.($akun->nama ?? '');
        $subtitle = 'Kode Akun: '.$kodeAkun.' | '.$this->periodeSubtitle($tahun, $bulan);

        $headers = ['No', 'Tanggal', 'Ref', 'Keterangan', 'Debit', 'Kredit', 'Saldo', 'P'];
        $rows = [];

        $rows[] = [
            '',
            $tahun.'-01-01',
            '',
            'Komulatif Transaksi Awal Tahun '.$tahun,
            $this->rupiah($saldoAwalDebit),
            $this->rupiah($saldoAwalKredit),
            $this->rupiah($saldoAwal),
            '',
        ];
        $rows[] = [
            '',
            $tahun.'-'.$bulan.'-01',
            '',
            'Komulatif Transaksi s/d Bulan Lalu',
            $this->rupiah($saldoBulanLaluDebit),
            $this->rupiah($saldoBulanLaluKredit),
            $this->rupiah($totalSaldo),
            '',
        ];

        foreach ($payments as $index => $payment) {
            $debit = 0;
            $kredit = 0;

            if ($payment->rekening_debit == $kodeAkun) {
                $debit = (float) $payment->total_harga;
            }
            if ($payment->rekening_kredit == $kodeAkun) {
                $kredit = (float) $payment->total_harga;
            }

            if ($akun->jenis_mutasi == 'debit') {
                $saldo = $debit - $kredit;
            } else {
                $saldo = $kredit - $debit;
            }

            $totalDebit += $debit;
            $totalKredit += $kredit;
            $totalSaldo += $saldo;

            $rows[] = [
                $index + 1,
                Carbon::parse($payment->tanggal_pembayaran)->format('Y-m-d'),
                $payment->id ?? '',
                $payment->catatan ?? '-',
                $debit > 0 ? $this->rupiah($debit) : '',
                $kredit > 0 ? $this->rupiah($kredit) : '',
                $this->rupiah($totalSaldo),
                $payment->p ?? '',
            ];
        }

        $namaBulan = '';
        try {
            $namaBulan = Carbon::createFromDate((int) $tahun, (int) $bulan, 1)->isoFormat('MMMM');
        } catch (\Throwable $e) {
            $namaBulan = $bulan;
        }

        $subtotals = [
            [
                '', '', '', 'Total Transaksi Bulan '.$namaBulan,
                $this->rupiah($totalDebit), $this->rupiah($totalKredit), $this->rupiah($totalSaldo), '',
            ],
            [
                '', '', '', 'Total Transaksi Sampai Dengan Bulan '.$namaBulan,
                $this->rupiah($totalDebit + $saldoBulanLaluDebit), $this->rupiah($totalKredit + $saldoBulanLaluKredit), '', '',
            ],
            [
                '', '', '', 'Total Transaksi Komulatif Sampai Dengan '.$tahun,
                $this->rupiah($totalDebit + $saldoBulanLaluDebit + $saldoAwalDebit), $this->rupiah($totalKredit + $saldoBulanLaluKredit + $saldoAwalKredit), '', '',
            ],
        ];

        $excelGroups = [
            [
                'title' => null,
                'headers' => $headers,
                'rows' => $rows,
                'subtotals' => $subtotals,
            ],
        ];

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            [],
            $excelGroups,
            'laporan-buku-besar-'.str_replace('.', '_', $kodeAkun).'.xlsx',
            [5, 6, 7],
            [5, 14, 10, 44, 16, 16, 18, 5]
        );
    }

    public function neraca(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $akunLevel1s = AkunLevel1::with([
            'akunLevel2.akunLevel3.accounts' => function ($query) use ($business) {
                $query->where('business_id', $business->id);
            },
            'akunLevel2.akunLevel3.accounts.balance' => function ($query) use ($business, $tahun) {
                $query->where('business_id', $business->id)->where('tahun', $tahun);
            },
        ])->where('id', '<=', '3')->get();

        $title = 'Laporan Neraca';
        $subtitle = $this->periodeSubtitle($tahun, $bulan);

        $headers = ['Kode', 'Nama Akun', 'Saldo'];
        $saldoAkunLevel1 = [];
        $totalLiabilitasEkuitas = 0;

        $excelGroups = [];
        foreach ($akunLevel1s as $akunLevel1) {
            $groupRows = [];
            $saldoAkunLevel1[$akunLevel1->id] = 0;

            foreach ($akunLevel1->akunLevel2 as $akunLevel2) {
                // Add akunLevel2 header row
                $groupRows[] = [
                    $akunLevel2->kode.'.',
                    $akunLevel2->nama,
                    '',
                ];

                foreach ($akunLevel2->akunLevel3 as $akunLevel3) {
                    $saldoAkun = 0;
                    foreach ($akunLevel3->accounts as $account) {
                        $saldo = KeuanganUtil::sumSaldo($account, (int) $bulan);
                        if ($account->kode == '3.2.02.01') {
                            $saldo = KeuanganUtil::saldoLabaRugi($tahun, $bulan);
                        }
                        $saldoAkun += $saldo;
                    }
                    $saldoAkunLevel1[$akunLevel1->id] += $saldoAkun;
                    $groupRows[] = [
                        $akunLevel3->kode.'.',
                        $akunLevel3->nama,
                        $this->rupiah($saldoAkun),
                    ];
                }
            }

            if ($akunLevel1->id == 2 || $akunLevel1->id == 3) {
                $totalLiabilitasEkuitas += $saldoAkunLevel1[$akunLevel1->id];
            }

            $excelGroups[] = [
                'title' => $akunLevel1->kode.'. '.$akunLevel1->nama,
                'headers' => $headers,
                'rows' => $groupRows,
                'subtotals' => [
                    ['', 'Jumlah '.$akunLevel1->nama, $this->rupiah($saldoAkunLevel1[$akunLevel1->id])],
                ],
            ];
        }

        $excelGroups[] = [
            'title' => '',
            'headers' => $headers,
            'rows' => [],
            'subtotals' => [
                ['', 'Jumlah Liabilitas + Ekuitas', $this->rupiah($totalLiabilitasEkuitas)],
            ],
        ];

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            [],
            $excelGroups,
            'laporan-neraca.xlsx',
            [],
            [14, 48, 18]
        );
    }

    public function calk(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $akunLevel1s = AkunLevel1::with([
            'akunLevel2.akunLevel3.accounts' => function ($query) use ($business) {
                $query->where('business_id', $business->id);
            },
            'akunLevel2.akunLevel3.accounts.balance' => function ($query) use ($business, $tahun) {
                $query->where('business_id', $business->id)->where('tahun', $tahun);
            },
        ])->where('id', '<=', '3')->get();

        $title = 'Catatan Atas Laporan Keuangan (CALK)';
        $subtitle = $this->periodeSubtitle($tahun, $bulan);

        $headers = ['Kode', 'Nama Akun', 'Saldo'];
        $excelGroups = [];
        $totalLiabilitasEkuitas = 0;

        foreach ($akunLevel1s as $akunLevel1) {
            $groupRows = [];
            $saldoLevel1 = 0;

            foreach ($akunLevel1->akunLevel2 as $akunLevel2) {
                foreach ($akunLevel2->akunLevel3 as $akunLevel3) {
                    $saldoLevel3 = 0;
                    foreach ($akunLevel3->accounts as $account) {
                        $saldo = KeuanganUtil::sumSaldo($account, (int) $bulan);
                        if ($account->kode == '3.2.02.01') {
                            $saldo = KeuanganUtil::saldoLabaRugi($tahun, $bulan);
                        }
                        $saldoLevel3 += $saldo;
                        $groupRows[] = [
                            $account->kode,
                            $account->nama,
                            $this->rupiah($saldo),
                        ];
                    }
                    $saldoLevel1 += $saldoLevel3;
                }
            }

            if ($akunLevel1->id == 2 || $akunLevel1->id == 3) {
                $totalLiabilitasEkuitas += $saldoLevel1;
            }

            $excelGroups[] = [
                'title' => $akunLevel1->kode.'. '.$akunLevel1->nama,
                'headers' => $headers,
                'rows' => $groupRows,
                'subtotals' => [
                    ['', 'Jumlah '.$akunLevel1->nama, $this->rupiah($saldoLevel1)],
                ],
            ];
        }

        $excelGroups[] = [
            'title' => '',
            'headers' => $headers,
            'rows' => [],
            'subtotals' => [
                ['', 'Jumlah Liabilitas + Ekuitas', $this->rupiah($totalLiabilitasEkuitas)],
            ],
        ];

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            [],
            $excelGroups,
            'laporan-calk.xlsx',
            [],
            [16, 50, 18]
        );
    }

    public function labaRugi(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $result = KeuanganUtil::labaRugi($tahun, $bulan);
        $labaRugi = $result['groups'];
        $metrics = $result['metrics'];

        $title = 'Laporan Laba Rugi';
        $subtitle = $this->periodeSubtitle($tahun, $bulan);

        $headers = ['Kode', 'Nama Akun', 'S/D Bln Lalu', 'Bln Ini', 'S/D Bln Ini'];
        $excelGroups = [];

        foreach ($labaRugi as $index => $lr) {
            $rows = [];
            if (! empty($lr['kode'])) {
                foreach ($lr['kode'] as $kode) {
                    $rows[] = [
                        $kode['kode'] ?? '',
                        $kode['nama'] ?? '',
                        $this->rupiah($kode['saldo_sd_lalu'] ?? 0),
                        $this->rupiah($kode['saldo_bulan_ini'] ?? 0),
                        $this->rupiah($kode['saldo_sd_ini'] ?? 0),
                    ];
                }
            }

            $footerLabel = null;
            if ($index == 0) {
                $footerLabel = 'Total Pendapatan';
            } elseif ($index == 1) {
                $footerLabel = 'LABA KOTOR';
            } elseif ($index == 2) {
                $footerLabel = 'Total Beban';
            } elseif ($index == 3) {
                $footerLabel = 'Laba Bersih';
            }

            $subtotals = [];
            if ($footerLabel) {
                $subtotals[] = [
                    '', $footerLabel,
                    $this->rupiah($lr['total_sd_lalu'] ?? 0),
                    $this->rupiah($lr['total_bulan_ini'] ?? 0),
                    $this->rupiah($lr['total_sd_ini'] ?? 0),
                ];
            }

            if ($index == 2) {
                $subtotals[] = [
                    '', 'Laba Sebelum Pajak',
                    $this->rupiah($lr['total_sd_lalu'] ?? 0),
                    $this->rupiah($lr['total_bulan_ini'] ?? 0),
                    $this->rupiah($lr['total_sd_ini'] ?? 0),
                ];
            }

            $excelGroups[] = [
                'title' => $lr['nama'] ?? '-',
                'headers' => $headers,
                'rows' => $rows,
                'subtotals' => $subtotals,
            ];
        }

        $excelGroups[] = [
            'title' => 'METRICS',
            'headers' => ['Kode', 'Nama Akun', '', '', 'Nilai'],
            'rows' => [
                ['', 'Margin Kotor (%)', '', '', number_format((float) ($metrics['margin_kotor'] ?? 0), 2, ',', '.').'%'],
                ['', 'Margin Bersih (%)', '', '', number_format((float) ($metrics['margin_bersih'] ?? 0), 2, ',', '.').'%'],
            ],
            'subtotals' => [],
        ];

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            [],
            $excelGroups,
            'laporan-laba-rugi.xlsx',
            [],
            [16, 40, 18, 18, 18]
        );
    }

    public function arusKas(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');
        $bulanLalu = max((int) $bulan - 1, 0);

        $arusKas = KeuanganUtil::arusKas($tahun, $bulan);
        $saldoKas = (float) KeuanganUtil::saldoKas($tahun, $bulanLalu);

        $title = 'Laporan Arus Kas';
        $subtitle = $this->periodeSubtitle($tahun, $bulan);

        $headers = ['', 'Nama Akun', 'Saldo'];
        $excelGroups = [];

        foreach ($arusKas as $index => $ak) {
            $rows = [];
            $subtotals = [];

            if ($ak['header']) {
                $rows[] = [
                    $index + 1,
                    $ak['header']->nama_akun ?? '-',
                    $index == 0 ? $this->rupiah($saldoKas) : '',
                ];
            }

            $grandTotal = [];
            foreach ($ak['groups'] as $indexGroup => $group) {
                if ($group['subheader']) {
                    $rows[] = ['', $group['subheader']->nama_akun ?? '', ''];
                }

                $total = 0;
                foreach ($group['items'] as $item) {
                    $rows[] = ['', $item->nama_akun ?? '-', $this->rupiah($item->total)];
                    $total += (float) $item->total;
                }

                $titleJumlah = $ak['header']->nama_akun ?? '';
                if ($group['subheader']) {
                    $titleJumlah = $group['subheader']->nama_akun ?? '';
                }

                if (strtolower($titleJumlah) != 'pengeluaran') {
                    $grandTotal[$indexGroup] = $total;
                    $rows[] = ['', 'Jumlah '.$titleJumlah, $this->rupiah($total)];
                }
            }

            if ($index > 0) {
                $totalBawah = 0;
                foreach ($grandTotal as $indexGrandTotal => $jumlahBawah) {
                    if ($indexGrandTotal == 0) {
                        $totalBawah += $jumlahBawah;
                    } else {
                        $totalBawah -= $jumlahBawah;
                    }
                }

                $label = '';
                if ($index == 1) {
                    $label = 'Kas Bersih yang diperoleh dari aktivitas Operasi (A-B-C)';
                } elseif ($index == 2) {
                    $label = 'Kas Bersih yang diperoleh dari aktivitas Investasi (A-B)';
                } elseif ($index == 3) {
                    $label = 'Kas Bersih yang diperoleh dari aktivitas Pendanaan (A-B)';
                }
                $subtotals[] = ['', $label, $this->rupiah($totalBawah)];
            }

            $excelGroups[] = [
                'title' => $ak['header'] ? $ak['header']->nama_akun : '',
                'headers' => $headers,
                'rows' => $rows,
                'subtotals' => $subtotals,
            ];
        }

        $totalArusKas = 0;
        foreach ($excelGroups as $g) {
            foreach ($g['subtotals'] as $st) {
                if (is_numeric($st[2])) {
                    $totalArusKas += (float) $st[2];
                }
            }
        }

        $finalGroup = [
            'title' => '',
            'headers' => $headers,
            'rows' => [],
            'subtotals' => [
                ['', 'Kenaikan (Penurunan) Kas', $this->rupiah($totalArusKas)],
                ['', 'SALDO AKHIR KAS SETARA KAS', $this->rupiah($totalArusKas + $saldoKas)],
            ],
        ];
        $excelGroups[] = $finalGroup;

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            [],
            $excelGroups,
            'laporan-arus-kas.xlsx',
            [],
            [8, 50, 22]
        );
    }

    public function asetTetapInventaris(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? '-';
        $tgl_kondisi = Carbon::createFromDate((int) $tahun, $bulan == '-' ? 12 : (int) $bulan, 1)->endOfMonth()->format('Y-m-d');

        $inventarisGroups = Inventory::where([
            ['jenis', '1'],
            ['status', '!=', '0'],
            ['tanggal_beli', '<=', $tgl_kondisi],
            ['harga_satuan', '>', '0'],
        ])->whereNotNull('tanggal_beli')
          ->whereIn('kategori', [1, 2, 3, 4])
          ->orderBy('kategori', 'ASC')->orderBy('tanggal_beli', 'ASC')
          ->get()->groupBy('kategori');

        $kategoriNaman = [
            1 => 'Tanah',
            2 => 'Gedung',
            3 => 'Kendaraan dan Mesin produksi',
            4 => 'Peralatan umum/ Inventaris',
        ];

        $title = 'Aset Tetap dan Inventaris';
        $subtitle = $this->periodeSubtitle($tahun, $bulan);

        $excelGroups = [];
        foreach ($inventarisGroups as $kategori => $items) {
            $namaKategori = $kategoriNaman[$kategori] ?? 'Kategori '.$kategori;
            $isTanah = ($kategori == 1);

            $headers = ['No', 'Tgl Beli', 'Nama Barang', 'Id', 'Kondisi', 'Unit', 'Harga Satuan', 'Harga Perolehan'];
            if (! $isTanah) {
                $headers = array_merge($headers, ['Umur Eko.', 'Satuan Susut', 'Umur', 'Biaya', 'Umur', 'Biaya']);
            }
            $headers[] = 'Nilai Buku';

            $rows = [];
            $t_unit = 0; $t_harga = 0; $t_penyusutan = 0; $t_akum_susut = 0; $t_nilai_buku = 0;
            $j_unit = 0; $j_harga = 0; $j_penyusutan = 0; $j_akum_susut = 0; $j_nilai_buku = 0;
            $no = 1;

            foreach ($items as $inv) {
                $nama_barang = $inv->nama_barang;
                $is_valid = true;
                if (strtolower($inv->status) != 'baik' && $tgl_kondisi >= $inv->tanggal_validasi) {
                    $tglValStr = Carbon::parse($inv->tanggal_validasi)->format('d/m/Y');
                    $nama_barang .= ' ('.$inv->status.' '.$tglValStr.')';
                    $is_valid = false;
                }

                $statusListInvalid = ['dijual', 'jual', 'hilang', 'dihapus', 'hapus'];
                $is_status_invalid = in_array(strtolower($inv->status), $statusListInvalid);

                if ($isTanah) {
                    $t_unit += $inv->jumlah;
                    $t_harga += $inv->harga_satuan * $inv->jumlah;
                    $nilai_buku = $inv->harga_satuan * $inv->jumlah;
                    if ($is_status_invalid) $nilai_buku = 0;
                    if ($is_status_invalid && $tgl_kondisi >= $inv->tanggal_validasi) {
                        $j_unit += $inv->jumlah;
                        $j_harga += $inv->harga_satuan * $inv->jumlah;
                        $j_nilai_buku += $nilai_buku;
                    } else {
                        $t_nilai_buku += $nilai_buku;
                    }
                    $rows[] = [
                        $no++, Carbon::parse($inv->tanggal_beli)->format('d/m/Y'), $nama_barang,
                        $inv->id, ucfirst($inv->status), (int) $inv->jumlah,
                        $this->rupiah($inv->harga_satuan), $this->rupiah($inv->harga_satuan * $inv->jumlah),
                        $this->rupiah($nilai_buku),
                    ];
                } else {
                    $satuan_susut = $inv->harga_satuan <= 0 ? 0 : round(($inv->harga_satuan * $inv->jumlah) / $inv->umur_ekonomis, 2);
                    $pakai_lalu = InventarisUtil::bulan($inv->tanggal_beli, ($tahun - 1).'-12-31');
                    $nilai_buku = InventarisUtil::nilaiBuku($tgl_kondisi, $inv);

                    if (strtolower($inv->status) != 'baik' && $tgl_kondisi >= $inv->tanggal_validasi) {
                        $umur = InventarisUtil::bulan($inv->tanggal_beli, $inv->tanggal_validasi);
                    } else {
                        $umur = InventarisUtil::bulan($inv->tanggal_beli, $tgl_kondisi);
                    }

                    $_satuan_susut = $satuan_susut;
                    if ($umur >= $inv->umur_ekonomis) {
                        $harga = $inv->harga_satuan * $inv->jumlah;
                        $_susut = $satuan_susut * ($inv->umur_ekonomis - 1);
                        $satuan_susut = $harga - $_susut - 1;
                    }

                    $susut = $satuan_susut * $umur;
                    if ($umur >= $inv->umur_ekonomis && $inv->harga_satuan * $inv->jumlah > 0) {
                        $akum_umur = $inv->umur_ekonomis;
                        $akum_susut = $inv->harga_satuan * $inv->jumlah - 1;
                        $nilai_buku = 1;
                    } else {
                        $akum_umur = $umur;
                        $akum_susut = $susut;
                        if ($nilai_buku < 0) $nilai_buku = 1;
                    }

                    $umur_pakai = $akum_umur - $pakai_lalu;
                    $penyusutan = $satuan_susut * $umur_pakai;

                    if ($is_status_invalid && $tgl_kondisi >= $inv->tanggal_validasi) {
                        $akum_susut = $inv->harga_satuan * $inv->jumlah;
                        $nilai_buku = 0;
                        $penyusutan = 0;
                        $umur_pakai = 0;
                    }
                    if (strtolower($inv->status) == 'rusak' && $tgl_kondisi >= $inv->tanggal_validasi) {
                        $akum_susut = $inv->harga_satuan * $inv->jumlah - 1;
                        $nilai_buku = 1;
                        $penyusutan = 0;
                        $umur_pakai = 0;
                    }
                    if (! ($umur_pakai >= 0 && $inv->harga_satuan * $inv->jumlah > 0)) {
                        $umur_pakai = 0;
                        $penyusutan = 0;
                    }
                    if ($akum_umur == $inv->umur_ekonomis && $umur_pakai > 0) {
                        $penyusutan = $_satuan_susut * ($umur_pakai - 1) + $satuan_susut;
                    }

                    $t_unit += $inv->jumlah;
                    $t_harga += $inv->harga_satuan * $inv->jumlah;
                    $t_penyusutan += $penyusutan;
                    $t_akum_susut += $akum_susut;
                    $t_nilai_buku += $nilai_buku;

                    $tahun_validasi = $inv->tanggal_validasi ? (int) substr($inv->tanggal_validasi, 0, 4) : 0;

                    if ($nilai_buku == 0 && $tahun_validasi < $tahun && $tahun_validasi > 0) {
                        $j_unit += $inv->jumlah;
                        $j_harga += $inv->harga_satuan * $inv->jumlah;
                        $j_penyusutan += $penyusutan;
                        $j_akum_susut += $akum_susut;
                        $j_nilai_buku += $nilai_buku;
                    } else {
                        $rows[] = [
                            $no++, Carbon::parse($inv->tanggal_beli)->format('d/m/Y'), $nama_barang,
                            $inv->id, ucfirst($inv->status), (int) $inv->jumlah,
                            $this->rupiah($inv->harga_satuan), $this->rupiah($inv->harga_satuan * $inv->jumlah),
                            (int) $inv->umur_ekonomis, $this->rupiah($_satuan_susut),
                            (int) $umur_pakai, $this->rupiah($penyusutan),
                            (int) $akum_umur, $this->rupiah($akum_susut),
                            $this->rupiah($nilai_buku),
                        ];
                    }
                }
            }

            $subtotals = [];
            if (! $isTanah) {
                $subtotals[] = [
                    '', '', 'Jumlah Daftar '.$namaKategori.' (Hapus, Hilang, Jual) s.d. Tahun '.($tahun - 1),
                    '', '', (int) $j_unit, '', $this->rupiah($j_harga),
                    '', '', '', $this->rupiah($j_penyusutan), '', $this->rupiah($j_akum_susut), $this->rupiah($j_nilai_buku),
                ];
            }
            $jumRow = ['', '', 'Jumlah', '', '', (int) $t_unit, '', $this->rupiah($t_harga)];
            if (! $isTanah) {
                $jumRow = array_merge($jumRow, ['', '', '', $this->rupiah($t_penyusutan), '', $this->rupiah($t_akum_susut)]);
            }
            $jumRow[] = $this->rupiah($t_nilai_buku);
            $subtotals[] = $jumRow;

            $excelGroups[] = [
                'title' => 'Daftar '.$namaKategori,
                'headers' => $headers,
                'rows' => $rows,
                'subtotals' => $subtotals,
            ];
        }

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            [],
            $excelGroups,
            'laporan-aset-tetap-inventaris.xlsx',
            [],
            [5, 12, 28, 6, 10, 6, 14, 16, 10, 14, 8, 14, 8, 14, 14]
        );
    }

    public function penjualanProduk(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? '-';
        $hari = $data['periode'] ?? '-';

        $query = SaleDetail::with(['sale.customer', 'product.unit'])
            ->whereHas('sale', function ($q) use ($business, $tahun, $bulan, $hari) {
                $q->where('business_id', $business->id);
                if ($bulan != '-') {
                    $q->whereYear('tanggal_transaksi', $tahun)->whereMonth('tanggal_transaksi', $bulan);
                } else {
                    $q->whereYear('tanggal_transaksi', $tahun);
                }
                if ($hari != '-') $q->whereDay('tanggal_transaksi', $hari);
            });

        if (isset($data['sub_laporan']) && $data['sub_laporan'] != '') {
            if (str_starts_with($data['sub_laporan'], 'prod:')) {
                $query->where('product_id', str_replace('prod:', '', $data['sub_laporan']));
            } elseif (str_starts_with($data['sub_laporan'], 'cus:')) {
                $cusId = str_replace('cus:', '', $data['sub_laporan']);
                $query->whereHas('sale', function ($q) use ($cusId) { $q->where('customer_id', $cusId); });
            }
        }

        $sales = $query->orderBy('id', 'desc')->get();
        $total = $sales->sum('subtotal');

        $headers = ['No', 'Product ID', 'Produk', 'Satuan', 'Nama Pelanggan', 'Nomor Faktur', 'Tanggal', 'Kuantitas', 'Harga Jual Satuan', 'Sub Total'];
        $rows = [];
        foreach ($sales as $i => $row) {
            $rows[] = [
                $i + 1,
                $row->product_id,
                $row->product->nama_produk ?? '-',
                $row->product->unit->inisial_satuan ?? ($row->product->unit->nama_satuan ?? '-'),
                $row->sale->customer->nama_pelanggan ?? 'Guest',
                $row->sale->no_invoice ?? '-',
                Carbon::parse($row->sale->tanggal_transaksi)->format('d/m/Y H:i'),
                (float) $row->jumlah,
                $this->rupiah($row->harga_satuan),
                $this->rupiah($row->subtotal),
            ];
        }
        $totalsRow = ['', '', '', '', '', '', '', '', 'Total:', $this->rupiah($total)];

        return $this->buildExcel(
            'Laporan Penjualan Produk',
            $this->periodeSubtitle($tahun, $bulan, $hari),
            $headers,
            $rows,
            $totalsRow,
            'laporan-penjualan-produk.xlsx',
            [],
            [5, 14, 32, 12, 22, 18, 18, 12, 18, 18]
        );
    }

    public function pembelianProduk(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? '-';
        $hari = $data['periode'] ?? '-';

        $query = PurchaseDetail::with(['purchase.supplier', 'product.unit'])
            ->whereHas('purchase', function ($q) use ($business, $tahun, $bulan, $hari) {
                $q->where('business_id', $business->id);
                if ($bulan != '-') {
                    $q->whereYear('tanggal_pembelian', $tahun)->whereMonth('tanggal_pembelian', $bulan);
                } else {
                    $q->whereYear('tanggal_pembelian', $tahun);
                }
                if ($hari != '-') $q->whereDay('tanggal_pembelian', $hari);
            });

        if (isset($data['sub_laporan']) && $data['sub_laporan'] != '') {
            if (str_starts_with($data['sub_laporan'], 'prod:')) {
                $query->where('product_id', str_replace('prod:', '', $data['sub_laporan']));
            } elseif (str_starts_with($data['sub_laporan'], 'sup:')) {
                $supId = str_replace('sup:', '', $data['sub_laporan']);
                $query->whereHas('purchase', function ($q) use ($supId) { $q->where('supplier_id', $supId); });
            }
        }

        $purchases = $query->orderBy('id', 'desc')->get();
        $total = $purchases->sum('subtotal');

        $headers = ['No', 'Product ID', 'Produk', 'Satuan', 'Nama Supplier', 'Nomor Pembelian', 'Tanggal', 'Kuantitas', 'Harga Beli Satuan', 'Sub Total'];
        $rows = [];
        foreach ($purchases as $i => $row) {
            $rows[] = [
                $i + 1,
                $row->product_id,
                $row->product->nama_produk ?? '-',
                $row->product->unit->inisial_satuan ?? ($row->product->unit->nama_satuan ?? '-'),
                $row->purchase->supplier->nama_supplier ?? '-',
                $row->purchase->no_pembelian ?? '-',
                Carbon::parse($row->purchase->tanggal_pembelian)->format('d/m/Y H:i'),
                (float) $row->jumlah,
                $this->rupiah($row->harga_satuan),
                $this->rupiah($row->subtotal),
            ];
        }
        $totalsRow = ['', '', '', '', '', '', '', '', 'Total:', $this->rupiah($total)];

        return $this->buildExcel(
            'Laporan Pembelian Produk',
            $this->periodeSubtitle($tahun, $bulan, $hari),
            $headers,
            $rows,
            $totalsRow,
            'laporan-pembelian-produk.xlsx',
            [],
            [5, 14, 32, 12, 22, 18, 18, 12, 18, 18]
        );
    }

    public function penjualanDetail(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? '-';
        $hari = $data['periode'] ?? '-';

        $query = Sale::with(['customer', 'user'])
            ->where('business_id', $business->id)
            ->whereYear('tanggal_transaksi', $tahun);

        if ($bulan != '-') {
            $query->whereMonth('tanggal_transaksi', $bulan);
        }
        if ($hari != '-') {
            $query->whereDay('tanggal_transaksi', $hari);
        }

        if (isset($data['sub_laporan']) && $data['sub_laporan'] != '') {
            if (str_starts_with($data['sub_laporan'], 'cus:')) {
                $cusId = str_replace('cus:', '', $data['sub_laporan']);
                $query->where('customer_id', $cusId);
            } elseif (str_starts_with($data['sub_laporan'], 'cat:')) {
                $catId = str_replace('cat:', '', $data['sub_laporan']);
                $query->whereHas('saleDetails.product', function ($q) use ($catId) {
                    $q->where('category_id', $catId);
                });
            }
        }

        $sales = $query->orderBy('tanggal_transaksi', 'desc')->get();

        $headers = ['No', 'No. Invoice', 'Tanggal', 'Pelanggan', 'Kasir', 'Item', 'Total Penjualan', 'HPP', 'Untung', 'Rugi'];
        $rows = [];
        $totPenjualan = 0; $totHpp = 0; $totUntung = 0; $totRugi = 0;

        foreach ($sales as $index => $sale) {
            $details = $sale->saleDetails()->get();
            $sumHpp = (float) $details->sum('hpp');
            $sumProfit = (float) $details->sum('profit');
            $sumUntung = $sumProfit > 0 ? $sumProfit : 0;
            $sumRugi = $sumProfit < 0 ? abs($sumProfit) : 0;
            $totalItem = (int) $details->sum('jumlah');

            $totPenjualan += (float) $sale->total;
            $totHpp += $sumHpp;
            $totUntung += $sumUntung;
            $totRugi += $sumRugi;

            $rows[] = [
                $index + 1,
                $sale->no_invoice ?? '-',
                Carbon::parse($sale->tanggal_transaksi)->format('d/m/Y H:i'),
                $sale->customer->nama_pelanggan ?? 'Guest',
                $sale->user->initial ?? '-',
                $totalItem,
                $this->rupiah($sale->total),
                $this->rupiah($sumHpp),
                $this->rupiah($sumUntung),
                $this->rupiah($sumRugi),
            ];
        }

        $totalsRow = [
            '', '', '', '', 'Total', '',
            $this->rupiah($totPenjualan),
            $this->rupiah($totHpp),
            $this->rupiah($totUntung),
            $this->rupiah($totRugi),
        ];

        return $this->buildExcel(
            'Laporan Penjualan Detail',
            $this->periodeSubtitle($tahun, $bulan, $hari),
            $headers,
            $rows,
            $totalsRow,
            'laporan-penjualan-detail.xlsx',
            [],
            [5, 20, 16, 24, 8, 8, 18, 16, 16, 16]
        );
    }

    public function produkTerlaris(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $query = SaleDetail::select(
            'product_id',
            DB::raw('SUM(jumlah) as total_terjual'),
            DB::raw('SUM(subtotal) as total_revenue'),
            DB::raw('SUM(profit) as total_profit')
        )
            ->whereHas('sale', function ($q) use ($tahun, $bulan) {
                $q->whereYear('tanggal_transaksi', $tahun);
                if ($bulan != '-') $q->whereMonth('tanggal_transaksi', $bulan);
            })
            ->when(isset($data['sub_laporan']) && str_starts_with($data['sub_laporan'], 'cat:'), function ($q) use ($data) {
                $catId = str_replace('cat:', '', $data['sub_laporan']);
                $q->whereHas('product', function ($sq) use ($catId) { $sq->where('category_id', $catId); });
            })
            ->groupBy('product_id')->orderByDesc('total_terjual')->limit(20)
            ->with('product.category')->get();

        $headers = ['No', 'Nama Produk', 'Kategori', 'Qty Terjual', 'Total Revenue', 'Total Profit'];
        $rows = [];
        $sumQty = 0; $sumRev = 0; $sumProf = 0;
        foreach ($query as $i => $item) {
            $rows[] = [
                $i + 1,
                $item->product->nama_produk ?? '-',
                $item->product->category->nama_kategori ?? '-',
                (float) $item->total_terjual,
                $this->rupiah($item->total_revenue),
                $this->rupiah($item->total_profit),
            ];
            $sumQty += (float) $item->total_terjual;
            $sumRev += (float) $item->total_revenue;
            $sumProf += (float) $item->total_profit;
        }
        $totalsRow = ['', '', 'Total', $sumQty, $this->rupiah($sumRev), $this->rupiah($sumProf)];

        return $this->buildExcel(
            'Laporan Produk Terlaris',
            $this->periodeSubtitle($tahun, $bulan).' (Top 20)',
            $headers,
            $rows,
            $totalsRow,
            'laporan-produk-terlaris.xlsx',
            [],
            [5, 36, 22, 14, 18, 18]
        );
    }

    public function piutang(array $data)
    {
        $sales = Sale::with('customer')
            ->where('jumlah_utang', '>', 0)
            ->orderBy('tanggal_transaksi', 'asc')->get();

        $grouped = $sales->groupBy('customer_id')->map(function ($items) {
            return [
                'customer' => $items->first()->customer,
                'total_piutang' => $items->sum('jumlah_utang'),
                'jumlah_invoice' => $items->count(),
                'items' => $items,
            ];
        })->sortByDesc('total_piutang');

        $totalPiutang = $sales->sum('jumlah_utang');

        $title = 'Laporan Piutang (Customer)';
        $subtitle = 'Per Tanggal: '.Carbon::now()->isoFormat('D MMMM Y');

        $summaryRow = [
            ['Total Piutang: '.$this->rupiah($totalPiutang), '', '', '', '', ''],
        ];

        $tableHeaders = ['No. Invoice', 'Tanggal', 'Total', 'Dibayar', 'Sisa Piutang', 'Umur (Hari)'];
        $excelGroups = [];
        foreach ($grouped as $group) {
            $rows = [];
            foreach ($group['items'] as $sale) {
                $umur = Carbon::parse($sale->tanggal_transaksi)->diffInDays(now());
                $rows[] = [
                    $sale->no_invoice ?? '-',
                    Carbon::parse($sale->tanggal_transaksi)->format('d/m/Y'),
                    $this->rupiah($sale->total),
                    $this->rupiah($sale->dibayar),
                    $this->rupiah($sale->jumlah_utang),
                    $umur.' hari',
                ];
            }
            $excelGroups[] = [
                'title' => ($group['customer']->nama_pelanggan ?? 'Guest').' ('.$group['jumlah_invoice'].' invoice)',
                'headers' => $tableHeaders,
                'rows' => $rows,
                'subtotals' => [
                    ['', '', '', 'Subtotal', $this->rupiah($group['total_piutang']), ''],
                ],
            ];
        }

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            $summaryRow,
            $excelGroups,
            'laporan-piutang.xlsx',
            [],
            [22, 14, 16, 16, 16, 14]
        );
    }

    public function hutang(array $data)
    {
        $purchases = Purchase::with('supplier')
            ->where('jumlah_utang', '>', 0)
            ->orderBy('tanggal_pembelian', 'asc')->get();

        $grouped = $purchases->groupBy('supplier_id')->map(function ($items) {
            return [
                'supplier' => $items->first()->supplier,
                'total_hutang' => $items->sum('jumlah_utang'),
                'jumlah_po' => $items->count(),
                'items' => $items,
            ];
        })->sortByDesc('total_hutang');

        $totalHutang = $purchases->sum('jumlah_utang');

        $title = 'Laporan Hutang (Supplier)';
        $subtitle = 'Per Tanggal: '.Carbon::now()->isoFormat('D MMMM Y');

        $summaryRow = [
            ['Total Hutang: '.$this->rupiah($totalHutang), '', '', '', '', ''],
        ];

        $tableHeaders = ['No. Pembelian', 'Tanggal', 'Total', 'Dibayar', 'Sisa Hutang', 'Umur (Hari)'];
        $excelGroups = [];
        foreach ($grouped as $group) {
            $rows = [];
            foreach ($group['items'] as $purchase) {
                $umur = Carbon::parse($purchase->tanggal_pembelian)->diffInDays(now());
                $rows[] = [
                    $purchase->no_pembelian ?? '-',
                    Carbon::parse($purchase->tanggal_pembelian)->format('d/m/Y'),
                    $this->rupiah($purchase->total),
                    $this->rupiah($purchase->dibayar),
                    $this->rupiah($purchase->jumlah_utang),
                    $umur.' hari',
                ];
            }
            $excelGroups[] = [
                'title' => ($group['supplier']->nama_supplier ?? '-').' ('.$group['jumlah_po'].' PO)',
                'headers' => $tableHeaders,
                'rows' => $rows,
                'subtotals' => [
                    ['', '', '', 'Subtotal', $this->rupiah($group['total_hutang']), ''],
                ],
            ];
        }

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            $summaryRow,
            $excelGroups,
            'laporan-hutang.xlsx',
            [],
            [22, 14, 16, 16, 16, 14]
        );
    }

    public function stokOpname(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $query = StockOpname::whereYear('tanggal_opname', $tahun)
            ->whereHas('details', function ($q) { $q->where('selisih', '!=', 0); })
            ->with(['details' => function ($q) {
                $q->where('selisih', '!=', 0)->with('product');
            }, 'user']);

        if ($bulan != '-') $query->whereMonth('tanggal_opname', $bulan);

        if (isset($data['sub_laporan']) && $data['sub_laporan'] != '') {
            if (str_starts_with($data['sub_laporan'], 'rak:')) {
                $rakId = str_replace('rak:', '', $data['sub_laporan']);
                $query->whereHas('details.product', function ($q) use ($rakId) { $q->where('shelf_id', $rakId); });
            } elseif (str_starts_with($data['sub_laporan'], 'cat:')) {
                $catId = str_replace('cat:', '', $data['sub_laporan']);
                $query->whereHas('details.product', function ($q) use ($catId) { $q->where('category_id', $catId); });
            }
        }

        $opnames = $query->orderBy('tanggal_opname', 'desc')->get();

        $title = 'Laporan Stok Opname';
        $subtitle = $this->periodeSubtitle($tahun, $bulan);

        $summaryRow = [];

        $tableHeaders = ['No', 'Produk', 'Stok Sistem', 'Stok Fisik', 'Selisih', 'Jenis', 'Nilai Selisih', 'Alasan'];
        $excelGroups = [];
        foreach ($opnames as $opname) {
            $rows = [];
            $detailNo = 0;
            foreach ($opname->details as $detail) {
                $detailNo++;
                $rows[] = [
                    $detailNo,
                    $detail->product->nama_produk ?? '-',
                    (float) ($detail->stok_sistem ?? 0),
                    (float) ($detail->stok_fisik ?? 0),
                    (float) ($detail->selisih ?? 0),
                    $detail->jenis_selisih ?? '-',
                    $this->rupiah($detail->total_harga ?? 0),
                    $detail->alasan ?? '-',
                ];
            }
            $excelGroups[] = [
                'title' => ($opname->no_opname ?? '-').' | '.Carbon::parse($opname->tanggal_opname)->format('d/m/Y')
                    .' | Status: '.($opname->status ?? '-').' | Petugas: '.($opname->user->name ?? '-'),
                'headers' => $tableHeaders,
                'rows' => $rows,
                'subtotals' => [],
            ];
        }

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            $summaryRow,
            $excelGroups,
            'laporan-stok-opname.xlsx',
            [],
            [5, 32, 14, 14, 12, 14, 16, 32]
        );
    }

    public function buktiStokOpname(array $data)
    {
        $business = view()->shared('business');
        $id = $data['id'] ?? null;
        if (! $id) {
            abort(404, 'ID Stock Opname tidak ditemukan');
        }

        $opname = StockOpname::with(['details' => function ($q) {
            $q->where('selisih', '!=', 0)->with('product');
        }, 'user', 'approvedBy'])->where('business_id', $business->id)->findOrFail($id);

        $title = 'Bukti Stock Opname';
        $subtitle = 'No: '.$opname->no_opname.' | Tanggal: '.Carbon::parse($opname->tanggal_opname)->format('d F Y')
            .' | Status: '.strtoupper($opname->status ?? '-');

        $summaryRow = [
            ['Petugas', ': '.($opname->user->nama_lengkap ?? '-'), 'Disetujui Oleh', ': '.($opname->approvedBy->nama_lengkap ?? '-')],
            ['Catatan', ': '.($opname->catatan ?? '-'), '', ''],
        ];

        $headers = ['No', 'Produk', 'Stok Sistem', 'Stok Fisik', 'Selisih', 'Alasan'];
        $rows = [];
        foreach ($opname->details as $detail) {
            $nama_produk = $detail->product->nama_produk ?? '-';
            $kode_produk = $detail->product->kode_produk ?? '';
            $rows[] = [
                $detail->iteration ?? (count($rows) + 1),
                $kode_produk ? $nama_produk.' ('.$kode_produk.')' : $nama_produk,
                (float) ($detail->stok_sistem ?? 0),
                (float) ($detail->stok_fisik ?? 0),
                (float) ($detail->selisih ?? 0),
                $detail->alasan ?? '-',
            ];
        }

        $excelGroups = [
            [
                'title' => null,
                'headers' => $headers,
                'rows' => $rows,
                'subtotals' => [],
            ],
        ];

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            $summaryRow,
            $excelGroups,
            'bukti-so-'.$opname->no_opname.'.xlsx',
            [],
            [5, 40, 14, 14, 12, 36]
        );
    }

    public function formStockOpname(array $data)
    {
        $business = view()->shared('business');
        $categoryId = $data['categoryId'] ?? null;
        $shelfId = $data['shelfId'] ?? null;
        $opnameId = $data['opnameId'] ?? null;

        $categoryName = '-';
        $shelfName = '-';
        $catatan = '-';

        $query = Product::where('business_id', auth()->user()->business_id)
            ->where('is_active', true);

        if ($opnameId) {
            $opname = StockOpname::find($opnameId);
            if ($opname) {
                $catatan = $opname->catatan ?: '-';
                $b = Business::find($opname->business_id);
                if ($b) {
                    $business = $b;
                    view()->share('business', $business);
                }
            }
            $query->whereIn('id', function ($q) use ($opnameId) {
                $q->select('product_id')->from('stock_opname_details')->where('stock_opname_id', $opnameId);
            });
        } else {
            if ($categoryId) {
                $query->where('category_id', $categoryId);
                $categoryName = \App\Models\Category::find($categoryId)?->nama_kategori ?: '-';
            }
            if ($shelfId) {
                $query->where('shelf_id', $shelfId);
                $shelfName = \App\Models\Shelves::find($shelfId)?->nama_rak ?: '-';
            }
        }

        $products = $query->orderBy('nama_produk')->get();

        $title = 'Form Stock Opname (Lembar Kerja)';
        $subtitle = 'Per Tanggal: '.Carbon::now()->isoFormat('D MMMM Y');

        $summaryRow = [
            ['Lokasi/Rak', ': '.$shelfName, 'Kategori', ': '.$categoryName],
            ['Catatan', ': '.$catatan, '', ''],
        ];

        $headers = ['No', 'Kode Produk', 'Nama Produk', 'Sistem', 'Fisik', 'Ket.'];
        $rows = [];
        foreach ($products as $i => $p) {
            $rows[] = [
                $i + 1,
                $p->sku ?? ($p->kode_produk ?? '-'),
                $p->nama_produk,
                (int) $p->stok_aktual,
                '',
                '',
            ];
        }

        $excelGroups = [
            [
                'title' => null,
                'headers' => $headers,
                'rows' => $rows,
                'subtotals' => [],
            ],
        ];

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            $summaryRow,
            $excelGroups,
            'form-stock-opname.xlsx',
            [],
            [5, 16, 40, 12, 12, 16]
        );
    }

    public function pembelian(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $query = Purchase::with('supplier')->whereYear('tanggal_pembelian', $tahun);
        if ($bulan != '-') $query->whereMonth('tanggal_pembelian', $bulan);
        if (isset($data['sub_laporan']) && str_starts_with($data['sub_laporan'], 'sup:')) {
            $supId = str_replace('sup:', '', $data['sub_laporan']);
            $query->where('supplier_id', $supId);
        }

        $purchases = $query->orderBy('tanggal_pembelian', 'desc')->get();

        $summary = [
            'total_po' => $purchases->count(),
            'total_pembelian' => $purchases->sum('total'),
            'total_dibayar' => $purchases->sum('dibayar'),
            'total_hutang' => $purchases->sum('jumlah_utang'),
        ];

        $title = 'Laporan Pembelian';
        $subtitle = $this->periodeSubtitle($tahun, $bulan);

        $summaryRow = [
            ['Jumlah PO', 'Total Pembelian', 'Total Dibayar', 'Total Hutang'],
            [
                $summary['total_po'],
                $this->rupiah($summary['total_pembelian']),
                $this->rupiah($summary['total_dibayar']),
                $this->rupiah($summary['total_hutang']),
            ],
        ];

        $headers = ['No', 'No. Pembelian', 'Tanggal', 'Supplier', 'Pembayaran', 'Total', 'Hutang', 'Status'];
        $rows = [];
        foreach ($purchases as $i => $purchase) {
            $rows[] = [
                $i + 1,
                $purchase->no_pembelian ?? '-',
                Carbon::parse($purchase->tanggal_pembelian)->format('d/m/Y'),
                $purchase->supplier->nama_supplier ?? '-',
                ucfirst($purchase->jenis_pembayaran ?? '-'),
                $this->rupiah($purchase->total),
                $this->rupiah($purchase->jumlah_utang),
                $purchase->status ?? '-',
            ];
        }
        $totalsRow = ['', '', '', '', 'Total', $this->rupiah($purchases->sum('total')), $this->rupiah($purchases->sum('jumlah_utang')), ''];

        $excelGroups = [
            [
                'title' => 'Rincian Pembelian',
                'headers' => $headers,
                'rows' => $rows,
                'subtotals' => [$totalsRow],
            ],
        ];

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            $summaryRow,
            $excelGroups,
            'laporan-pembelian.xlsx',
            [],
            [5, 22, 14, 28, 14, 16, 16, 14]
        );
    }

    public function marginProduk(array $data)
    {
        $business = view()->shared('business');
        $products = Product::where('business_id', $business->id)
            ->where('is_active', true)->where('harga_jual', '>', 0)
            ->with('category')->get()
            ->map(function ($p) {
                $p->margin_rp = $p->harga_jual - $p->biaya_rata_rata;
                $p->margin_pct = $p->harga_jual > 0 ? (($p->harga_jual - $p->biaya_rata_rata) / $p->harga_jual) * 100 : 0;
                return $p;
            })->sortByDesc('margin_pct');

        $headers = ['No', 'SKU', 'Nama Produk', 'Kategori', 'HPP', 'Harga Jual', 'Margin (Rp)', 'Margin (%)'];
        $rows = [];
        foreach ($products as $i => $p) {
            $rows[] = [
                $i + 1,
                $p->sku ?? '-',
                $p->nama_produk,
                $p->category->nama_kategori ?? '-',
                $this->rupiah($p->biaya_rata_rata),
                $this->rupiah($p->harga_jual),
                $this->rupiah($p->margin_rp),
                number_format($p->margin_pct, 2, ',', '.').'%',
            ];
        }

        return $this->buildExcel(
            'Laporan Margin & Profitabilitas Produk',
            'Per Tanggal: '.Carbon::now()->isoFormat('D MMMM Y'),
            $headers,
            $rows,
            [],
            'laporan-margin-produk.xlsx',
            [],
            [5, 14, 36, 22, 16, 16, 16, 14]
        );
    }

    public function customerTerbaik(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $query = Sale::select(
            'customer_id',
            DB::raw('COUNT(*) as jumlah_transaksi'),
            DB::raw('SUM(total) as total_belanja'),
            DB::raw('AVG(total) as rata_rata')
        )
            ->where('business_id', $business->id)
            ->whereYear('tanggal_transaksi', $tahun);
        if ($bulan != '-') $query->whereMonth('tanggal_transaksi', $bulan);

        $customers = $query->groupBy('customer_id')->orderByDesc('total_belanja')->limit(20)
            ->with('customer')->get();

        $headers = ['No', 'Nama Customer', 'Jumlah Transaksi', 'Total Belanja', 'Rata-rata'];
        $rows = [];
        foreach ($customers as $i => $c) {
            $rows[] = [
                $i + 1,
                $c->customer->nama_pelanggan ?? 'Guest',
                (int) $c->jumlah_transaksi,
                $this->rupiah($c->total_belanja),
                $this->rupiah($c->rata_rata),
            ];
        }
        $totalsRow = ['', 'Total', (int) $customers->sum('jumlah_transaksi'), $this->rupiah($customers->sum('total_belanja')), ''];

        return $this->buildExcel(
            'Laporan Customer Terbaik',
            $this->periodeSubtitle($tahun, $bulan).' (Top 20)',
            $headers,
            $rows,
            $totalsRow,
            'laporan-customer-terbaik.xlsx',
            [],
            [5, 32, 18, 18, 18]
        );
    }

    public function inventoryTurnover(array $data)
    {
        $business = view()->shared('business');
        $products = Product::where('business_id', $business->id)
            ->with('category')->where('is_active', true)->where('stok_aktual', '>', 0)
            ->get()
            ->map(function ($p) {
                $terjual30 = SaleDetail::where('product_id', $p->id)
                    ->whereHas('sale', function ($q) {
                        $q->where('tanggal_transaksi', '>=', Carbon::now()->subDays(30));
                    })->sum('jumlah');
                $p->terjual_30hari = $terjual30;
                $avgDailySales = $terjual30 / 30;
                $p->days_in_inventory = $avgDailySales > 0 ? round($p->stok_aktual / $avgDailySales) : null;
                $p->turnover_ratio = $p->stok_aktual > 0 && $terjual30 > 0 ? round($terjual30 / $p->stok_aktual, 2) : 0;
                $p->nilai_stok = $p->stok_aktual * $p->biaya_rata_rata;
                return $p;
            })->sortByDesc('turnover_ratio');

        $headers = ['No', 'Produk', 'Kategori', 'Stok', 'Nilai Stok', 'Terjual (30hr)', 'Turnover', 'Days in Inv.'];
        $rows = [];
        foreach ($products as $i => $p) {
            $rows[] = [
                $i + 1,
                $p->nama_produk,
                $p->category->nama_kategori ?? '-',
                (int) $p->stok_aktual,
                $this->rupiah($p->nilai_stok),
                (int) $p->terjual_30hari,
                number_format($p->turnover_ratio, 2, ',', '.'),
                $p->days_in_inventory !== null ? (int) $p->days_in_inventory : '-',
            ];
        }

        return $this->buildExcel(
            'Laporan Inventory Turnover',
            '30 Hari Terakhir | Per Tanggal: '.Carbon::now()->isoFormat('D MMMM Y'),
            $headers,
            $rows,
            [],
            'laporan-inventory-turnover.xlsx',
            [],
            [5, 32, 22, 12, 18, 14, 12, 14]
        );
    }

    public function retur(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $salesReturnQuery = SalesReturn::with(['sale.customer', 'user'])->whereYear('tanggal_return', $tahun);
        $purchaseReturnQuery = PurchasesReturn::with(['purchase.supplier', 'user'])->whereYear('tanggal_return', $tahun);
        if ($bulan != '-') {
            $salesReturnQuery->whereMonth('tanggal_return', $bulan);
            $purchaseReturnQuery->whereMonth('tanggal_return', $bulan);
        }
        $salesReturns = $salesReturnQuery->orderBy('tanggal_return', 'desc')->get();
        $purchaseReturns = $purchaseReturnQuery->orderBy('tanggal_return', 'desc')->get();

        $title = 'Laporan Retur';
        $subtitle = $this->periodeSubtitle($tahun, $bulan);

        $summaryRow = [];

        $salesHeaders = ['No', 'No. Return', 'Tanggal', 'No. Invoice', 'Customer', 'Nilai Return', 'Alasan', 'Status'];
        $salesRows = [];
        foreach ($salesReturns as $index => $sr) {
            $salesRows[] = [
                $index + 1,
                $sr->no_return ?? '-',
                Carbon::parse($sr->tanggal_return)->format('d/m/Y'),
                $sr->sale->no_invoice ?? '-',
                $sr->sale->customer->nama_pelanggan ?? 'Guest',
                $this->rupiah($sr->total_return),
                $sr->alasan_return ?? '-',
                $sr->status ?? '-',
            ];
        }

        $purchaseHeaders = ['No', 'No. Return', 'Tanggal', 'No. Pembelian', 'Supplier', 'Nilai Return', 'Alasan', 'Status'];
        $purchaseRows = [];
        foreach ($purchaseReturns as $index => $pr) {
            $purchaseRows[] = [
                $index + 1,
                $pr->no_return ?? '-',
                Carbon::parse($pr->tanggal_return)->format('d/m/Y'),
                $pr->purchase->no_pembelian ?? '-',
                $pr->purchase->supplier->nama_supplier ?? '-',
                $this->rupiah($pr->total_return),
                $pr->alasan_return ?? '-',
                $pr->status ?? '-',
            ];
        }

        $excelGroups = [];
        $excelGroups[] = [
            'title' => 'A. Retur Penjualan (dari Customer)',
            'headers' => $salesHeaders,
            'rows' => $salesRows,
            'subtotals' => !empty($salesRows) ? [['', '', '', '', 'Total Retur Penjualan', $this->rupiah($salesReturns->sum('total_return')), '', '']] : [],
        ];
        $excelGroups[] = [
            'title' => 'B. Retur Pembelian (ke Supplier)',
            'headers' => $purchaseHeaders,
            'rows' => $purchaseRows,
            'subtotals' => !empty($purchaseRows) ? [['', '', '', '', 'Total Retur Pembelian', $this->rupiah($purchaseReturns->sum('total_return')), '', '']] : [],
        ];

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            $summaryRow,
            $excelGroups,
            'laporan-retur.xlsx',
            [],
            [5, 18, 14, 22, 24, 18, 32, 14]
        );
    }

    public function cashierReport(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? '-';
        $hari = $data['periode'] ?? '-';
        $userId = $data['sub_laporan'] ?? '';

        $query = cashDrawer::with(['user', 'business'])->whereYear('tanggal_buka', $tahun);
        if ($bulan != '-') $query->whereMonth('tanggal_buka', $bulan);
        if ($hari != '-') $query->whereDay('tanggal_buka', $hari);
        if ($userId != '') $query->where('user_id', $userId);

        $sessions = $query->orderBy('tanggal_buka', 'desc')->get();

        foreach ($sessions as $session) {
            $session->sales_items = SaleDetail::select(
                'product_id',
                DB::raw('SUM(jumlah) as total_qty'),
                DB::raw('SUM(subtotal) as total_amount')
            )
            ->whereHas('sale', function ($q) use ($session) {
                $q->where('user_id', $session->user_id)
                  ->where('created_at', '>=', $session->tanggal_buka);
                if ($session->tanggal_tutup) {
                    $q->where('created_at', '<=', $session->tanggal_tutup);
                }
            })
            ->groupBy('product_id')
            ->with('product')
            ->get();
        }

        $title = 'Laporan Kasir';
        $subtitle = $this->periodeSubtitle($tahun, $bulan, $hari);

        $summaryRow = [];

        $excelGroups = [];
        foreach ($sessions as $session) {
            $sessionTitle = 'Kasir: '.($session->user->nama_lengkap ?? ($session->user->name ?? '-'))
                .' | Buka: '.Carbon::parse($session->tanggal_buka)->format('d/m/Y H:i')
                .' | Tutup: '.($session->tanggal_tutup ? Carbon::parse($session->tanggal_tutup)->format('d/m/Y H:i') : 'SEKARANG');

            $itemHeaders = ['Produk', 'Qty', 'Total Penjualan'];
            $itemRows = [];
            foreach ($session->sales_items as $item) {
                $itemRows[] = [
                    $item->product->nama_produk ?? '-',
                    (int) $item->total_qty,
                    $this->rupiah($item->total_amount),
                ];
            }

            $selisih = $session->selisih ?? ((float)($session->saldo_akhir ?? 0) - (float)($session->saldo_akhir_aplikasi ?? 0));

            $excelGroups[] = [
                'title' => $sessionTitle,
                'headers' => $itemHeaders,
                'rows' => $itemRows,
                'subtotals' => [
                    [
                        'Saldo Awal: '.$this->rupiah($session->saldo_awal ?? 0),
                        'Saldo Akhir (App): '.$this->rupiah($session->saldo_akhir_aplikasi ?? 0),
                        'Saldo Akhir (Manual): '.$this->rupiah($session->saldo_akhir ?? 0),
                    ],
                    [
                        '',
                        'Selisih:',
                        $this->rupiah($selisih),
                    ],
                ],
            ];
        }

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            $summaryRow,
            $excelGroups,
            'laporan-kasir.xlsx',
            [3],
            [50, 12, 22]
        );
    }

    public function cover(array $data)
    {
        $business = Business::with('owner')->find(auth()->user()?->business_id) ?? Business::with('owner')->first();
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? '-';

        $periode = (string) $tahun;
        if ($bulan != '-') {
            try {
                $periode = Carbon::createFromDate((int) $tahun, (int) $bulan, 1)->isoFormat('MMMM').' '.$tahun;
            } catch (\Throwable $e) {
                $periode = $bulan.' '.$tahun;
            }
        }

        $headers = ['Item', 'Keterangan'];
        $rows = [
            ['Nama Usaha', $business?->nama_usaha ?? '-'],
            ['Alamat', $business?->alamat ?? '-'],
            ['No Telp', $business?->no_telp ?? '-'],
            ['Email', $business?->email ?? '-'],
            ['Periode Laporan', $periode],
            ['Tanggal Cetak', Carbon::now()->isoFormat('D MMMM Y')],
        ];

        return $this->buildExcel(
            'Halaman Sampul (Cover)',
            'Laporan Keuangan',
            $headers,
            $rows,
            [],
            'cover-laporan.xlsx',
            [],
            [22, 60]
        );
    }

    public function laporanStok(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? '-';
        $hari = $data['periode'] ?? '-';

        $endDate = Carbon::createFromDate((int) $tahun, $bulan == '-' ? 12 : (int) $bulan, 1)->endOfMonth();
        if ($hari != '-') {
            $endDate = Carbon::createFromDate((int) $tahun, $bulan == '-' ? 12 : (int) $bulan, (int) $hari);
        }
        $startDate = Carbon::createFromDate((int) $tahun, $bulan == '-' ? 1 : (int) $bulan, 1)->startOfMonth();

        $query = Product::with(['category', 'unit', 'shelf'])
            ->where('business_id', $business->id)->where('is_active', true);

        if (isset($data['sub_laporan']) && $data['sub_laporan'] != '') {
            if (str_starts_with($data['sub_laporan'], 'cat:')) {
                $query->where('category_id', str_replace('cat:', '', $data['sub_laporan']));
            } elseif (str_starts_with($data['sub_laporan'], 'rak:')) {
                $query->where('shelf_id', str_replace('rak:', '', $data['sub_laporan']));
            }
        }

        $products = $query->orderBy('nama_produk')->get()
            ->map(function ($p) use ($startDate, $endDate) {
                $masuk = $p->stockMovements()
                    ->whereIn('jenis_perubahan', ['masuk', 'pembelian', 'retur_pembelian', 'koreksi_tambah'])
                    ->whereBetween('tanggal_perubahan_stok', [$startDate, $endDate])
                    ->sum('jumlah_perubahan');
                $keluar = $p->stockMovements()
                    ->whereIn('jenis_perubahan', ['keluar', 'penjualan', 'retur_penjualan', 'koreksi_kurang', 'rusak'])
                    ->whereBetween('tanggal_perubahan_stok', [$startDate, $endDate])
                    ->sum('jumlah_perubahan');
                $p->stok_awal_periode = $p->stok_aktual - ($masuk - $keluar);
                $p->stok_masuk = (int) $masuk;
                $p->stok_keluar = (int) $keluar;
                $p->stok_akhir = $p->stok_aktual;
                $p->nilai_stok = $p->stok_aktual * $p->biaya_rata_rata;
                return $p;
            });

        $headers = ['No', 'SKU', 'Nama Produk', 'Kategori', 'Satuan', 'Rak', 'Stok Awal', 'Masuk', 'Keluar', 'Stok Akhir', 'HPP', 'Nilai Stok'];
        $rows = [];
        foreach ($products as $i => $p) {
            $rows[] = [
                $i + 1,
                $p->sku ?? '-',
                $p->nama_produk,
                $p->category->nama_kategori ?? '-',
                $p->unit->nama_satuan ?? '-',
                $p->shelf->nama_rak ?? '-',
                (int) $p->stok_awal_periode,
                (int) $p->stok_masuk,
                (int) $p->stok_keluar,
                (int) $p->stok_akhir,
                $this->rupiah($p->biaya_rata_rata),
                $this->rupiah($p->nilai_stok),
            ];
        }
        $totalsRow = ['', '', '', '', '', 'Total', '', '', '', (int) $products->sum('stok_akhir'), '', $this->rupiah($products->sum('nilai_stok'))];

        return $this->buildExcel(
            'Laporan Stok',
            $this->periodeSubtitle($tahun, $bulan, $hari),
            $headers,
            $rows,
            $totalsRow,
            'laporan-stok.xlsx',
            [],
            [5, 14, 32, 22, 12, 14, 14, 12, 12, 14, 16, 18]
        );
    }
}
