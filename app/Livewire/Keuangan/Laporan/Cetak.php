<?php

namespace App\Livewire\Keuangan\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AkunLevel1;
use App\Models\Business;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchasesReturn;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SalesReturn;
use App\Models\StockOpname;
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

        $business = Business::first();
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

    public function labaRugi(array $data)
    {
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

    public function neraca(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $akunLevel1s = AkunLevel1::with([
            'akunLevel2.akunLevel3.accounts' => function ($query) {
                $query->where('business_id', auth()->user()->business_id);
            },
            'akunLevel2.akunLevel3.accounts.balance' => function ($query) use ($tahun) {
                $query->where('business_id', auth()->user()->business_id)->where('tahun', $tahun);
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

    public function bukuBesar(array $data)
    {
        $kodeAkun = $data['sub_laporan'];
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $akun = Account::where('kode', $kodeAkun)->with([
            'balance' => function ($query) use ($tahun) {
                $query->where('business_id', auth()->user()->business_id)->where('tahun', $tahun);
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

        $query = StockOpname::with(['details.product', 'user'])
            ->whereYear('tanggal_opname', $tahun);

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

    public function pembelian(array $data)
    {
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
        $products = Product::with('category')
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
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $query = Sale::select(
            'customer_id',
            DB::raw('COUNT(*) as jumlah_transaksi'),
            DB::raw('SUM(total) as total_belanja'),
            DB::raw('AVG(total) as rata_rata')
        )
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
        $products = Product::with('category')
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

    private function streamPdf($html, $filename)
    {
        $options = new Options;
        $options->set('defaultFont', 'sans-serif');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();

        return response($output, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$filename.'"');
    }
}
