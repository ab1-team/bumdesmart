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

class ExportCsv extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->all();

        if (! isset($data['laporan']) || ! method_exists($this, $data['laporan'])) {
            abort(404, 'Laporan tidak ditemukan');
        }

        $owner = tenant();
        $business = $owner
            ? Business::where('owner_id', $owner->id)->first()
            : (Business::find(auth()->user()?->business_id) ?? Business::first());

        view()->share('business', $business);

        $payload = $this->{$data['laporan']}($data);

        $filename = $payload['filename'] ?? 'laporan.csv';
        $title = $payload['title'] ?? '';
        $subtitle = $payload['subtitle'] ?? '';
        $headers = $payload['headers'] ?? [];
        $rows = $payload['rows'] ?? [];
        $totals = $payload['totals'] ?? [];
        $numberCols = $payload['numberCols'] ?? [];

        return $this->streamCsv($filename, $title, $subtitle, $headers, $rows, $totals, $numberCols);
    }

    private function streamCsv(string $filename, string $title, string $subtitle, array $headers, array $rows, array $totals, array $numberCols): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headersCount = count($headers);
        $filename = preg_replace('/\.xlsx$/', '', $filename) . '.csv';

        return response()->streamDownload(function () use ($title, $subtitle, $headers, $rows, $totals, $headersCount) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($out, [$title], ';', '"');
            fputcsv($out, [$subtitle], ';', '"');
            fputcsv($out, [], ';', '"');
            fputcsv($out, $headers, ';', '"');

            foreach ($rows as $row) {
                fputcsv($out, $this->normalizeRow($row), ';', '"');
            }

            if (! empty($totals)) {
                fputcsv($out, [], ';', '"');
                fputcsv($out, $this->normalizeRow($totals), ';', '"');
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
            return $v;
        }, $row);
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

        $headers = ['No', 'Tanggal', 'No Invoice', 'Pelanggan', 'Kasir', 'Metode', 'Status', 'Total', 'Dibayar', 'Utang'];
        $rows = [];
        $totalAll = 0; $totalBayar = 0; $totalUtang = 0;
        foreach ($sales as $i => $sale) {
            $dibayar = (float) $sale->dibayar;
            $utang = (float) $sale->jumlah_utang;
            $metode = '-';
            if ($dibayar > 0) {
                $payment = $sale->payments->whereIn('metode_pembayaran', ['tunai', 'transfer', 'qris', 'cash'])->first();
                if ($payment) $metode = strtoupper($payment->metode_pembayaran);
            }
            $rows[] = [
                $i + 1,
                Carbon::parse($sale->tanggal_transaksi)->format('d/m/Y H:i'),
                $sale->no_invoice ?? '-',
                $sale->customer->nama_pelanggan ?? 'Guest',
                $sale->user->nama_lengkap ?? '-',
                $metode,
                ucfirst($sale->status ?? 'paid'),
                (float) $sale->total,
                $dibayar,
                $utang,
            ];
            $totalAll += (float) $sale->total;
            $totalBayar += $dibayar;
            $totalUtang += $utang;
        }

        return [
            'title' => 'Laporan Penjualan Harian',
            'subtitle' => $this->periodeSubtitle($tahun, $bulan, $hari),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => ['', '', '', '', '', '', '', $totalAll, $totalBayar, $totalUtang],
            'numberCols' => [8, 9, 10],
            'filename' => 'laporan-penjualan-harian.csv',
        ];
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

        $headers = ['No', 'Produk', 'Kategori', 'Stok Aktual', 'Stok Minimal', 'Kekurangan', 'Saran Order'];
        $rows = [];
        foreach ($products as $i => $p) {
            $rows[] = [
                $i + 1,
                $p->nama_produk ?? $p->product_name,
                $p->category->nama_kategori ?? '-',
                (int) $p->stok_aktual,
                (int) $p->stok_minimal,
                (int) $p->kekurangan,
                (int) $p->suggested_order,
            ];
        }

        return [
            'title' => 'Laporan Stok Minimum',
            'subtitle' => 'Periode: ' . Carbon::now()->isoFormat('MMMM Y'),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => [],
            'filename' => 'laporan-stok-minimum.csv',
        ];
    }

    public function jurnalTransaksi(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $payments = Payment::where('business_id', auth()->user()->business_id)
            ->where('tanggal_pembayaran', 'LIKE', $tahun . '-' . $bulan . '-%')
            ->with(['accountDebit', 'accountKredit', 'user'])->get();

        $headers = ['No', 'Tanggal', 'No Jurnal', 'Rekening Debit', 'Rekening Kredit', 'Keterangan', 'User', 'Nominal'];
        $rows = [];
        $total = 0;
        foreach ($payments as $i => $p) {
            $rows[] = [
                $i + 1,
                Carbon::parse($p->tanggal_pembayaran)->format('d/m/Y'),
                $p->no_jurnal ?? '-',
                ($p->rekening_debit ?? '-') . ' ' . ($p->accountDebit->nama ?? ''),
                ($p->rekening_kredit ?? '-') . ' ' . ($p->accountKredit->nama ?? ''),
                $p->keterangan ?? '-',
                $p->user->nama_lengkap ?? '-',
                (float) $p->nominal,
            ];
            $total += (float) $p->nominal;
        }

        return [
            'title' => 'Jurnal Transaksi',
            'subtitle' => $this->periodeSubtitle($tahun, $bulan),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => ['', '', '', '', '', '', '', $total],
            'numberCols' => [8],
            'filename' => 'laporan-jurnal-transaksi.csv',
        ];
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
            ['tanggal_pembayaran', 'LIKE', $tahun . '-' . $bulan . '-%'],
        ])->where(function ($q) use ($kodeAkun) {
            $q->where('rekening_debit', $kodeAkun)->orWhere('rekening_kredit', $kodeAkun);
        })->orderBy('tanggal_pembayaran', 'asc')->orderBy('id', 'asc')->get();

        $headers = ['No', 'Tanggal', 'Keterangan', 'Rekening Lawan', 'Debit', 'Kredit', 'Saldo'];
        $rows = [];
        $saldo = 0;
        foreach ($payments as $i => $p) {
            $debit = (float) $p->nominal;
            $kredit = 0.0;
            $isDebit = ($p->rekening_debit === $kodeAkun);
            if (! $isDebit) {
                $debit = 0;
                $kredit = (float) $p->nominal;
            }
            $saldo += ($debit - $kredit);
            $rows[] = [
                $i + 1,
                Carbon::parse($p->tanggal_pembayaran)->format('d/m/Y'),
                $p->keterangan ?? '-',
                $isDebit ? ($p->rekening_kredit ?? '-') : ($p->rekening_debit ?? '-'),
                $debit,
                $kredit,
                $saldo,
            ];
        }

        return [
            'title' => 'Buku Besar ' . ($akun->nama ?? $kodeAkun) . ' (' . $kodeAkun . ')',
            'subtitle' => $this->periodeSubtitle($tahun, $bulan),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => [],
            'numberCols' => [5, 6, 7],
            'filename' => 'laporan-buku-besar-' . str_replace('.', '_', $kodeAkun) . '.csv',
        ];
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

        $headers = ['Kelompok', 'Kode', 'Akun', 'Saldo'];
        $rows = [];
        foreach ($akunLevel1s as $a1) {
            foreach ($a1->akunLevel2 as $a2) {
                foreach ($a2->akunLevel3 as $a3) {
                    foreach ($a3->accounts as $acc) {
                        $saldo = (float) KeuanganUtil::sumSaldo($acc, (int) $bulan);
                        $rows[] = [
                            $a1->nama,
                            $acc->kode,
                            $acc->nama,
                            $saldo,
                        ];
                    }
                }
            }
        }
        $total = array_sum(array_column($rows, 3));

        return [
            'title' => 'Laporan Neraca',
            'subtitle' => $this->periodeSubtitle($tahun, $bulan),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => ['', '', 'Total', $total],
            'numberCols' => [4],
            'filename' => 'laporan-neraca.csv',
        ];
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

        $headers = ['Kelompok', 'Kode Akun', 'Nama Akun', 'Keterangan / Catatan', 'Saldo'];
        $rows = [];
        foreach ($akunLevel1s as $a1) {
            foreach ($a1->akunLevel2 as $a2) {
                foreach ($a2->akunLevel3 as $a3) {
                    foreach ($a3->accounts as $acc) {
                        $saldo = (float) KeuanganUtil::sumSaldo($acc, (int) $bulan);
                        $rows[] = [
                            $a1->nama,
                            $acc->kode,
                            $acc->nama,
                            $acc->keterangan ?? '-',
                            $saldo,
                        ];
                    }
                }
            }
        }

        return [
            'title' => 'Catatan Atas Laporan Keuangan (CALK)',
            'subtitle' => $this->periodeSubtitle($tahun, $bulan),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => [],
            'numberCols' => [5],
            'filename' => 'laporan-calk.csv',
        ];
    }

    public function labaRugi(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $result = KeuanganUtil::labaRugi($tahun, $bulan);
        $labaRugi = $result['groups'];
        $metrics = $result['metrics'];

        $headers = ['Group', 'Kode', 'Nama Akun', 'Sd. Lalu', 'Bulan Ini', 'Sd. Ini'];
        $rows = [];
        foreach ($labaRugi as $group) {
            if (! empty($group['kode'])) {
                foreach ($group['kode'] as $kode) {
                    $rows[] = [
                        $group['nama'] ?? '-',
                        $kode['kode'] ?? '',
                        $kode['nama'] ?? '',
                        (float) ($kode['saldo_sd_lalu'] ?? 0),
                        (float) ($kode['saldo_bulan_ini'] ?? 0),
                        (float) ($kode['saldo_sd_ini'] ?? 0),
                    ];
                }
            }
            $rows[] = [
                $group['nama'] ?? '-',
                '',
                'TOTAL ' . $group['nama'],
                (float) ($group['total_sd_lalu'] ?? 0),
                (float) ($group['total_bulan_ini'] ?? 0),
                (float) ($group['total_sd_ini'] ?? 0),
            ];
        }
        $rows[] = ['METRICS', '', 'Margin Kotor (%)', '', '', (float) ($metrics['margin_kotor'] ?? 0)];
        $rows[] = ['METRICS', '', 'Margin Bersih (%)', '', '', (float) ($metrics['margin_bersih'] ?? 0)];

        return [
            'title' => 'Laporan Laba Rugi',
            'subtitle' => $this->periodeSubtitle($tahun, $bulan),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => [],
            'numberCols' => [4, 5, 6],
            'filename' => 'laporan-laba-rugi.csv',
        ];
    }

    public function arusKas(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');
        $bulanLalu = max((int) $bulan - 1, 0);

        $arusKas = KeuanganUtil::arusKas($tahun, $bulan);
        $saldoKas = (float) KeuanganUtil::saldoKas($tahun, $bulanLalu);

        $headers = ['Kategori', 'Sub Kategori', 'Total'];
        $rows = [];
        foreach ($arusKas as $root) {
            if (! empty($root['children'])) {
                foreach ($root['children'] as $child) {
                    $rows[] = [$root['nama'], $child['nama'], (float) $child['total']];
                }
            }
            $rows[] = [$root['nama'], 'TOTAL ' . $root['nama'], (float) $root['total']];
        }
        $rows[] = ['SALDO KAS', 'Saldo Kas Awal', $saldoKas];

        return [
            'title' => 'Laporan Arus Kas',
            'subtitle' => $this->periodeSubtitle($tahun, $bulan),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => [],
            'numberCols' => [3],
            'filename' => 'laporan-arus-kas.csv',
        ];
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
            2 => 'Bangunan',
            3 => 'Kendaraan',
            4 => 'Peralatan',
        ];

        $headers = ['Kategori', 'Nama Barang', 'Tanggal Beli', 'Tanggal Validasi', 'Status', 'Jumlah', 'Harga Satuan', 'Nilai Perolehan', 'Nilai Buku'];
        $rows = [];
        foreach ($inventarisGroups as $kategori => $items) {
            $namaKategori = $kategoriNaman[$kategori] ?? 'Kategori ' . $kategori;
            foreach ($items as $inv) {
                $nilaiBuku = 0;
                try {
                    $nilaiBuku = (float) InventarisUtil::nilaiBuku($tgl_kondisi, $inv);
                } catch (\Throwable $e) {
                    $nilaiBuku = (float) $inv->harga_satuan;
                }
                $rows[] = [
                    $namaKategori,
                    $inv->nama_barang ?? '-',
                    $inv->tanggal_beli ? Carbon::parse($inv->tanggal_beli)->format('d/m/Y') : '-',
                    $inv->tanggal_validasi ? Carbon::parse($inv->tanggal_validasi)->format('d/m/Y') : '-',
                    ucfirst($inv->status ?? '-'),
                    (int) ($inv->jumlah ?? 1),
                    (float) $inv->harga_satuan,
                    (float) $inv->harga_satuan * (int) ($inv->jumlah ?? 1),
                    $nilaiBuku,
                ];
            }
        }

        return [
            'title' => 'Aset Tetap dan Inventaris',
            'subtitle' => $this->periodeSubtitle($tahun, $bulan),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => [],
            'numberCols' => [7, 8, 9],
            'filename' => 'laporan-aset-tetap-inventaris.csv',
        ];
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

        $headers = ['No', 'Product ID', 'Produk', 'Pelanggan', 'No Faktur', 'Tanggal', 'Qty', 'Harga Satuan', 'Subtotal'];
        $rows = [];
        foreach ($sales as $i => $row) {
            $rows[] = [
                $i + 1,
                $row->product_id,
                $row->product->nama_produk ?? '-',
                $row->sale->customer->nama_pelanggan ?? 'Guest',
                $row->sale->no_invoice ?? '-',
                Carbon::parse($row->sale->tanggal_transaksi)->format('d/m/Y H:i'),
                (float) $row->jumlah,
                (float) $row->harga_satuan,
                (float) $row->subtotal,
            ];
        }

        return [
            'title' => 'Laporan Penjualan Produk',
            'subtitle' => $this->periodeSubtitle($tahun, $bulan, $hari),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => ['', '', '', '', '', '', '', 'Total:', (float) $total],
            'numberCols' => [7, 8, 9],
            'filename' => 'laporan-penjualan-produk.csv',
        ];
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

        $headers = ['No', 'Product ID', 'Produk', 'Supplier', 'No Pembelian', 'Tanggal', 'Qty', 'Harga Satuan', 'Subtotal'];
        $rows = [];
        foreach ($purchases as $i => $row) {
            $rows[] = [
                $i + 1,
                $row->product_id,
                $row->product->nama_produk ?? '-',
                $row->purchase->supplier->nama_supplier ?? '-',
                $row->purchase->no_pembelian ?? '-',
                Carbon::parse($row->purchase->tanggal_pembelian)->format('d/m/Y H:i'),
                (float) $row->jumlah,
                (float) $row->harga_satuan,
                (float) $row->subtotal,
            ];
        }

        return [
            'title' => 'Laporan Pembelian Produk',
            'subtitle' => $this->periodeSubtitle($tahun, $bulan, $hari),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => ['', '', '', '', '', '', '', 'Total:', (float) $total],
            'numberCols' => [7, 8, 9],
            'filename' => 'laporan-pembelian-produk.csv',
        ];
    }

    public function produkTerlaris(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $query = SaleDetail::select(
            'product_id',
            \DB::raw('SUM(jumlah) as total_terjual'),
            \DB::raw('SUM(subtotal) as total_revenue'),
            \DB::raw('SUM(profit) as total_profit')
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

        $headers = ['No', 'Produk', 'Kategori', 'Qty Terjual', 'Pendapatan', 'Profit'];
        $rows = [];
        $sumQty = 0; $sumRev = 0; $sumProf = 0;
        foreach ($query as $i => $item) {
            $rows[] = [
                $i + 1,
                $item->product->nama_produk ?? '-',
                $item->product->category->nama_kategori ?? '-',
                (float) $item->total_terjual,
                (float) $item->total_revenue,
                (float) $item->total_profit,
            ];
            $sumQty += (float) $item->total_terjual;
            $sumRev += (float) $item->total_revenue;
            $sumProf += (float) $item->total_profit;
        }

        return [
            'title' => 'Laporan Produk Terlaris',
            'subtitle' => $this->periodeSubtitle($tahun, $bulan) . ' (Top 20)',
            'headers' => $headers,
            'rows' => $rows,
            'totals' => ['', '', 'Total', $sumQty, $sumRev, $sumProf],
            'numberCols' => [4, 5, 6],
            'filename' => 'laporan-produk-terlaris.csv',
        ];
    }

    public function piutang(array $data)
    {
        $sales = Sale::with('customer')
            ->where('jumlah_utang', '>', 0)
            ->orderBy('tanggal_transaksi', 'asc')->get();

        $headers = ['No', 'Pelanggan', 'Tanggal', 'No Invoice', 'Total', 'Dibayar', 'Utang'];
        $rows = [];
        $totalPiutang = 0;
        foreach ($sales as $i => $sale) {
            $rows[] = [
                $i + 1,
                $sale->customer->nama_pelanggan ?? 'Guest',
                Carbon::parse($sale->tanggal_transaksi)->format('d/m/Y'),
                $sale->no_invoice ?? '-',
                (float) $sale->total,
                (float) $sale->dibayar,
                (float) $sale->jumlah_utang,
            ];
            $totalPiutang += (float) $sale->jumlah_utang;
        }

        return [
            'title' => 'Laporan Piutang (Customer)',
            'subtitle' => 'Per Tanggal: ' . Carbon::now()->isoFormat('D MMMM Y'),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => ['', '', '', '', '', 'Total Piutang', $totalPiutang],
            'numberCols' => [5, 6, 7],
            'filename' => 'laporan-piutang.csv',
        ];
    }

    public function hutang(array $data)
    {
        $purchases = Purchase::with('supplier')
            ->where('jumlah_utang', '>', 0)
            ->orderBy('tanggal_pembelian', 'asc')->get();

        $headers = ['No', 'Supplier', 'Tanggal', 'No Pembelian', 'Total', 'Dibayar', 'Utang'];
        $rows = [];
        $totalHutang = 0;
        foreach ($purchases as $i => $purchase) {
            $rows[] = [
                $i + 1,
                $purchase->supplier->nama_supplier ?? '-',
                Carbon::parse($purchase->tanggal_pembelian)->format('d/m/Y'),
                $purchase->no_pembelian ?? '-',
                (float) $purchase->total,
                (float) $purchase->dibayar,
                (float) $purchase->jumlah_utang,
            ];
            $totalHutang += (float) $purchase->jumlah_utang;
        }

        return [
            'title' => 'Laporan Hutang (Supplier)',
            'subtitle' => 'Per Tanggal: ' . Carbon::now()->isoFormat('D MMMM Y'),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => ['', '', '', '', '', 'Total Hutang', $totalHutang],
            'numberCols' => [5, 6, 7],
            'filename' => 'laporan-hutang.csv',
        ];
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

        $headers = ['No', 'No Opname', 'Tanggal', 'Produk', 'Stok Sistem', 'Stok Aktual', 'Selisih', 'User', 'Status'];
        $rows = [];
        $no = 0;
        foreach ($opnames as $op) {
            foreach ($op->details as $d) {
                $no++;
                $rows[] = [
                    $no,
                    $op->no_opname ?? '-',
                    Carbon::parse($op->tanggal_opname)->format('d/m/Y'),
                    $d->product->nama_produk ?? '-',
                    (float) ($d->stok_sistem ?? 0),
                    (float) ($d->stok_aktual ?? 0),
                    (float) ($d->selisih ?? 0),
                    $op->user->nama_lengkap ?? '-',
                    $op->status ?? '-',
                ];
            }
        }

        return [
            'title' => 'Laporan Stok Opname',
            'subtitle' => $this->periodeSubtitle($tahun, $bulan),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => [],
            'numberCols' => [5, 6, 7],
            'filename' => 'laporan-stok-opname.csv',
        ];
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

        $headers = ['No', 'Produk', 'Stok Sistem', 'Stok Aktual', 'Selisih', 'Catatan'];
        $rows = [];
        foreach ($opname->details as $i => $d) {
            $rows[] = [
                $i + 1,
                $d->product->nama_produk ?? '-',
                (float) ($d->stok_sistem ?? 0),
                (float) ($d->stok_aktual ?? 0),
                (float) ($d->selisih ?? 0),
                $d->catatan ?? '-',
            ];
        }

        return [
            'title' => 'Bukti Stock Opname ' . $opname->no_opname,
            'subtitle' => 'Tanggal: ' . Carbon::parse($opname->tanggal_opname)->format('d/m/Y') . ' | Status: ' . strtoupper($opname->status ?? '-'),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => [],
            'numberCols' => [3, 4, 5],
            'filename' => 'bukti-so-' . $opname->no_opname . '.csv',
        ];
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
        $subtitleParts = ['Per Tanggal: ' . Carbon::now()->isoFormat('D MMMM Y')];
        if ($categoryName !== '-') $subtitleParts[] = 'Kategori: ' . $categoryName;
        if ($shelfName !== '-') $subtitleParts[] = 'Rak: ' . $shelfName;

        $headers = ['No', 'Produk', 'Stok Sistem', 'Stok Aktual', 'Selisih', 'Catatan'];
        $rows = [];
        foreach ($products as $i => $p) {
            $rows[] = [
                $i + 1,
                $p->nama_produk,
                (int) $p->stok_aktual,
                '',
                '',
                '',
            ];
        }

        return [
            'title' => 'Form Stock Opname (Lembar Kerja)',
            'subtitle' => implode(' | ', $subtitleParts),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => [],
            'filename' => 'form-stock-opname.csv',
        ];
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

        $headers = ['No', 'Tanggal', 'No Pembelian', 'Supplier', 'Status', 'Total', 'Dibayar', 'Utang'];
        $rows = [];
        $sumTotal = 0; $sumBayar = 0; $sumUtang = 0;
        foreach ($purchases as $i => $purchase) {
            $rows[] = [
                $i + 1,
                Carbon::parse($purchase->tanggal_pembelian)->format('d/m/Y'),
                $purchase->no_pembelian ?? '-',
                $purchase->supplier->nama_supplier ?? '-',
                $purchase->status ?? '-',
                (float) $purchase->total,
                (float) $purchase->dibayar,
                (float) $purchase->jumlah_utang,
            ];
            $sumTotal += (float) $purchase->total;
            $sumBayar += (float) $purchase->dibayar;
            $sumUtang += (float) $purchase->jumlah_utang;
        }

        return [
            'title' => 'Laporan Pembelian',
            'subtitle' => $this->periodeSubtitle($tahun, $bulan),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => ['', '', '', '', 'Total', $sumTotal, $sumBayar, $sumUtang],
            'numberCols' => [6, 7, 8],
            'filename' => 'laporan-pembelian.csv',
        ];
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

        $headers = ['No', 'Produk', 'Kategori', 'Harga Jual', 'Biaya Rata-rata', 'Margin (Rp)', 'Margin (%)'];
        $rows = [];
        foreach ($products as $i => $p) {
            $rows[] = [
                $i + 1,
                $p->nama_produk,
                $p->category->nama_kategori ?? '-',
                (float) $p->harga_jual,
                (float) $p->biaya_rata_rata,
                (float) $p->margin_rp,
                (float) $p->margin_pct,
            ];
        }

        return [
            'title' => 'Laporan Margin & Profitabilitas Produk',
            'subtitle' => 'Per Tanggal: ' . Carbon::now()->isoFormat('D MMMM Y'),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => [],
            'numberCols' => [4, 5, 6, 7],
            'filename' => 'laporan-margin-produk.csv',
        ];
    }

    public function customerTerbaik(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $query = Sale::select(
            'customer_id',
            \DB::raw('COUNT(*) as jumlah_transaksi'),
            \DB::raw('SUM(total) as total_belanja'),
            \DB::raw('AVG(total) as rata_rata')
        )
            ->where('business_id', $business->id)
            ->whereYear('tanggal_transaksi', $tahun);
        if ($bulan != '-') $query->whereMonth('tanggal_transaksi', $bulan);

        $customers = $query->groupBy('customer_id')->orderByDesc('total_belanja')->limit(20)
            ->with('customer')->get();

        $headers = ['No', 'Pelanggan', 'Jumlah Transaksi', 'Total Belanja', 'Rata-rata'];
        $rows = [];
        foreach ($customers as $i => $c) {
            $rows[] = [
                $i + 1,
                $c->customer->nama_pelanggan ?? 'Guest',
                (int) $c->jumlah_transaksi,
                (float) $c->total_belanja,
                (float) $c->rata_rata,
            ];
        }

        return [
            'title' => 'Laporan Customer Terbaik',
            'subtitle' => $this->periodeSubtitle($tahun, $bulan) . ' (Top 20)',
            'headers' => $headers,
            'rows' => $rows,
            'totals' => [],
            'numberCols' => [4, 5],
            'filename' => 'laporan-customer-terbaik.csv',
        ];
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

        $headers = ['No', 'Produk', 'Kategori', 'Stok', 'Terjual 30hr', 'Turnover Ratio', 'Days in Inv', 'Nilai Stok'];
        $rows = [];
        foreach ($products as $i => $p) {
            $rows[] = [
                $i + 1,
                $p->nama_produk,
                $p->category->nama_kategori ?? '-',
                (int) $p->stok_aktual,
                (int) $p->terjual_30hari,
                (float) $p->turnover_ratio,
                $p->days_in_inventory !== null ? (int) $p->days_in_inventory : '-',
                (float) $p->nilai_stok,
            ];
        }

        return [
            'title' => 'Laporan Inventory Turnover',
            'subtitle' => '30 Hari Terakhir | Per Tanggal: ' . Carbon::now()->isoFormat('D MMMM Y'),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => [],
            'numberCols' => [6, 8],
            'filename' => 'laporan-inventory-turnover.csv',
        ];
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

        $headers = ['No', 'Tanggal', 'No Retur', 'Tipe', 'Pelanggan/Supplier', 'No Referensi', 'Status', 'Total Return', 'Alasan'];
        $rows = [];
        $no = 0;
        foreach ($salesReturns as $sr) {
            $no++;
            $rows[] = [
                $no,
                Carbon::parse($sr->tanggal_return)->format('d/m/Y'),
                $sr->no_return ?? '-',
                'Penjualan',
                $sr->sale->customer->nama_pelanggan ?? 'Guest',
                $sr->sale->no_invoice ?? '-',
                $sr->status ?? '-',
                (float) $sr->total_return,
                $sr->alasan_return ?? '-',
            ];
        }
        foreach ($purchaseReturns as $pr) {
            $no++;
            $rows[] = [
                $no,
                Carbon::parse($pr->tanggal_return)->format('d/m/Y'),
                $pr->no_return ?? '-',
                'Pembelian',
                $pr->purchase->supplier->nama_supplier ?? '-',
                $pr->purchase->no_pembelian ?? '-',
                $pr->status ?? '-',
                (float) $pr->total_return,
                $pr->alasan_return ?? '-',
            ];
        }
        $sumTotal = $salesReturns->sum('total_return') + $purchaseReturns->sum('total_return');

        return [
            'title' => 'Laporan Retur',
            'subtitle' => $this->periodeSubtitle($tahun, $bulan),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => ['', '', '', '', '', '', 'Total', $sumTotal, ''],
            'numberCols' => [8],
            'filename' => 'laporan-retur.csv',
        ];
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

        $headers = ['No', 'Tanggal Buka', 'Tanggal Tutup', 'Kasir', 'Saldo Awal', 'Saldo Akhir Aplikasi', 'Saldo Akhir Real', 'Status'];
        $rows = [];
        foreach ($sessions as $i => $s) {
            $rows[] = [
                $i + 1,
                Carbon::parse($s->tanggal_buka)->format('d/m/Y H:i'),
                $s->tanggal_tutup ? Carbon::parse($s->tanggal_tutup)->format('d/m/Y H:i') : '-',
                $s->user->nama_lengkap ?? '-',
                (float) ($s->saldo_awal ?? 0),
                (float) ($s->saldo_akhir_aplikasi ?? 0),
                (float) ($s->saldo_akhir ?? 0),
                $s->status ?? '-',
            ];
        }

        return [
            'title' => 'Laporan Kasir',
            'subtitle' => $this->periodeSubtitle($tahun, $bulan, $hari),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => [],
            'numberCols' => [5, 6, 7],
            'filename' => 'laporan-kasir.csv',
        ];
    }

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

        $headers = ['Item', 'Keterangan'];
        $rows = [
            ['Nama Usaha', $business?->nama_usaha ?? '-'],
            ['Alamat', $business?->alamat ?? '-'],
            ['No Telp', $business?->no_telp ?? '-'],
            ['Email', $business?->email ?? '-'],
            ['Periode Laporan', $periode],
            ['Tanggal Cetak', Carbon::now()->isoFormat('D MMMM Y')],
        ];

        return [
            'title' => 'Halaman Sampul (Cover)',
            'subtitle' => 'Laporan Keuangan',
            'headers' => $headers,
            'rows' => $rows,
            'totals' => [],
            'filename' => 'cover-laporan.csv',
        ];
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

        $headers = ['No', 'Produk', 'Kategori', 'Satuan', 'Rak', 'Stok Awal', 'Masuk', 'Keluar', 'Stok Akhir', 'Nilai Stok'];
        $rows = [];
        foreach ($products as $i => $p) {
            $rows[] = [
                $i + 1,
                $p->nama_produk,
                $p->category->nama_kategori ?? '-',
                $p->unit->nama_satuan ?? '-',
                $p->shelf->nama_rak ?? '-',
                (int) $p->stok_awal_periode,
                (int) $p->stok_masuk,
                (int) $p->stok_keluar,
                (int) $p->stok_akhir,
                (float) $p->nilai_stok,
            ];
        }

        return [
            'title' => 'Laporan Stok (Per Periode)',
            'subtitle' => $this->periodeSubtitle($tahun, $bulan, $hari),
            'headers' => $headers,
            'rows' => $rows,
            'totals' => ['', '', '', '', 'Total', '', '', '', (int) $products->sum('stok_akhir'), (float) $products->sum('nilai_stok')],
            'numberCols' => [10],
            'filename' => 'laporan-stok.csv',
        ];
    }
}
