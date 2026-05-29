@section('button-header')
    <div class="btn-list">
        <a href="{{ url('/penjualan/pos') }}" class="btn btn-primary d-none d-sm-inline-block">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
                stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M12 5l0 14" />
                <path d="M5 12l14 0" />
            </svg>
            Penjualan Baru
        </a>
    </div>
@endsection

<div>
    {{-- Period Filter --}}
    <div class="row align-items-center mb-4">
        <div class="col-12 col-md-auto mb-3 mb-md-0">
            <div class="btn-group w-100" role="group">
                <button type="button" wire:click="setPeriod('today')"
                    class="btn {{ $period === 'today' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Hari ini
                </button>
                <button type="button" wire:click="setPeriod('week')"
                    class="btn {{ $period === 'week' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Minggu ini
                </button>
                <button type="button" wire:click="setPeriod('month')"
                    class="btn {{ $period === 'month' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Bulan ini
                </button>
                <button type="button" wire:click="setPeriod('year')"
                    class="btn {{ $period === 'year' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Tahun ini
                </button>
            </div>
        </div>
        <div class="col text-end text-muted">
            <small>{{ now()->translatedFormat('l, d F Y') }}</small>
        </div>
    </div>

    {{-- Main KPIs Row --}}
    <div class="row row-deck row-cards mb-4">
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Total Penjualan</div>
                    </div>
                    <div class="h1 mb-1">Rp {{ number_format($totalSales, 0, ',', '.') }}</div>
                    <div class="d-flex align-items-center">
                        @if ($salesGrowth >= 0)
                            <span class="text-success d-inline-flex align-items-center lh-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="16"
                                    height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    fill="none">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M3 17l6 -6l4 4l8 -8" />
                                    <path d="M14 7l7 0l0 7" />
                                </svg>
                                {{ $salesGrowth }}%
                            </span>
                        @else
                            <span class="text-danger d-inline-flex align-items-center lh-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="16"
                                    height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    fill="none">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M3 7l6 6l4 -4l8 8" />
                                    <path d="M21 10l0 7l-7 0" />
                                </svg>
                                {{ $salesGrowth }}%
                            </span>
                        @endif
                        <span class="text-muted ms-2">dari periode sebelumnya</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Total Profit</div>
                    </div>
                    <div class="h1 mb-1 text-success">Rp {{ number_format($totalProfit, 0, ',', '.') }}</div>
                    @if ($totalSales > 0)
                        <div class="text-muted">
                            Margin {{ round(($totalProfit / $totalSales) * 100, 1) }}%
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Transaksi</div>
                    </div>
                    <div class="h1 mb-1">{{ number_format($totalTransactions, 0) }}</div>
                    <div class="text-muted">
                        Rata-rata Rp {{ number_format($avgTransaction, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader mb-2">Alert</div>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($lowStockCount > 0)
                            <span class="badge bg-danger text-light" style="cursor: pointer;"
                                wire:click="showLowStockModal">{{ $lowStockCount }} Stok Rendah</span>
                        @endif
                        @if ($overdueReceivablesCount > 0)
                            <span class="badge bg-warning">{{ $overdueReceivablesCount }} Piutang</span>
                        @endif
                        @if ($duePayablesCount > 0)
                            <span class="badge bg-orange">{{ $duePayablesCount }} Hutang</span>
                        @endif
                        @if ($nearExpiryCount > 0)
                            <span class="badge bg-purple">{{ $nearExpiryCount }} Expired</span>
                        @endif
                        @if ($lowStockCount == 0 && $overdueReceivablesCount == 0 && $duePayablesCount == 0 && $nearExpiryCount == 0)
                            <span class="text-success">✓ Semua Aman</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart + Top Products --}}
    <div class="row row-deck row-cards mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Grafik Penjualan</h3>
                </div>
                <div class="card-body">
                    <div id="salesChart" style="height: 300px;" wire:ignore></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Top Produk</h3>
                </div>
                <div class="card-body p-0">
                    @if (count($topProducts) > 0)
                        <div class="list-group list-group-flush">
                            @foreach ($topProducts as $index => $item)
                                <div class="list-group-item d-flex align-items-center py-2">
                                    <span
                                        class="badge {{ $index === 0 ? 'bg-yellow' : 'bg-secondary' }} me-2">{{ $index + 1 }}</span>
                                    <div class="flex-fill text-truncate">
                                        <div class="text-truncate fw-medium">
                                            {{ $item->product->nama_produk ?? '-' }}
                                        </div>
                                        <small class="text-muted">{{ number_format($item->total_qty, 0) }} terjual
                                            •
                                            Rp {{ number_format($item->total_revenue, 0, ',', '.') }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-3 text-center text-muted">
                            <small>Belum ada data</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Top Products by Revenue --}}
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Produk dengan Penjualan Tertinggi</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th class="w-1">#</th>
                        <th>SKU</th>
                        <th>Nama Produk</th>
                        <th class="text-end">Qty Terjual</th>
                        <th class="text-end">Total Penjualan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($topProductsByRevenue as $index => $item)
                        <tr>
                            <td>
                                <span
                                    class="badge {{ $index === 0 ? 'bg-yellow' : ($index < 3 ? 'bg-secondary-lt' : 'bg-secondary') }}">{{ $index + 1 }}</span>
                            </td>
                            <td><code>{{ $item->product->sku ?? '-' }}</code></td>
                            <td class="fw-medium">{{ $item->product->nama_produk ?? '-' }}</td>
                            <td class="text-end">{{ number_format($item->total_qty, 0) }}</td>
                            <td class="text-end fw-bold text-success">Rp
                                {{ number_format($item->total_revenue, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada data penjualan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Transactions --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Transaksi Terbaru</h3>
            <div class="card-actions">
                <a href="{{ url('/penjualan/daftar') }}" class="btn btn-sm btn-outline-primary">
                    Lihat Semua
                </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentTransactions as $tx)
                        <tr>
                            <td><code>{{ $tx->no_invoice }}</code></td>
                            <td>{{ $tx->customer->nama_pelanggan ?? 'Walk-in' }}</td>
                            <td class="text-end fw-bold">Rp {{ number_format($tx->total, 0, ',', '.') }}</td>
                            <td>
                                @if ($tx->status == 'paid' || $tx->status == 'lunas')
                                    <span class="badge bg-success-lt">Lunas</span>
                                @elseif ($tx->status == 'partial')
                                    <span class="badge bg-warning-lt">Sebagian</span>
                                @else
                                    <span class="badge bg-secondary-lt">{{ ucfirst($tx->status) }}</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $tx->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada transaksi</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="lowStockModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Produk Stok Rendah</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-vcenter table-hover">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Nama Produk</th>
                                    <th class="text-end">Stok Aktual</th>
                                    <th class="text-end">Stok Minimal</th>
                                    <th class="text-end">Selisih</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lowStockProducts as $product)
                                    <tr>
                                        <td><code>{{ $product['sku'] ?? '-' }}</code></td>
                                        <td>{{ $product['nama_produk'] }}</td>
                                        <td class="text-end">
                                            <span
                                                class="badge {{ $product['stok_aktual'] <= 0 ? 'bg-danger text-light' : 'bg-warning' }}">{{ $product['stok_aktual'] }}</span>
                                        </td>
                                        <td class="text-end">{{ $product['stok_minimal'] }}</td>
                                        <td class="text-end text-danger">
                                            -{{ $product['stok_minimal'] - $product['stok_aktual'] }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Memuat data...</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ url('/master-produk/produk') }}" class="btn btn-primary">Kelola Produk</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        let salesChart = null;

        function initChart() {
            const chartEl = document.querySelector("#salesChart");
            if (!chartEl) return;

            const options = {
                chart: {
                    type: 'area',
                    height: 300,
                    toolbar: {
                        show: false
                    },
                    fontFamily: 'inherit',
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 500
                    }
                },
                series: [{
                    name: 'Penjualan',
                    data: @json($chartData)
                }],
                xaxis: {
                    categories: @json($chartLabels),
                    labels: {
                        style: {
                            colors: '#666'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function(val) {
                            if (val >= 1000000) {
                                return 'Rp ' + (val / 1000000).toFixed(1) + 'jt';
                            } else if (val >= 1000) {
                                return 'Rp ' + (val / 1000).toFixed(0) + 'rb';
                            }
                            return 'Rp ' + val;
                        },
                        style: {
                            colors: '#666'
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
                        }
                    }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.1,
                        stops: [0, 90, 100]
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                colors: ['#206bc4'],
                dataLabels: {
                    enabled: false
                },
                grid: {
                    borderColor: '#f0f0f0',
                    strokeDashArray: 3
                }
            };

            if (salesChart) {
                salesChart.destroy();
            }

            salesChart = new ApexCharts(chartEl, options);
            salesChart.render();
        }

        document.addEventListener('DOMContentLoaded', initChart);

        // Reinit on Livewire update
        document.addEventListener('livewire:navigated', initChart);

        Livewire.hook('morph.updated', ({
            component
        }) => {
            setTimeout(initChart, 100);
        });
    </script>
@endpush
