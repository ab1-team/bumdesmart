<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Dashboard extends Component
{
    public $businessId;

    // Period Filter
    public $period = 'today'; // today, week, month, year

    // Main Metrics (dynamic based on period)
    public $totalSales = 0;

    public $totalProfit = 0;

    public $totalTransactions = 0;

    public $avgTransaction = 0;

    // Growth
    public $salesGrowth = 0;

    public $previousPeriodSales = 0;

    // Chart Data
    public $chartLabels = [];

    public $chartData = [];

    // Alerts (always show current status)
    public $lowStockCount = 0;

    public $overdueReceivablesCount = 0;

    public $duePayablesCount = 0;

    public $nearExpiryCount = 0;

    // Low Stock Products
    public $lowStockProducts = [];

    // Top Products & Recent Transactions
    public $topProducts = [];

    public $topProductsByRevenue = [];

    public $recentTransactions = [];

    public function mount()
    {
        $this->businessId = auth()->user()->business_id;
        $this->loadData();
    }

    public function setPeriod($period)
    {
        $this->period = $period;
        $this->loadData();
    }

    public function showLowStockModal()
    {
        $this->lowStockProducts = Product::where('business_id', $this->businessId)
            ->where('is_active', true)
            ->whereColumn('stok_aktual', '<=', 'stok_minimal')
            ->get(['id', 'nama_produk', 'sku', 'stok_aktual', 'stok_minimal'])
            ->toArray();

        $this->dispatch('show-modal', modalId: 'lowStockModal');
    }

    public function loadData()
    {
        $now = now();

        // Determine date range based on period
        switch ($this->period) {
            case 'today':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $prevStart = $now->copy()->subDay()->startOfDay();
                $prevEnd = $now->copy()->subDay()->endOfDay();
                $chartDays = 7;
                break;
            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                $prevStart = $now->copy()->subWeek()->startOfWeek();
                $prevEnd = $now->copy()->subWeek()->endOfWeek();
                $chartDays = 7;
                break;
            case 'month':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $prevStart = $now->copy()->subMonth()->startOfMonth();
                $prevEnd = $now->copy()->subMonth()->endOfMonth();
                $chartDays = 30;
                break;
            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                $prevStart = $now->copy()->subYear()->startOfYear();
                $prevEnd = $now->copy()->subYear()->endOfYear();
                $chartDays = 12; // months
                break;
            default:
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $prevStart = $now->copy()->subDay()->startOfDay();
                $prevEnd = $now->copy()->subDay()->endOfDay();
                $chartDays = 7;
        }

        // =====================
        // MAIN METRICS
        // =====================
        $this->totalSales = Sale::where('business_id', $this->businessId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total');

        $this->totalTransactions = Sale::where('business_id', $this->businessId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $this->avgTransaction = $this->totalTransactions > 0
            ? $this->totalSales / $this->totalTransactions
            : 0;

        $this->totalProfit = SaleDetail::whereHas('sale', function ($q) use ($startDate, $endDate) {
            $q->where('business_id', $this->businessId)
                ->whereBetween('created_at', [$startDate, $endDate]);
        })->sum('profit');

        // Previous period for growth
        $this->previousPeriodSales = Sale::where('business_id', $this->businessId)
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->sum('total');

        if ($this->previousPeriodSales > 0) {
            $this->salesGrowth = round((($this->totalSales - $this->previousPeriodSales) / $this->previousPeriodSales) * 100, 1);
        } else {
            $this->salesGrowth = $this->totalSales > 0 ? 100 : 0;
        }

        // =====================
        // CHART DATA
        // =====================
        $this->loadChartData($now, $chartDays);

        // =====================
        // ALERTS (Current Status)
        // =====================
        $this->lowStockCount = Product::where('business_id', $this->businessId)
            ->where('is_active', true)
            ->whereColumn('stok_aktual', '<=', 'stok_minimal')
            ->count();

        $overdueDate = $now->copy()->subDays(30);
        $this->overdueReceivablesCount = Sale::where('business_id', $this->businessId)
            ->where('jumlah_utang', '>', 0)
            ->where('created_at', '<', $overdueDate)
            ->count();

        $this->duePayablesCount = Purchase::where('business_id', $this->businessId)
            ->where('jumlah_utang', '>', 0)
            ->count();

        $expiryDate = $now->copy()->addDays(30);
        $this->nearExpiryCount = ProductBatch::where('jumlah_saat_ini', '>', 0)
            ->whereNotNull('tanggal_kadaluarsa')
            ->where('tanggal_kadaluarsa', '<=', $expiryDate)
            ->where('tanggal_kadaluarsa', '>=', $now)
            ->whereHas('product', function ($q) {
                $q->where('business_id', $this->businessId);
            })
            ->count();

        // =====================
        // TOP PRODUCTS
        // =====================
        $this->topProducts = SaleDetail::whereHas('sale', function ($q) use ($startDate, $endDate) {
            $q->where('business_id', $this->businessId)
                ->whereBetween('created_at', [$startDate, $endDate]);
        })
            ->select('product_id', DB::raw('sum(jumlah) as total_qty'), DB::raw('sum(subtotal) as total_revenue'))
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->with(['product:id,nama_produk,sku'])
            ->get();

        $this->topProductsByRevenue = SaleDetail::whereHas('sale', function ($q) use ($startDate, $endDate) {
            $q->where('business_id', $this->businessId)
                ->whereBetween('created_at', [$startDate, $endDate]);
        })
            ->select('product_id', DB::raw('sum(jumlah) as total_qty'), DB::raw('sum(subtotal) as total_revenue'))
            ->groupBy('product_id')
            ->orderByDesc('total_revenue')
            ->take(10)
            ->with(['product:id,nama_produk,sku'])
            ->get();

        // =====================
        // RECENT TRANSACTIONS
        // =====================
        $this->recentTransactions = Sale::where('business_id', $this->businessId)
            ->with('customer:id,nama_pelanggan')
            ->latest()
            ->take(8)
            ->get(['id', 'no_invoice', 'customer_id', 'total', 'status', 'created_at']);
    }

    private function loadChartData($now, $days)
    {
        $chartData = collect();

        if ($this->period === 'year') {
            // Monthly data for year view
            for ($i = 11; $i >= 0; $i--) {
                $date = $now->copy()->subMonths($i);
                $sales = Sale::where('business_id', $this->businessId)
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->sum('total');

                $chartData->push([
                    'label' => $date->format('M'),
                    'total' => $sales,
                ]);
            }
        } else {
            // Daily data
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = $now->copy()->subDays($i);
                $sales = Sale::where('business_id', $this->businessId)
                    ->whereDate('created_at', $date)
                    ->sum('total');

                $chartData->push([
                    'label' => $date->format('d M'),
                    'total' => $sales,
                ]);
            }
        }

        $this->chartLabels = $chartData->pluck('label')->toArray();
        $this->chartData = $chartData->pluck('total')->toArray();
    }

    #[Layout('layouts.app')]
    #[Title('Dashboard')]
    public function render()
    {
        return view('livewire.dashboard');
    }
}
