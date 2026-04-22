<?php

namespace App\Livewire\Keuangan\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AkunLevel1;
use App\Models\AkunLevel2;
use App\Models\AkunLevel3;
use App\Models\Business;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchasesReturn;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SalesReturn;
use App\Models\StockOpname;
use App\Models\cashDrawer;
use App\Utils\KeuanganUtil;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Cetak extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->all();

        if (! isset($data['laporan']) || ! method_exists($this, $data['laporan'])) {
            abort(404, 'Laporan tidak ditemukan');
        }

        // Cek bisnis berdasarkan domain (Multi-tenant style)
        $host = request()->getHost();
        $owner = \App\Models\Owner::where('domain', $host)
            ->orWhere('domain_alternatif', $host)
            ->first();

        if ($owner) {
            $business = Business::where('owner_id', $owner->id)->first();
        } else {
            $business = Business::find(auth()->user()?->business_id) ?? Business::first();
        }

        view()->share('business', $business);

        return $this->{$data['laporan']}($data);
    }

    public function penjualanHarian(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? '-';
        $hari = $data['periode'] ?? '-';

        $query = Sale::with(['customer'])
            ->whereYear('tanggal_transaksi', $tahun);

        if ($bulan != '-') {
            $query->whereMonth('tanggal_transaksi', $bulan);
        }

        if ($hari != '-') {
            $query->whereDay('tanggal_transaksi', $hari);
        }

        $sales = $query->orderBy('tanggal_transaksi', 'desc')->get();

        $summary = [
            'total_transactions' => $sales->count(),
            'total_sales' => $sales->sum('total'),
            'avg_transaction' => $sales->count() > 0 ? $sales->avg('total') : 0,
        ];

        $title = 'Laporan Penjualan Harian';
        $periodeParts = [];
        if ($bulan != '-') {
            $periodeParts[] = Carbon::createFromDate($tahun, $bulan, 1)->isoFormat('MMMM');
        }
        $periodeParts[] = $tahun;
        $subtitle = 'Periode: '.implode(' ', $periodeParts);

        $html = view('livewire.keuangan.pelaporan.penjualan-harian', compact('title', 'subtitle', 'sales', 'summary'))->render();

        return $this->streamPdf($html, 'laporan-penjualan-harian.pdf');
    }

    public function stokMinimum(array $data)
    {
        $products = Product::with('category')
            ->whereColumn('stok_aktual', '<=', 'stok_minimal')
            ->where('is_active', true)
            ->get()
            ->map(function ($product) {
                $product->kekurangan = $product->stok_minimal - $product->stok_aktual;
                $product->suggested_order = ($product->stok_minimal * 2) - $product->stok_aktual;

                return $product;
            })
            ->sortByDesc('kekurangan');

        $title = 'Laporan Stok Minimum';
        $subtitle = 'Periode: '.Carbon::now()->isoFormat('MMMM Y');

        $html = view('livewire.keuangan.pelaporan.stok-minimum', compact('title', 'subtitle', 'products'))->render();

        return $this->streamPdf($html, 'laporan-stok-minimum.pdf');
    }

    public function jurnalTransaksi(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $payments = Payment::where('business_id', auth()->user()->business_id)->where('tanggal_pembayaran', 'LIKE', $tahun.'-'.$bulan.'-%')->with([
            'accountDebit',
            'accountKredit',
            'user',
        ])->get();

        $title = 'Jurnal Transaksi';
        $periodeParts = [];
        if ($bulan != '-') {
            $periodeParts[] = Carbon::createFromDate($tahun, $bulan, 1)->isoFormat('MMMM');
        }

        $periodeParts[] = $tahun;
        $namaBulan = implode(' ', $periodeParts);
        $subtitle = 'Periode: '.$namaBulan;

        $html = view('livewire.keuangan.pelaporan.jurnal-transaksi', compact('title', 'subtitle', 'payments'))->render();

        return $this->streamPdf($html, 'laporan-jurnal-transaksi.pdf');
    }

    public function bukuBesar(array $data)
    {
        $business = view()->shared('business');
        $kodeAkun = $data['sub_laporan'];
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $akun = Account::where('kode', $kodeAkun)->with([
            'balance' => function ($query) use ($tahun) {
                $query->where('business_id', $business->id)->where('tahun', $tahun);
            },
        ])->first();

        $payments = Payment::where([
            ['business_id', auth()->user()->business_id],
            ['tanggal_pembayaran', 'LIKE', $tahun.'-'.$bulan.'-%'],
        ])->where(function ($query) use ($kodeAkun) {
            $query->where('rekening_debit', $kodeAkun)
                ->orWhere('rekening_kredit', $kodeAkun);
        })->get();

        $title = 'Laporan Buku Besar';
        $periodeParts = [];
        if ($bulan != '-') {
            $periodeParts[] = Carbon::createFromDate($tahun, $bulan, 1)->isoFormat('MMMM');
        }

        $periodeParts[] = $tahun;
        $namaBulan = implode(' ', $periodeParts);
        $subtitle = 'Periode: '.$namaBulan;

        $html = view('livewire.keuangan.pelaporan.buku-besar', compact('title', 'subtitle', 'akun', 'payments', 'tahun', 'bulan', 'namaBulan'))->render();

        return $this->streamPdf($html, 'laporan-buku-besar.pdf');
    }

    public function neraca(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $akunLevel1s = AkunLevel1::with([
            'akunLevel2.akunLevel3.accounts' => function ($query) {
                $query->where('business_id', $business->id);
            },
            'akunLevel2.akunLevel3.accounts.balance' => function ($query) use ($tahun) {
                $query->where('business_id', $business->id)->where('tahun', $tahun);
            },
        ])->where('id', '<=', '3')->get();

        $title = 'Laporan Neraca';
        $periodeParts = [];
        if ($bulan != '-') {
            $periodeParts[] = Carbon::createFromDate($tahun, $bulan, 1)->isoFormat('MMMM');
        }
        $periodeParts[] = $tahun;
        $subtitle = 'Periode: '.implode(' ', $periodeParts);

        $html = view('livewire.keuangan.pelaporan.neraca', compact('title', 'subtitle', 'akunLevel1s', 'tahun', 'bulan'))->render();

        return $this->streamPdf($html, 'laporan-neraca.pdf');
    }

    public function labaRugi(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $labaRugi = KeuanganUtil::labaRugi($tahun, $bulan);

        $title = 'Laporan Laba Rugi';
        $periodeParts = [];
        if ($bulan != '-') {
            $periodeParts[] = Carbon::createFromDate($tahun, $bulan, 1)->isoFormat('MMMM');
        }
        $periodeParts[] = $tahun;
        $subtitle = 'Periode: '.implode(' ', $periodeParts);

        $html = view('livewire.keuangan.pelaporan.laba-rugi', compact('title', 'subtitle', 'labaRugi'))->render();

        return $this->streamPdf($html, 'laporan-laba-rugi.pdf');
    }

    public function arusKas(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');
        $bulanLalu = $bulan - 1;

        $tanggalMulai = $tahun.'-'.$bulan.'-01';
        $tanggalAkhir = date('Y-m-t', strtotime($tanggalMulai));

        $arusKas = KeuanganUtil::arusKas($tanggalMulai, $tanggalAkhir);
        $saldoKas = KeuanganUtil::saldoKas($tahun, $bulanLalu);

        $title = 'Laporan Arus Kas';
        $periodeParts = [];
        if ($bulan != '-') {
            $periodeParts[] = Carbon::createFromDate($tahun, $bulan, 1)->isoFormat('MMMM');
        }
        $periodeParts[] = $tahun;
        $subtitle = 'Periode: '.implode(' ', $periodeParts);

        $html = view('livewire.keuangan.pelaporan.arus-kas', compact('title', 'subtitle', 'arusKas', 'saldoKas'))->render();

        return $this->streamPdf($html, 'laporan-arus-kas.pdf');
    }

    public function asetTetapInventaris(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        // Batas tanggal penyusutan
        $tgl_kondisi = Carbon::createFromDate($tahun, $bulan == '-' ? 12 : $bulan)->endOfMonth()->format('Y-m-d');

        // Ambil data Inventaris (jenis = 1, kategori = 1 s.d 4)
        $inventarisGroups = \App\Models\Inventory::where([
            ['jenis', '1'],
            ['status', '!=', '0'],
            ['tanggal_beli', '<=', $tgl_kondisi],
            ['harga_satuan', '>', '0'],
        ])
            ->whereNotNull('tanggal_beli')
            ->whereIn('kategori', [1, 2, 3, 4])
            ->orderBy('kategori', 'ASC')
            ->orderBy('tanggal_beli', 'ASC')
            ->get()
            ->groupBy('kategori');

        $title = 'Aset Tetap Inventaris';
        $periodeParts = [];
        if ($bulan != '-') {
            $periodeParts[] = Carbon::createFromDate($tahun, $bulan, 1)->isoFormat('MMMM');
        }
        $periodeParts[] = $tahun;
        $subtitle = 'Periode: '.implode(' ', $periodeParts);

        $html = view('livewire.keuangan.pelaporan.aset-tetap-inventaris', compact('title', 'subtitle', 'inventarisGroups', 'tgl_kondisi', 'tahun', 'bulan'))->render();

        return $this->streamPdf($html, 'laporan-aset-tetap-inventaris.pdf', 'landscape');
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
                if ($bulan != '-') {
                    $q->whereMonth('tanggal_transaksi', $bulan);
                }
            })
            ->groupBy('product_id')
            ->orderByDesc('total_terjual')
            ->limit(20)
            ->with('product.category')
            ->get();

        $title = 'Laporan Produk Terlaris';
        $periodeParts = [];
        if ($bulan != '-') {
            $periodeParts[] = Carbon::createFromDate($tahun, $bulan, 1)->isoFormat('MMMM');
        }
        $periodeParts[] = $tahun;
        $subtitle = 'Periode: '.implode(' ', $periodeParts).' (Top 20)';

        $products = $query;

        $html = view('livewire.keuangan.pelaporan.produk-terlaris', compact('title', 'subtitle', 'products'))->render();

        return $this->streamPdf($html, 'laporan-produk-terlaris.pdf');
    }

    public function piutang(array $data)
    {
        $business = view()->shared('business');
        $sales = Sale::with('customer')
            ->where('jumlah_utang', '>', 0)
            ->orderBy('tanggal_transaksi', 'asc')
            ->get();

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

        $html = view('livewire.keuangan.pelaporan.piutang', compact('title', 'subtitle', 'grouped', 'totalPiutang'))->render();

        return $this->streamPdf($html, 'laporan-piutang.pdf');
    }

    public function hutang(array $data)
    {
        $business = view()->shared('business');
        $purchases = Purchase::with('supplier')
            ->where('jumlah_utang', '>', 0)
            ->orderBy('tanggal_pembelian', 'asc')
            ->get();

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

        $html = view('livewire.keuangan.pelaporan.hutang', compact('title', 'subtitle', 'grouped', 'totalHutang'))->render();

        return $this->streamPdf($html, 'laporan-hutang.pdf');
    }

    public function stokOpname(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $query = StockOpname::whereYear('tanggal_opname', $tahun)
            ->whereHas('details', function($q) {
                $q->where('selisih', '!=', 0);
            })
            ->with(['details' => function($q) {
                $q->where('selisih', '!=', 0)->with('product');
            }, 'user']);

        if ($bulan != '-') {
            $query->whereMonth('tanggal_opname', $bulan);
        }

        $opnames = $query->orderBy('tanggal_opname', 'desc')->get();

        $title = 'Laporan Stok Opname';
        $periodeParts = [];
        if ($bulan != '-') {
            $periodeParts[] = Carbon::createFromDate($tahun, $bulan, 1)->isoFormat('MMMM');
        }
        $periodeParts[] = $tahun;
        $subtitle = 'Periode: '.implode(' ', $periodeParts);

        $html = view('livewire.keuangan.pelaporan.stok-opname', compact('title', 'subtitle', 'opnames'))->render();

        return $this->streamPdf($html, 'laporan-stok-opname.pdf');
    }

    public function buktiStokOpname(array $data)
    {
        $business = view()->shared('business');
        $id = $data['id'] ?? null;
        if (! $id) {
            abort(404, 'ID Stock Opname tidak ditemukan');
        }

        $opname = StockOpname::with(['details' => function($q) {
                $q->where('selisih', '!=', 0)->with('product');
            }, 'user', 'approvedBy'])
            ->where('business_id', $business->id)
            ->findOrFail($id);

        $title = 'Bukti Stock Opname';
        $subtitle = 'No: '.$opname->no_opname;

        $html = view('livewire.keuangan.pelaporan.bukti-stok-opname', compact('title', 'subtitle', 'opname'))->render();

        return $this->streamPdf($html, 'bukti-so-'.$opname->no_opname.'.pdf');
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
                
                // Prioritaskan bisnis milik data opname ini untuk KOP
                $business = Business::find($opname->business_id);
                if ($business) {
                    view()->share('business', $business);
                }
            }
            $query->whereIn('id', function($q) use ($opnameId) {
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

        $html = view('livewire.keuangan.pelaporan.form-stock-opname', compact('title', 'subtitle', 'products', 'business', 'categoryName', 'shelfName', 'catatan'))->render();

        return $this->streamPdf($html, 'form-stock-opname.pdf');
    }

    public function pembelian(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $query = Purchase::with('supplier')
            ->whereYear('tanggal_pembelian', $tahun);

        if ($bulan != '-') {
            $query->whereMonth('tanggal_pembelian', $bulan);
        }

        $purchases = $query->orderBy('tanggal_pembelian', 'desc')->get();

        $summary = [
            'total_po' => $purchases->count(),
            'total_pembelian' => $purchases->sum('total'),
            'total_dibayar' => $purchases->sum('dibayar'),
            'total_hutang' => $purchases->sum('jumlah_utang'),
        ];

        $title = 'Laporan Pembelian';
        $periodeParts = [];
        if ($bulan != '-') {
            $periodeParts[] = Carbon::createFromDate($tahun, $bulan, 1)->isoFormat('MMMM');
        }
        $periodeParts[] = $tahun;
        $subtitle = 'Periode: '.implode(' ', $periodeParts);

        $html = view('livewire.keuangan.pelaporan.pembelian', compact('title', 'subtitle', 'purchases', 'summary'))->render();

        return $this->streamPdf($html, 'laporan-pembelian.pdf');
    }

    public function marginProduk(array $data)
    {
        $business = view()->shared('business');
        $products = Product::where('business_id', $business->id)
            ->where('is_active', true)
            ->where('harga_jual', '>', 0)
            ->get()
            ->map(function ($p) {
                $p->margin_rp = $p->harga_jual - $p->biaya_rata_rata;
                $p->margin_pct = $p->harga_jual > 0 ? (($p->harga_jual - $p->biaya_rata_rata) / $p->harga_jual) * 100 : 0;

                return $p;
            })
            ->sortByDesc('margin_pct');

        $title = 'Laporan Margin & Profitabilitas Produk';
        $subtitle = 'Per Tanggal: '.Carbon::now()->isoFormat('D MMMM Y');

        $html = view('livewire.keuangan.pelaporan.margin-produk', compact('title', 'subtitle', 'products'))->render();

        return $this->streamPdf($html, 'laporan-margin-produk.pdf');
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

        if ($bulan != '-') {
            $query->whereMonth('tanggal_transaksi', $bulan);
        }

        $customers = $query
            ->groupBy('customer_id')
            ->orderByDesc('total_belanja')
            ->limit(20)
            ->with('customer')
            ->get();

        $title = 'Laporan Customer Terbaik';
        $periodeParts = [];
        if ($bulan != '-') {
            $periodeParts[] = Carbon::createFromDate($tahun, $bulan, 1)->isoFormat('MMMM');
        }
        $periodeParts[] = $tahun;
        $subtitle = 'Periode: '.implode(' ', $periodeParts).' (Top 20)';

        $html = view('livewire.keuangan.pelaporan.customer-terbaik', compact('title', 'subtitle', 'customers'))->render();

        return $this->streamPdf($html, 'laporan-customer-terbaik.pdf');
    }

    public function inventoryTurnover(array $data)
    {
        $business = view()->shared('business');
        $products = Product::where('business_id', $business->id)
            ->with('category')
            ->where('is_active', true)
            ->where('stok_aktual', '>', 0)
            ->get()
            ->map(function ($p) {
                $terjual30 = SaleDetail::where('product_id', $p->id)
                    ->whereHas('sale', function ($q) {
                        $q->where('tanggal_transaksi', '>=', Carbon::now()->subDays(30));
                    })
                    ->sum('jumlah');

                $p->terjual_30hari = $terjual30;
                $avgDailySales = $terjual30 / 30;
                $p->days_in_inventory = $avgDailySales > 0 ? round($p->stok_aktual / $avgDailySales) : null;
                $p->turnover_ratio = $p->stok_aktual > 0 && $terjual30 > 0 ? round($terjual30 / $p->stok_aktual, 2) : 0;
                $p->nilai_stok = $p->stok_aktual * $p->biaya_rata_rata;

                return $p;
            })
            ->sortByDesc('turnover_ratio');

        $title = 'Laporan Inventory Turnover';
        $subtitle = '30 Hari Terakhir | Per Tanggal: '.Carbon::now()->isoFormat('D MMMM Y');

        $html = view('livewire.keuangan.pelaporan.inventory-turnover', compact('title', 'subtitle', 'products'))->render();

        return $this->streamPdf($html, 'laporan-inventory-turnover.pdf');
    }

    public function retur(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $salesReturnQuery = SalesReturn::with(['sale.customer', 'user'])
            ->whereYear('tanggal_return', $tahun);
        $purchaseReturnQuery = PurchasesReturn::with(['purchase.supplier', 'user'])
            ->whereYear('tanggal_return', $tahun);

        if ($bulan != '-') {
            $salesReturnQuery->whereMonth('tanggal_return', $bulan);
            $purchaseReturnQuery->whereMonth('tanggal_return', $bulan);
        }

        $salesReturns = $salesReturnQuery->orderBy('tanggal_return', 'desc')->get();
        $purchaseReturns = $purchaseReturnQuery->orderBy('tanggal_return', 'desc')->get();

        $title = 'Laporan Retur';
        $periodeParts = [];
        if ($bulan != '-') {
            $periodeParts[] = Carbon::createFromDate($tahun, $bulan, 1)->isoFormat('MMMM');
        }
        $periodeParts[] = $tahun;
        $subtitle = 'Periode: '.implode(' ', $periodeParts);

        $html = view('livewire.keuangan.pelaporan.retur', compact('title', 'subtitle', 'salesReturns', 'purchaseReturns'))->render();

        return $this->streamPdf($html, 'laporan-retur.pdf');
    }

    public function cashierReport(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? '-';
        $hari = $data['periode'] ?? '-';
        $userId = $data['sub_laporan'] ?? '';

        $query = cashDrawer::with(['user', 'business'])
            ->whereYear('tanggal_buka', $tahun);

        if ($bulan != '-') {
            $query->whereMonth('tanggal_buka', $bulan);
        }

        if ($hari != '-') {
            $query->whereDay('tanggal_buka', $hari);
        }

        if ($userId != '') {
            $query->where('user_id', $userId);
        }

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
        $periodeParts = [];
        if ($bulan != '-') {
            $periodeParts[] = Carbon::createFromDate($tahun, $bulan, 1)->isoFormat('MMMM');
        }
        $periodeParts[] = $tahun;
        $subtitle = 'Periode: '.implode(' ', $periodeParts);

        $html = view('livewire.keuangan.pelaporan.cashier-report', compact('title', 'subtitle', 'sessions'))->render();

        return $this->streamPdf($html, 'laporan-kasir.pdf', 'landscape');
    }

    private function streamPdf($html, $filename, $orientation = 'portrait')
    {
        $options = new Options;
        $options->set('defaultFont', 'sans-serif');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();

        $output = $dompdf->output();

        return response($output, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$filename.'"');
    }
}
