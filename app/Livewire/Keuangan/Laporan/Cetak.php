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
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
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
        $owner = tenant();

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

        $query = Sale::with(['customer', 'payments', 'user'])
            ->whereYear('tanggal_transaksi', $tahun);

        if ($bulan != '-') {
            $query->whereMonth('tanggal_transaksi', $bulan);
        }

        if ($hari != '-') {
            $query->whereDay('tanggal_transaksi', $hari);
        }

        if (isset($data['sub_laporan']) && $data['sub_laporan'] != '') {
            if (str_contains($data['sub_laporan'], ':')) {
                [$type, $id] = explode(':', $data['sub_laporan']);
                if ($type === 'user') {
                    $query->where('user_id', $id);
                } elseif ($type === 'cat') {
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
            'Piutang' => ['items' => [], 'total' => 0]
        ];

        foreach ($sales as $sale) {
            $dibayar = $sale->dibayar;
            $utang = $sale->jumlah_utang;

            if ($dibayar > 0) {
                $metode = 'tunai';
                $payment = $sale->payments->whereIn('metode_pembayaran', ['tunai', 'transfer', 'qris', 'cash'])->first();
                if ($payment) {
                    $metode = $payment->metode_pembayaran;
                }

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
        $periodeParts = [];
        if ($bulan != '-') {
            $periodeParts[] = Carbon::createFromDate($tahun, $bulan, 1)->isoFormat('MMMM');
        }
        $periodeParts[] = $tahun;
        $subtitle = 'Periode: '.implode(' ', $periodeParts);

        $html = view('livewire.keuangan.pelaporan.penjualan-harian', compact('title', 'subtitle', 'groups', 'summary'))->render();

        return $this->streamPdf($html, 'laporan-penjualan-harian.pdf');
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
            'balance' => function ($query) use ($business, $tahun) {
                $query->where('business_id', $business->id)->where('tahun', $tahun);
            },
        ])->first();

        $payments = Payment::where([
            ['business_id', auth()->user()->business_id],
            ['tanggal_pembayaran', 'LIKE', $tahun.'-'.$bulan.'-%'],
        ])->where(function ($query) use ($kodeAkun) {
            $query->where('rekening_debit', $kodeAkun)
                ->orWhere('rekening_kredit', $kodeAkun);
        })->orderBy('tanggal_pembayaran', 'asc')->get();

        $title = 'Buku Besar ' . ($akun->nama ?? '');
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
            'akunLevel2.akunLevel3.accounts' => function ($query) use ($business) {
                $query->where('business_id', $business->id);
            },
            'akunLevel2.akunLevel3.accounts.balance' => function ($query) use ($business, $tahun) {
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
        $periodeParts = [];
        if ($bulan != '-') {
            $periodeParts[] = Carbon::createFromDate($tahun, $bulan, 1)->isoFormat('MMMM');
        }
        $periodeParts[] = $tahun;
        $subtitle = 'Periode: '.implode(' ', $periodeParts);

        $html = view('livewire.keuangan.pelaporan.calk', compact('title', 'subtitle', 'akunLevel1s', 'tahun', 'bulan'))->render();

        return $this->streamPdf($html, 'laporan-calk.pdf');
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
        $periodeParts = [];
        if ($bulan != '-') {
            $periodeParts[] = Carbon::createFromDate($tahun, $bulan, 1)->isoFormat('MMMM');
        }
        $periodeParts[] = $tahun;
        $subtitle = 'Periode: '.implode(' ', $periodeParts);

        $html = view('livewire.keuangan.pelaporan.laba-rugi', compact('title', 'subtitle', 'labaRugi', 'metrics'))->render();

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
            ->whereHas('sale', function ($q) use ($tahun, $bulan, $data) {
                $q->whereYear('tanggal_transaksi', $tahun);
                if ($bulan != '-') {
                    $q->whereMonth('tanggal_transaksi', $bulan);
                }
            })
            ->when(isset($data['sub_laporan']) && str_starts_with($data['sub_laporan'], 'cat:'), function($q) use ($data) {
                $catId = str_replace('cat:', '', $data['sub_laporan']);
                $q->whereHas('product', function ($sq) use ($catId) {
                    $sq->where('category_id', $catId);
                });
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

        if (isset($data['sub_laporan']) && $data['sub_laporan'] != '') {
            if (str_starts_with($data['sub_laporan'], 'rak:')) {
                $rakId = str_replace('rak:', '', $data['sub_laporan']);
                $query->whereHas('details.product', function($q) use ($rakId) {
                    $q->where('shelf_id', $rakId);
                });
            } elseif (str_starts_with($data['sub_laporan'], 'cat:')) {
                $catId = str_replace('cat:', '', $data['sub_laporan']);
                $query->whereHas('details.product', function($q) use ($catId) {
                    $q->where('category_id', $catId);
                });
            }
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

    public function cover(array $data)
    {
        $business = Business::with('owner')->find(auth()->user()?->business_id) ?? Business::with('owner')->first();
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? '-';
        
        $periode = $tahun;
        if ($bulan != '-') {
            $periode = Carbon::createFromDate($tahun, $bulan, 1)->isoFormat('MMMM') . ' ' . $tahun;
        }

        $owner = $business->owner ?? \App\Models\Owner::first();
        $logoPath = $owner && $owner->logo ? storage_path('app/public/' . $owner->logo) : null;
        $base64Logo = null;
        if ($logoPath && file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $imgData = file_get_contents($logoPath);
            $base64Logo = 'data:image/' . $type . ';base64,' . base64_encode($imgData);
        }

        $html = view('livewire.keuangan.pelaporan.cover', compact('business', 'periode', 'base64Logo'))->render();

        return $this->streamPdf($html, 'cover-laporan.pdf', 'portrait', [
            'margin-top' => '60mm',
            'margin-bottom' => '20mm',
            'is_cover' => true,
        ]);
    }

    private function streamPdf($html, $filename, $orientation = 'portrait', $options = [])
    {
        $business = Business::find(auth()->user()?->business_id) ?? Business::first();
        $owner = $business?->owner ?? \App\Models\Owner::first();
        
        $headerData = [
            'namaUsaha' => $business?->nama_usaha ?? ($owner?->nama_usaha ?? config('app.name', 'BUMDes Smart')),
            'alamatUsaha' => $business?->alamat ?? '',
            'telpUsaha' => $business?->no_telp ?? '',
            'emailUsaha' => $business?->email ?? '',
            'base64Logo' => null,
        ];

        $logoPath = $owner && $owner->logo ? storage_path('app/public/' . $owner->logo) : null;
        if ($logoPath && file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $headerData['base64Logo'] = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $isCover = $options['is_cover'] ?? false;
        $headerData['isCover'] = $isCover;

        $headerHtml = view('layouts.pdf-header', $headerData)->render();
        $footerHtml = view('layouts.pdf-footer', ['isCover' => $isCover])->render();

        $pdf = PDF::loadHTML($html)
            ->setPaper('a4')
            ->setOrientation($orientation)
            ->setOption('margin-top', $options['margin-top'] ?? '40mm')
            ->setOption('margin-bottom', $options['margin-bottom'] ?? '20mm')
            ->setOption('margin-left', $options['margin-left'] ?? '15mm')
            ->setOption('margin-right', $options['margin-right'] ?? '15mm')
            ->setOption('header-spacing', 5)
            ->setOption('enable-local-file-access', true);

        if (! ($options['skip_header'] ?? false)) {
            $pdf->setOption('header-html', $headerHtml);
        }

        if (! ($options['skip_footer'] ?? false)) {
            $pdf->setOption('footer-html', $footerHtml);
        }

        return $pdf->inline($filename);
    }
}
