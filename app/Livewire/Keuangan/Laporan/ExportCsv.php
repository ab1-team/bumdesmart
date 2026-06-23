<?php

namespace App\Livewire\Keuangan\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AkunLevel1;
use App\Models\Business;
use App\Models\cashDrawer;
use App\Models\Inventory;
use App\Models\NumberUtil;
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

class ExportCsv extends Controller
{
    public function __invoke(Request $request)
    {
        @set_time_limit(180);
        @ini_set('memory_limit', '512M');

        $data = $request->all();
        if (! isset($data['laporan']) || ! method_exists($this, $data['laporan'])) {
            abort(404, 'Laporan tidak ditemukan');
        }

        $owner = tenant();
        $business = $owner
            ? Business::where('owner_id', $owner->id)->first()
            : (Business::find(auth()->user()?->business_id) ?? Business::first());

        view()->share('business', $business);

        return $this->{$data['laporan']}($data);
    }

    private function streamCsv(string $filename, string $title, string $subtitle, ?array $summary = null, array $sections = []): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = preg_replace('/\.pdf$/', '', $filename) . '.csv';

        return response()->streamDownload(function () use ($title, $subtitle, $summary, $sections) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, [$title], ';', '"');
            fputcsv($out, [$subtitle], ';', '"');
            fputcsv($out, [], ';', '"');

            if (! empty($summary)) {
                foreach ($summary as $line) {
                    fputcsv($out, $line, ';', '"');
                }
                fputcsv($out, [], ';', '"');
            }

            foreach ($sections as $section) {
                if (! empty($section['title'])) {
                    fputcsv($out, [$section['title']], ';', '"');
                }
                fputcsv($out, $section['headers'], ';', '"');
                foreach ($section['rows'] as $row) {
                    fputcsv($out, $this->normalizeRow($row), ';', '"');
                }
                if (! empty($section['footer'])) {
                    fputcsv($out, [], ';', '"');
                    fputcsv($out, $this->normalizeRow($section['footer']), ';', '"');
                }
                fputcsv($out, [], ';', '"');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function normalizeRow(array $row): array
    {
        return array_map(function ($v) {
            if (is_bool($v)) return $v ? '1' : '0';
            if ($v === null) return '';
            if (is_array($v)) return implode(' / ', array_filter(array_map('strval', $v), fn ($s) => $s !== ''));
            return $v;
        }, $row);
    }

    private function fmt(float $n, int $dec = 2): string
    {
        return number_format($n, $dec, '.', ',');
    }

    private function rp(float $n): string
    {
        return 'Rp ' . $this->fmt($n);
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
        $sub = 'Periode: ' . implode(' ', $parts);
        if ($hari !== null && $hari != '-') {
            $sub .= ' | Tanggal: ' . $hari;
        }
        return $sub;
    }

    private function bulanNama(string $bulan): string
    {
        try {
            return Carbon::createFromDate(null, (int) $bulan, 1)->isoFormat('MMMM');
        } catch (\Throwable $e) {
            return $bulan;
        }
    }

    // ============================================================
    // 1. PENJUALAN HARIAN
    // ============================================================
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
            'total_sales' => (float) $sales->sum('total'),
            'avg_transaction' => $sales->count() > 0 ? (float) $sales->avg('total') : 0,
        ];

        $groups = [
            'Cash' => ['items' => [], 'total' => 0],
            'Transfer/Qris' => ['items' => [], 'total' => 0],
            'Piutang' => ['items' => [], 'total' => 0],
        ];

        foreach ($sales as $sale) {
            $dibayar = (float) $sale->dibayar;
            $utang = (float) $sale->jumlah_utang;
            $metode = '-';
            if ($dibayar > 0) {
                $payment = $sale->payments->whereIn('metode_pembayaran', ['tunai', 'transfer', 'qris', 'cash'])->first();
                if ($payment) $metode = strtolower($payment->metode_pembayaran);
            }

            if ($utang > 0) {
                $groupKey = 'Piutang';
                $amount = $utang;
            } elseif (in_array($metode, ['transfer', 'qris'])) {
                $groupKey = 'Transfer/Qris';
                $amount = $dibayar > 0 ? $dibayar : (float) $sale->total;
            } else {
                $groupKey = 'Cash';
                $amount = $dibayar > 0 ? $dibayar : (float) $sale->total;
            }

            $groups[$groupKey]['items'][] = ['sale' => $sale, 'metode' => $metode, 'amount' => $amount];
            $groups[$groupKey]['total'] += $amount;
        }

        $headers = ['No', 'No. Invoice', 'Waktu', 'Pelanggan', 'Pembayaran', 'Inisial', 'Nominal', 'Status'];
        $sections = [];
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
                    $this->rp($item['amount']),
                    ucfirst($item['sale']->status ?? 'paid'),
                ];
            }
            $sections[] = [
                'title' => $groupName,
                'headers' => $headers,
                'rows' => $rows,
                'footer' => ['', '', '', '', '', 'Total ' . $groupName, $this->rp($groupData['total']), ''],
            ];
        }

        $summaryRows = [
            ['Rangkuman'],
            ['Total Penjualan', 'Jumlah Transaksi', 'Rata-rata'],
            [$this->rp($summary['total_sales']), (string) $summary['total_transactions'], $this->rp($summary['avg_transaction'])],
        ];

        return $this->streamCsv(
            'laporan-penjualan-harian.pdf',
            'Laporan Penjualan Harian',
            $this->periodeSubtitle($tahun, $bulan, $hari),
            $summaryRows,
            $sections
        );
    }

    // ============================================================
    // 2. STOK MINIMUM
    // ============================================================
    public function stokMinimum(array $data)
    {
        $query = Product::with('category')
            ->whereColumn('stok_aktual', '<=', 'stok_minimal')
            ->where('is_active', true);
        if (isset($data['sub_laporan']) && str_starts_with($data['sub_laporan'], 'cat:')) {
            $catId = str_replace('cat:', '', $data['sub_laporan']);
            $query->where('category_id', $catId);
        }
        $products = $query->get()->map(function ($p) {
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
                $p->stok_aktual,
                $p->stok_minimal,
                $p->kekurangan,
                $p->suggested_order,
            ];
        }

        return $this->streamCsv(
            'laporan-stok-minimum.pdf',
            'Laporan Stok Minimum',
            'Daftar produk yang stoknya berada di bawah batas minimum.',
            null,
            [['title' => null, 'headers' => $headers, 'rows' => $rows, 'footer' => null]]
        );
    }

    // ============================================================
    // 3. JURNAL TRANSAKSI
    // ============================================================
    public function jurnalTransaksi(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $payments = Payment::where('business_id', auth()->user()->business_id)
            ->where('tanggal_pembayaran', 'LIKE', $tahun . '-' . $bulan . '-%')
            ->with(['accountDebit', 'accountKredit', 'user'])->get();

        $headers = ['No', 'Tanggal', 'Ref ID', 'Kode Akun', 'Keterangan', 'Debit', 'Kredit', 'Ins'];
        $rows = [];
        $totalDebit = 0; $totalKredit = 0;
        foreach ($payments as $i => $p) {
            $rows[] = [
                $i + 1,
                date('Y-m-d', strtotime($p->tanggal_pembayaran)),
                $p->transaction_id ?? $p->id,
                ($p->rekening_debit ?? '-') . ' - ' . ($p->accountDebit->nama ?? ''),
                $p->catatan ?? '-',
                $this->fmt((float) $p->total_harga),
                '0',
                $p->user->initial ?? '-',
            ];
            $rows[] = [
                '',
                '',
                '',
                ($p->rekening_kredit ?? '-') . ' - ' . ($p->accountKredit->nama ?? ''),
                '',
                '0',
                $this->fmt((float) $p->total_harga),
                '',
            ];
            $totalDebit += (float) $p->total_harga;
            $totalKredit += (float) $p->total_harga;
        }

        return $this->streamCsv(
            'laporan-jurnal-transaksi.pdf',
            'Jurnal Transaksi',
            $this->periodeSubtitle($tahun, $bulan),
            null,
            [['title' => null, 'headers' => $headers, 'rows' => $rows, 'footer' => ['', '', '', '', 'Total', $this->fmt($totalDebit), $this->fmt($totalKredit), '']]]
        );
    }

    // ============================================================
    // 4. BUKU BESAR
    // ============================================================
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

        $namaBulan = $this->bulanNama($bulan);

        $balance = $akun?->balance->first();
        $saldoAwalDebit = $balance ? (float) $balance->debit_00 : 0;
        $saldoAwalKredit = $balance ? (float) $balance->kredit_00 : 0;

        $payments = Payment::where([
            ['business_id', auth()->user()->business_id],
            ['tanggal_pembayaran', 'LIKE', $tahun . '-' . $bulan . '-%'],
        ])->where(function ($q) use ($kodeAkun) {
            $q->where('rekening_debit', $kodeAkun)->orWhere('rekening_kredit', $kodeAkun);
        })->orderBy('tanggal_pembayaran', 'asc')->orderBy('id', 'asc')->get();

        $headers = ['No', 'Tanggal', 'Ref', 'Keterangan', 'Debit', 'Kredit', 'Saldo', 'P'];
        $rows = [];

        $saldo = 0;
        if ($akun && property_exists($akun, 'jenis_mutasi') && $akun->jenis_mutasi === 'debit') {
            $saldo = $saldoAwalDebit - $saldoAwalKredit;
        } else {
            $saldo = $saldoAwalKredit - $saldoAwalDebit;
        }

        $rows[] = [
            '',
            $tahun . '-01-01',
            '',
            'Komulatif Transaksi Awal Tahun ' . $tahun,
            $this->fmt($saldoAwalDebit),
            $this->fmt($saldoAwalKredit),
            ($saldo < 0 ? '(' . $this->fmt(abs($saldo)) . ')' : $this->fmt($saldo)),
            '',
        ];
        $rows[] = [
            '',
            $tahun . '-' . str_pad((string) (int) $bulan, 2, '0', STR_PAD_LEFT) . '-01',
            '',
            'Komulatif Transaksi s/d Bulan Lalu',
            '',
            '',
            '',
            '',
        ];

        $sumDebit = 0; $sumKredit = 0;
        foreach ($payments as $i => $p) {
            $debit = 0; $kredit = 0;
            $isDebit = ($p->rekening_debit === $kodeAkun);
            if ($isDebit) {
                $debit = (float) $p->total_harga;
                $saldo += $debit;
            } else {
                $kredit = (float) $p->total_harga;
                $saldo -= $kredit;
            }
            $sumDebit += $debit; $sumKredit += $kredit;
            $rows[] = [
                $i + 1,
                date('Y-m-d', strtotime($p->tanggal_pembayaran)),
                $p->id,
                $p->catatan ?? '-',
                $this->fmt($debit),
                $this->fmt($kredit),
                ($saldo < 0 ? '(' . $this->fmt(abs($saldo)) . ')' : $this->fmt($saldo)),
                $p->p ?? '',
            ];
        }

        $saldoAkhir = $saldo;
        $saldoSdBulanIni = $saldoAwalDebit - $saldoAwalKredit + $sumDebit - $sumKredit;

        return $this->streamCsv(
            'laporan-buku-besar.pdf',
            'Buku Besar ' . ($akun->nama ?? $kodeAkun) . ' (' . $kodeAkun . ')',
            'Kode Akun : ' . $kodeAkun . ' | ' . $this->periodeSubtitle($tahun, $bulan),
            null,
            [[
                'title' => null,
                'headers' => $headers,
                'rows' => $rows,
                'footer' => [
                    '', '', '', 'Total Transaksi Bulan ' . $namaBulan,
                    $this->fmt($sumDebit), $this->fmt($sumKredit),
                    ($saldoAkhir < 0 ? '(' . $this->fmt(abs($saldoAkhir)) . ')' : $this->fmt($saldoAkhir)), ''
                ],
            ]]
        );
    }

    // ============================================================
    // 5. NERACA
    // ============================================================
    public function neraca(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $akunLevel1s = AkunLevel1::with([
            'akunLevel2.akunLevel3' => function ($query) use ($business) {
                $query->where('business_id', $business->id);
            },
        ])->where('id', '<=', '3')->get();

        $headers = ['Kode', 'Nama', 'Saldo'];
        $sections = [];
        $totalLiabilitasEkuitas = 0;
        $totalAset = 0;

        foreach ($akunLevel1s as $a1) {
            $sectionRows = [];
            $sectionTotal = 0;
            foreach ($a1->akunLevel2 as $a2) {
                foreach ($a2->akunLevel3 as $a3) {
                    $saldo = 0;
                    if (method_exists(KeuanganUtil::class, 'sumSaldoAkunLevel3')) {
                        $saldo = (float) KeuanganUtil::sumSaldoAkunLevel3($a3, (int) $bulan);
                    }
                    $sectionRows[] = [$a3->kode, $a3->nama, $this->fmt($saldo)];
                    $sectionTotal += $saldo;
                }
            }
            $sectionRows[] = ['', 'Jumlah ' . $a1->nama, $this->fmt($sectionTotal)];
            $sections[] = ['title' => $a1->nama, 'headers' => $headers, 'rows' => $sectionRows, 'footer' => null];
            if ($a1->id == 1) $totalAset = $sectionTotal;
            else $totalLiabilitasEkuitas += $sectionTotal;
        }
        $sections[] = [
            'title' => 'Total',
            'headers' => $headers,
            'rows' => [['', 'Jumlah Liabilitas + Ekuitas', $this->fmt($totalLiabilitasEkuitas)]],
            'footer' => null,
        ];

        return $this->streamCsv(
            'laporan-neraca.pdf',
            'Laporan Neraca',
            $this->periodeSubtitle($tahun, $bulan),
            null,
            $sections
        );
    }

    // ============================================================
    // 6. CALK
    // ============================================================
    public function calk(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $akunLevel1s = AkunLevel1::with([
            'akunLevel2.akunLevel3.accounts' => function ($query) use ($business) {
                $query->where('business_id', $business->id);
            },
        ])->where('id', '<=', '3')->get();

        $headers = ['Kode', 'Nama', 'Saldo'];
        $sections = [];
        foreach ($akunLevel1s as $a1) {
            $sectionRows = [];
            $sectionTotal = 0;
            foreach ($a1->akunLevel2 as $a2) {
                $sectionRows[] = [$a2->kode, $a2->nama, ''];
                foreach ($a2->akunLevel3 as $a3) {
                    foreach ($a3->accounts as $acc) {
                        $saldo = (float) KeuanganUtil::sumSaldo($acc, (int) $bulan);
                        if ($acc->kode === '3.2.02.01' && method_exists(KeuanganUtil::class, 'saldoLabaRugi')) {
                            $saldo = (float) KeuanganUtil::saldoLabaRugi($tahun, $bulan);
                        }
                        $sectionRows[] = [$acc->kode, '    ' . $acc->nama, $this->fmt($saldo)];
                        $sectionTotal += $saldo;
                    }
                }
            }
            $sectionRows[] = ['', 'Jumlah ' . $a1->nama, $this->fmt($sectionTotal)];
            $sections[] = ['title' => $a1->nama, 'headers' => $headers, 'rows' => $sectionRows, 'footer' => null];
        }

        return $this->streamCsv(
            'laporan-calk.pdf',
            'Catatan Atas Laporan Keuangan (CALK)',
            $this->periodeSubtitle($tahun, $bulan),
            null,
            $sections
        );
    }

    // ============================================================
    // 7. LABA RUGI
    // ============================================================
    public function labaRugi(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $result = KeuanganUtil::labaRugi($tahun, $bulan);
        $labaRugi = $result['groups'];

        $headers = ['Kode', 'Nama Akun', 'S/D Bln Lalu', 'Bln Ini', 'S/D Bln Ini'];
        $sections = [];

        $totalPendapatanSdLalu = 0; $totalPendapatanBlnIni = 0; $totalPendapatanSdIni = 0;
        $totalBebanSdLalu = 0; $totalBebanBlnIni = 0; $totalBebanSdIni = 0;

        foreach ($labaRugi as $idx => $group) {
            $rows = [];
            $jumlahSdLalu = 0; $jumlahBlnIni = 0; $jumlahSdIni = 0;
            if (! empty($group['kode'])) {
                foreach ($group['kode'] as $kode) {
                    $rows[] = [
                        $kode['kode'] ?? '',
                        ($kode['is_bold'] ?? false ? '[B] ' : '') . ($kode['nama'] ?? ''),
                        $this->fmt((float) ($kode['saldo_sd_lalu'] ?? 0)),
                        $this->fmt((float) ($kode['saldo_bulan_ini'] ?? 0)),
                        $this->fmt((float) ($kode['saldo_sd_ini'] ?? 0)),
                    ];
                    $jumlahSdLalu += (float) ($kode['saldo_sd_lalu'] ?? 0);
                    $jumlahBlnIni += (float) ($kode['saldo_bulan_ini'] ?? 0);
                    $jumlahSdIni += (float) ($kode['saldo_sd_ini'] ?? 0);
                }
            }
            $rows[] = [
                '',
                'Jumlah ' . ($group['nama'] ?? ''),
                $this->fmt($jumlahSdLalu),
                $this->fmt($jumlahBlnIni),
                $this->fmt($jumlahSdIni),
            ];

            if ($idx === 0) {
                $totalPendapatanSdLalu = $jumlahSdLalu;
                $totalPendapatanBlnIni = $jumlahBlnIni;
                $totalPendapatanSdIni = $jumlahSdIni;
            } elseif ($idx === 2) {
                $totalBebanSdLalu = $jumlahSdLalu;
                $totalBebanBlnIni = $jumlahBlnIni;
                $totalBebanSdIni = $jumlahSdIni;
            }

            $sections[] = ['title' => strtoupper($group['nama'] ?? '-'), 'headers' => $headers, 'rows' => $rows, 'footer' => null];
        }

        $labaKotorSdLalu = $totalPendapatanSdLalu - $totalBebanSdLalu;
        $labaKotorBlnIni = $totalPendapatanBlnIni - $totalBebanBlnIni;
        $labaKotorSdIni = $totalPendapatanSdIni - $totalBebanSdIni;

        $sections[] = [
            'title' => 'Ringkasan',
            'headers' => $headers,
            'rows' => [
                ['', 'Total Pendapatan', $this->fmt($totalPendapatanSdLalu), $this->fmt($totalPendapatanBlnIni), $this->fmt($totalPendapatanSdIni)],
                ['', 'LABA KOTOR', $this->fmt($labaKotorSdLalu), $this->fmt($labaKotorBlnIni), $this->fmt($labaKotorSdIni)],
                ['', 'Total Beban', $this->fmt($totalBebanSdLalu), $this->fmt($totalBebanBlnIni), $this->fmt($totalBebanSdIni)],
                ['', 'Laba Sebelum Pajak', $this->fmt($labaKotorSdLalu), $this->fmt($labaKotorBlnIni), $this->fmt($labaKotorSdIni)],
                ['', 'Laba Bersih', $this->fmt($labaKotorSdLalu), $this->fmt($labaKotorBlnIni), $this->fmt($labaKotorSdIni)],
            ],
            'footer' => null,
        ];

        return $this->streamCsv(
            'laporan-laba-rugi.pdf',
            'Laporan Laba Rugi',
            $this->periodeSubtitle($tahun, $bulan),
            null,
            $sections
        );
    }

    // ============================================================
    // 8. ARUS KAS
    // ============================================================
    public function arusKas(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $arusKas = KeuanganUtil::arusKas($tahun, $bulan);
        $saldoKas = (float) KeuanganUtil::saldoKas($tahun, max((int) $bulan - 1, 0));

        $headers = ['No', 'Nama Akun', 'Saldo'];
        $rows = [];
        $no = 0;
        foreach ($arusKas as $root) {
            $no++;
            if (! empty($root['children'])) {
                foreach ($root['children'] as $child) {
                    $rows[] = [
                        $no,
                        ($child['nama'] ?? '') . ' (dari ' . ($root['nama'] ?? '') . ')',
                        $this->fmt((float) $child['total']),
                    ];
                }
            }
            $rows[] = [
                $no,
                'Kas Bersih aktivitas ' . ($root['nama'] ?? ''),
                $this->fmt((float) ($root['total'] ?? 0)),
            ];
        }
        $rows[] = ['', 'Kenaikan (Penurunan) Kas', ''];
        $rows[] = ['', 'SALDO AKHIR KAS SETARA KAS', $this->fmt($saldoKas)];

        return $this->streamCsv(
            'laporan-arus-kas.pdf',
            'Laporan Arus Kas',
            $this->periodeSubtitle($tahun, $bulan),
            null,
            [['title' => null, 'headers' => $headers, 'rows' => $rows, 'footer' => null]]
        );
    }

    // ============================================================
    // 9. ASET TETAP INVENTARIS
    // ============================================================
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

        $kategoriNaman = [1 => 'Tanah', 2 => 'Gedung/Bangunan', 3 => 'Kendaraan', 4 => 'Peralatan'];

        $sections = [];
        $no = 0;
        foreach ($inventarisGroups as $kategori => $items) {
            $namaKategori = $kategoriNaman[$kategori] ?? 'Kategori ' . $kategori;
            $rows = [];
            $subtotal = 0;
            foreach ($items as $inv) {
                $no++;
                $nilaiBuku = 0;
                try {
                    $nilaiBuku = (float) InventarisUtil::nilaiBuku($tgl_kondisi, $inv);
                } catch (\Throwable $e) {
                    $nilaiBuku = (float) $inv->harga_satuan;
                }
                $perolehan = (float) $inv->harga_satuan * (int) ($inv->jumlah ?? 1);
                $rows[] = [
                    $no,
                    $inv->tanggal_beli ? Carbon::parse($inv->tanggal_beli)->format('d/m/Y') : '-',
                    $inv->nama_barang,
                    $inv->id,
                    ucfirst($inv->status),
                    $inv->jumlah ?? 1,
                    $this->fmt((float) $inv->harga_satuan),
                    $this->fmt($perolehan),
                    $inv->umur_ekonomis ?? '',
                    $this->fmt(0),
                    '',
                    $this->fmt(0),
                    '',
                    $this->fmt(0),
                    $this->fmt($nilaiBuku),
                ];
                $subtotal += $nilaiBuku;
            }
            $rows[] = ['', '', '', '', '', '', '', 'Jumlah ' . $namaKategori, '', '', '', '', '', '', $this->fmt($subtotal)];

            $sections[] = [
                'title' => $namaKategori,
                'headers' => ['No', 'Tgl Beli', 'Nama Barang', 'Id', 'Kondisi', 'Unit', 'Harga Satuan', 'Harga Perolehan', 'Umur Eko.', 'Satuan Susut', 'Tahun Ini - Umur', 'Tahun Ini - Biaya', 's.d. Tahun Ini - Umur', 's.d. Tahun Ini - Biaya', 'Nilai Buku'],
                'rows' => $rows,
                'footer' => null,
            ];
        }

        return $this->streamCsv(
            'laporan-aset-tetap-inventaris.pdf',
            'Aset Tetap dan Inventaris',
            $this->periodeSubtitle($tahun, $bulan),
            null,
            $sections
        );
    }

    // ============================================================
    // 10. PENJUALAN PRODUK
    // ============================================================
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
        $total = (float) $sales->sum('subtotal');

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
                $this->fmt((float) $row->jumlah),
                $this->fmt((float) $row->harga_satuan),
                $this->fmt((float) $row->subtotal),
            ];
        }

        return $this->streamCsv(
            'laporan-penjualan-produk.pdf',
            'Laporan Penjualan Produk',
            $this->periodeSubtitle($tahun, $bulan, $hari),
            null,
            [['title' => null, 'headers' => $headers, 'rows' => $rows, 'footer' => ['', '', '', '', '', '', '', '', 'Total:', $this->fmt($total)]]]
        );
    }

    // ============================================================
    // 11. PEMBELIAN PRODUK
    // ============================================================
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
        $total = (float) $purchases->sum('subtotal');

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
                $this->fmt((float) $row->jumlah),
                $this->fmt((float) $row->harga_satuan),
                $this->fmt((float) $row->subtotal),
            ];
        }

        return $this->streamCsv(
            'laporan-pembelian-produk.pdf',
            'Laporan Pembelian Produk',
            $this->periodeSubtitle($tahun, $bulan, $hari),
            null,
            [['title' => null, 'headers' => $headers, 'rows' => $rows, 'footer' => ['', '', '', '', '', '', '', '', 'Total:', $this->fmt($total)]]]
        );
    }

    // ============================================================
    // 12. PRODUK TERLARIS
    // ============================================================
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
                number_format($item->total_terjual),
                $this->rp((float) $item->total_revenue),
                $this->rp((float) $item->total_profit),
            ];
            $sumQty += (float) $item->total_terjual;
            $sumRev += (float) $item->total_revenue;
            $sumProf += (float) $item->total_profit;
        }

        return $this->streamCsv(
            'laporan-produk-terlaris.pdf',
            'Laporan Produk Terlaris',
            $this->periodeSubtitle($tahun, $bulan) . ' (Top 20)',
            null,
            [['title' => null, 'headers' => $headers, 'rows' => $rows, 'footer' => ['', '', 'Total', number_format($sumQty), $this->rp($sumRev), $this->rp($sumProf)]]]
        );
    }

    // ============================================================
    // 13. PIUTANG
    // ============================================================
    public function piutang(array $data)
    {
        $sales = Sale::with('customer')
            ->where('jumlah_utang', '>', 0)
            ->orderBy('tanggal_transaksi', 'asc')->get();

        $grouped = [];
        foreach ($sales as $sale) {
            $custId = $sale->customer_id ?? 0;
            if (! isset($grouped[$custId])) {
                $grouped[$custId] = [
                    'customer' => $sale->customer,
                    'jumlah_invoice' => 0,
                    'items' => [],
                ];
            }
            $grouped[$custId]['items'][] = $sale;
            $grouped[$custId]['jumlah_invoice']++;
        }

        $totalPiutang = 0;
        $sections = [];
        $headers = ['No. Invoice', 'Tanggal', 'Total', 'Dibayar', 'Sisa Piutang', 'Umur (Hari)'];
        foreach ($grouped as $g) {
            $rows = [];
            $subtotal = 0;
            foreach ($g['items'] as $sale) {
                $umur = Carbon::parse($sale->tanggal_transaksi)->diffInDays(Carbon::now());
                $rows[] = [
                    $sale->no_invoice ?? '-',
                    Carbon::parse($sale->tanggal_transaksi)->format('d/m/Y'),
                    $this->rp((float) $sale->total),
                    $this->rp((float) $sale->dibayar),
                    $this->rp((float) $sale->jumlah_utang),
                    $umur . ' hari',
                ];
                $subtotal += (float) $sale->jumlah_utang;
            }
            $rows[] = ['', '', '', 'Subtotal ' . ($g['customer']->nama_pelanggan ?? 'Guest'), $this->rp($subtotal), ''];
            $sections[] = [
                'title' => ($g['customer']->nama_pelanggan ?? 'Guest') . ' (' . $g['jumlah_invoice'] . ' invoice)',
                'headers' => $headers,
                'rows' => $rows,
                'footer' => null,
            ];
            $totalPiutang += $subtotal;
        }

        return $this->streamCsv(
            'laporan-piutang.pdf',
            'Laporan Piutang (Customer)',
            'Total Piutang: ' . $this->rp($totalPiutang) . ' | Per Tanggal: ' . Carbon::now()->isoFormat('D MMMM Y'),
            null,
            $sections
        );
    }

    // ============================================================
    // 14. HUTANG
    // ============================================================
    public function hutang(array $data)
    {
        $purchases = Purchase::with('supplier')
            ->where('jumlah_utang', '>', 0)
            ->orderBy('tanggal_pembelian', 'asc')->get();

        $grouped = [];
        foreach ($purchases as $purchase) {
            $supId = $purchase->supplier_id ?? 0;
            if (! isset($grouped[$supId])) {
                $grouped[$supId] = [
                    'supplier' => $purchase->supplier,
                    'jumlah_po' => 0,
                    'items' => [],
                ];
            }
            $grouped[$supId]['items'][] = $purchase;
            $grouped[$supId]['jumlah_po']++;
        }

        $totalHutang = 0;
        $sections = [];
        $headers = ['No. Pembelian', 'Tanggal', 'Total', 'Dibayar', 'Sisa Hutang', 'Umur (Hari)'];
        foreach ($grouped as $g) {
            $rows = [];
            $subtotal = 0;
            foreach ($g['items'] as $purchase) {
                $umur = Carbon::parse($purchase->tanggal_pembelian)->diffInDays(Carbon::now());
                $rows[] = [
                    $purchase->no_pembelian ?? '-',
                    Carbon::parse($purchase->tanggal_pembelian)->format('d/m/Y'),
                    $this->rp((float) $purchase->total),
                    $this->rp((float) $purchase->dibayar),
                    $this->rp((float) $purchase->jumlah_utang),
                    $umur . ' hari',
                ];
                $subtotal += (float) $purchase->jumlah_utang;
            }
            $rows[] = ['', '', '', 'Subtotal ' . ($g['supplier']->nama_supplier ?? '-'), $this->rp($subtotal), ''];
            $sections[] = [
                'title' => ($g['supplier']->nama_supplier ?? '-') . ' (' . $g['jumlah_po'] . ' PO)',
                'headers' => $headers,
                'rows' => $rows,
                'footer' => null,
            ];
            $totalHutang += $subtotal;
        }

        return $this->streamCsv(
            'laporan-hutang.pdf',
            'Laporan Hutang (Supplier)',
            'Total Hutang: ' . $this->rp($totalHutang) . ' | Per Tanggal: ' . Carbon::now()->isoFormat('D MMMM Y'),
            null,
            $sections
        );
    }

    // ============================================================
    // 15. STOK OPNAME
    // ============================================================
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

        $headers = ['No', 'Produk', 'Stok Sistem', 'Stok Fisik', 'Selisih', 'Jenis', 'Nilai Selisih', 'Alasan'];
        $sections = [];
        $no = 0;
        foreach ($opnames as $op) {
            $rows = [];
            foreach ($op->details as $d) {
                $no++;
                $rows[] = [
                    $no,
                    ($d->product->nama_produk ?? '-') . ' (' . ($d->product->kode_produk ?? '-') . ')',
                    $d->stok_sistem,
                    $d->stok_fisik,
                    ($d->selisih > 0 ? '+' : '') . $d->selisih,
                    $d->jenis_selisih ?? '-',
                    $this->rp((float) ($d->total_harga ?? 0)),
                    $d->alasan ?? '-',
                ];
            }
            $sections[] = [
                'title' => $op->no_opname . ' | ' . Carbon::parse($op->tanggal_opname)->format('d/m/Y') . ' | Status: ' . ($op->status ?? '-') . ' | Petugas: ' . ($op->user->name ?? '-'),
                'headers' => $headers,
                'rows' => $rows,
                'footer' => null,
            ];
        }

        return $this->streamCsv(
            'laporan-stok-opname.pdf',
            'Laporan Stok Opname',
            $this->periodeSubtitle($tahun, $bulan),
            null,
            $sections
        );
    }

    // ============================================================
    // 16. BUKTI STOK OPNAME
    // ============================================================
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

        $headers = ['No', 'Produk', 'Stok Sistem', 'Stok Fisik', 'Selisih', 'Alasan'];
        $rows = [];
        foreach ($opname->details as $i => $d) {
            $rows[] = [
                $i + 1,
                ($d->product->nama_produk ?? '-') . ' (' . ($d->product->kode_produk ?? '-') . ')',
                $d->stok_sistem,
                $d->stok_fisik,
                ($d->selisih > 0 ? '+' : '') . $d->selisih,
                $d->alasan ?? '-',
            ];
        }

        $infoRows = [
            ['No Opname', $opname->no_opname, 'Tanggal', Carbon::parse($opname->tanggal_opname)->format('d F Y'), 'Status', strtoupper($opname->status ?? '-')],
            ['Petugas', $opname->user->nama_lengkap ?? '-', 'Disetujui Oleh', $opname->approvedBy->nama_lengkap ?? '-'],
            ['Catatan', $opname->catatan ?? '-'],
        ];

        return $this->streamCsv(
            'bukti-so-' . $opname->no_opname . '.pdf',
            'Bukti Stock Opname ' . $opname->no_opname,
            'Tanggal: ' . Carbon::parse($opname->tanggal_opname)->format('d/m/Y') . ' | Status: ' . strtoupper($opname->status ?? '-'),
            $infoRows,
            [['title' => null, 'headers' => $headers, 'rows' => $rows, 'footer' => null]]
        );
    }

    // ============================================================
    // 17. FORM STOCK OPNAME
    // ============================================================
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
        $infoRows = [
            ['Lokasi/Rak', $shelfName],
            ['Kategori', $categoryName],
            ['Catatan', $catatan],
        ];

        $headers = ['No', 'Kode Produk', 'Nama Produk', 'Sistem', 'Fisik', 'Ket.'];
        $rows = [];
        $i = 0;
        foreach ($products as $p) {
            $i++;
            $stokFmt = class_exists(NumberUtil::class) ? NumberUtil::format($p->stok_aktual) : (string) $p->stok_aktual;
            $rows[] = [
                $i,
                $p->sku ?? ($p->kode_produk ?? '-'),
                $p->nama_produk,
                $stokFmt,
                '',
                '',
            ];
        }

        return $this->streamCsv(
            'form-stock-opname.pdf',
            'Form Stock Opname (Lembar Kerja)',
            'Per Tanggal: ' . Carbon::now()->isoFormat('D MMMM Y'),
            $infoRows,
            [['title' => null, 'headers' => $headers, 'rows' => $rows, 'footer' => null]]
        );
    }

    // ============================================================
    // 18. PEMBELIAN
    // ============================================================
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
            'total_pembelian' => (float) $purchases->sum('total'),
            'total_dibayar' => (float) $purchases->sum('dibayar'),
            'total_hutang' => (float) $purchases->sum('jumlah_utang'),
        ];

        $headers = ['No', 'No. Pembelian', 'Tanggal', 'Supplier', 'Pembayaran', 'Total', 'Hutang', 'Status'];
        $rows = [];
        $sumTotal = 0; $sumHutang = 0;
        foreach ($purchases as $i => $purchase) {
            $rows[] = [
                $i + 1,
                $purchase->no_pembelian ?? '-',
                Carbon::parse($purchase->tanggal_pembelian)->format('d/m/Y'),
                $purchase->supplier->nama_supplier ?? '-',
                ucfirst($purchase->jenis_pembayaran ?? '-'),
                $this->rp((float) $purchase->total),
                $this->rp((float) $purchase->jumlah_utang),
                $purchase->status ?? '-',
            ];
            $sumTotal += (float) $purchase->total;
            $sumHutang += (float) $purchase->jumlah_utang;
        }

        $summaryRows = [
            ['Rangkuman'],
            ['Jumlah PO', 'Total Pembelian', 'Total Dibayar', 'Total Hutang'],
            [(string) $summary['total_po'], $this->rp($summary['total_pembelian']), $this->rp($summary['total_dibayar']), $this->rp($summary['total_hutang'])],
            [],
            ['Rincian Pembelian'],
        ];

        return $this->streamCsv(
            'laporan-pembelian.pdf',
            'Laporan Pembelian',
            $this->periodeSubtitle($tahun, $bulan),
            $summaryRows,
            [['title' => null, 'headers' => $headers, 'rows' => $rows, 'footer' => ['', '', '', '', 'Total', $this->rp($sumTotal), $this->rp($sumHutang), '']]]
        );
    }

    // ============================================================
    // 19. MARGIN PRODUK
    // ============================================================
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
                $this->rp((float) $p->biaya_rata_rata),
                $this->rp((float) $p->harga_jual),
                $this->rp((float) $p->margin_rp),
                number_format((float) $p->margin_pct, 1) . '%',
            ];
        }

        return $this->streamCsv(
            'laporan-margin-produk.pdf',
            'Laporan Margin & Profitabilitas Produk',
            'Per Tanggal: ' . Carbon::now()->isoFormat('D MMMM Y'),
            null,
            [['title' => null, 'headers' => $headers, 'rows' => $rows, 'footer' => null]]
        );
    }

    // ============================================================
    // 20. CUSTOMER TERBAIK
    // ============================================================
    public function customerTerbaik(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $customers = Sale::select(
            'customer_id',
            DB::raw('COUNT(*) as jumlah_transaksi'),
            DB::raw('SUM(total) as total_belanja'),
            DB::raw('AVG(total) as rata_rata')
        )
            ->where('business_id', $business->id)
            ->whereYear('tanggal_transaksi', $tahun);
        if ($bulan != '-') $customers = $customers->whereMonth('tanggal_transaksi', $bulan);

        $customers = $customers->groupBy('customer_id')->orderByDesc('total_belanja')->limit(20)
            ->with('customer')->get();

        $headers = ['No', 'Nama Customer', 'Jumlah Transaksi', 'Total Belanja', 'Rata-rata'];
        $rows = [];
        $sumTrans = 0; $sumBelanja = 0;
        foreach ($customers as $i => $c) {
            $rows[] = [
                $i + 1,
                $c->customer->nama_pelanggan ?? 'Guest',
                number_format($c->jumlah_transaksi),
                $this->rp((float) $c->total_belanja),
                $this->rp((float) $c->rata_rata),
            ];
            $sumTrans += (int) $c->jumlah_transaksi;
            $sumBelanja += (float) $c->total_belanja;
        }

        return $this->streamCsv(
            'laporan-customer-terbaik.pdf',
            'Laporan Customer Terbaik',
            $this->periodeSubtitle($tahun, $bulan) . ' (Top 20)',
            null,
            [['title' => null, 'headers' => $headers, 'rows' => $rows, 'footer' => ['', 'Total', number_format($sumTrans), $this->rp($sumBelanja), '']]]
        );
    }

    // ============================================================
    // 21. INVENTORY TURNOVER
    // ============================================================
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
                $p->stok_aktual,
                $this->rp((float) $p->nilai_stok),
                $p->terjual_30hari,
                $p->turnover_ratio . 'x',
                $p->days_in_inventory !== null ? $p->days_in_inventory . ' hari' : '-',
            ];
        }

        $footerRows = [
            ['Keterangan:'],
            ['🟢 Turnover >= 2x = Fast Moving'],
            ['🟡 Turnover 1-2x = Normal'],
            ['🔴 Turnover < 1x = Slow Moving'],
        ];

        return $this->streamCsv(
            'laporan-inventory-turnover.pdf',
            'Laporan Inventory Turnover',
            '30 Hari Terakhir | Per Tanggal: ' . Carbon::now()->isoFormat('D MMMM Y'),
            $footerRows,
            [['title' => null, 'headers' => $headers, 'rows' => $rows, 'footer' => null]]
        );
    }

    // ============================================================
    // 22. RETUR
    // ============================================================
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

        $headers = ['No', 'No. Return', 'Tanggal', 'No. Referensi', 'Customer/Supplier', 'Nilai Return', 'Alasan', 'Status'];

        $sections = [];

        $rowsA = [];
        $totalA = 0;
        foreach ($salesReturns as $i => $sr) {
            $rowsA[] = [
                $i + 1,
                $sr->no_return ?? '-',
                Carbon::parse($sr->tanggal_return)->format('d/m/Y'),
                $sr->sale->no_invoice ?? '-',
                $sr->sale->customer->nama_pelanggan ?? 'Guest',
                $this->rp((float) $sr->total_return),
                $sr->alasan_return ?? '-',
                $sr->status ?? '-',
            ];
            $totalA += (float) $sr->total_return;
        }
        $rowsA[] = ['', '', '', '', 'Total Retur Penjualan', $this->rp($totalA), '', ''];
        $sections[] = ['title' => 'A. Retur Penjualan (dari Customer)', 'headers' => $headers, 'rows' => $rowsA, 'footer' => null];

        $headers[3] = 'No. Pembelian';
        $headers[4] = 'Supplier';
        $rowsB = [];
        $totalB = 0;
        foreach ($purchaseReturns as $i => $pr) {
            $rowsB[] = [
                $i + 1,
                $pr->no_return ?? '-',
                Carbon::parse($pr->tanggal_return)->format('d/m/Y'),
                $pr->purchase->no_pembelian ?? '-',
                $pr->purchase->supplier->nama_supplier ?? '-',
                $this->rp((float) $pr->total_return),
                $pr->alasan_return ?? '-',
                $pr->status ?? '-',
            ];
            $totalB += (float) $pr->total_return;
        }
        $rowsB[] = ['', '', '', '', 'Total Retur Pembelian', $this->rp($totalB), '', ''];
        $sections[] = ['title' => 'B. Retur Pembelian (ke Supplier)', 'headers' => $headers, 'rows' => $rowsB, 'footer' => null];

        return $this->streamCsv(
            'laporan-retur.pdf',
            'Laporan Retur',
            $this->periodeSubtitle($tahun, $bulan),
            null,
            $sections
        );
    }

    // ============================================================
    // 23. CASHIER REPORT
    // ============================================================
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

        $sections = [];
        foreach ($sessions as $s) {
            $items = $s->sales_items ?? collect([]);
            $headers = ['No', 'Produk', 'Qty', 'Total Penjualan'];
            $rows = [];
            foreach ($items as $i => $item) {
                $rows[] = [
                    $i + 1,
                    $item->product->nama_produk ?? '-',
                    $item->total_qty ?? 0,
                    $this->rp((float) ($item->total_amount ?? 0)),
                ];
            }
            $infoRows = [
                ['Kasir', $s->user->nama_lengkap ?? $s->user->name ?? '-'],
                ['Buka', Carbon::parse($s->tanggal_buka)->format('d/m/Y H:i')],
                ['Tutup', $s->tanggal_tutup ? Carbon::parse($s->tanggal_tutup)->format('d/m/Y H:i') : 'SEKARANG'],
                [],
                ['Saldo Awal', $this->rp((float) ($s->saldo_awal ?? 0)), 'Saldo Akhir (App)', $this->rp((float) ($s->saldo_akhir_aplikasi ?? 0)), 'Saldo Akhir (Manual)', $this->rp((float) ($s->saldo_akhir ?? 0)), 'Selisih', $this->rp((float) ($s->selisih ?? 0))],
            ];
            $sections[] = ['title' => null, 'headers' => $headers, 'rows' => $rows, 'footer' => null, 'info' => $infoRows];
        }

        return $this->streamCsv(
            'laporan-kasir.pdf',
            'Laporan Kasir',
            $this->periodeSubtitle($tahun, $bulan, $hari),
            null,
            $sections
        );
    }

    // ============================================================
    // 24. COVER
    // ============================================================
    public function cover(array $data)
    {
        $business = Business::with('owner')->find(auth()->user()?->business_id) ?? Business::with('owner')->first();
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? '-';

        $periode = (string) $tahun;
        if ($bulan != '-') {
            try {
                $periode = Carbon::createFromDate((int) $tahun, (int) $bulan, 1)->isoFormat('MMMM') . ' ' . $tahun;
            } catch (\Throwable $e) {
                $periode = $bulan . ' ' . $tahun;
            }
        }

        $infoRows = [
            ['LAPORAN KEUANGAN'],
            ['Unit Usaha BUMDes'],
            ['PERIODE: ' . $periode],
            ['Tanggal Cetak: ' . Carbon::now()->isoFormat('D MMMM Y')],
            [],
            ['Nama Usaha', $business?->nama_usaha ?? '-'],
            ['Alamat', $business?->alamat ?? '-'],
            ['No Telp', $business?->no_telp ?? '-'],
            ['Email', $business?->email ?? '-'],
        ];

        return $this->streamCsv(
            'cover-laporan.pdf',
            'Halaman Sampul (Cover)',
            'Laporan Keuangan',
            $infoRows,
            []
        );
    }

    // ============================================================
    // 25. LAPORAN STOK
    // ============================================================
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

        $summary = [
            'total_produk' => $products->count(),
            'total_nilai_stok' => (float) $products->sum('nilai_stok'),
        ];

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
                $p->stok_awal_periode,
                $p->stok_masuk,
                $p->stok_keluar,
                $p->stok_akhir,
                $this->fmt((float) $p->biaya_rata_rata),
                $this->fmt((float) $p->nilai_stok),
            ];
        }

        $summaryRows = [
            ['Laporan Stok per Periode'],
            ['Total Produk', (string) $summary['total_produk'] . ' item'],
            ['Total Nilai Stok', $this->rp($summary['total_nilai_stok'])],
        ];

        return $this->streamCsv(
            'laporan-stok.pdf',
            'Laporan Stok (Per Periode)',
            $this->periodeSubtitle($tahun, $bulan, $hari),
            $summaryRows,
            [['title' => null, 'headers' => $headers, 'rows' => $rows, 'footer' => ['', '', '', '', '', 'Total', '', '', (string) $products->sum('stok_akhir'), '', $this->fmt((float) $products->sum('nilai_stok'))]]]
        );
    }
}
