<?php

namespace App\Livewire;

use App\Models\BatchMovement;
use App\Models\cashDrawer;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Utils\PaymentUtil;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class SalePos extends Component
{
    use WithPagination;

    public $businessId;

    public $searchProduct = null;

    public $cashDrawer = null;

    public $defaultCustomer = null;

    public $openingBalance = 0;

    public $closingBalanceManual = 0;

    public $cashDrawerNote = '';

    public function updatedSearchProduct($value)
    {
        if (empty($value)) {
            return;
        }

        // Perform the same query as render, but get count
        $query = Product::where('business_id', $this->businessId)
            ->where('is_active', true)
            ->where('stok_aktual', '>', 0)
            ->where(function ($q) use ($value) {
                $q->where('nama_produk', 'LIKE', "%{$value}%")
                    ->orWhere('sku', 'LIKE', "%{$value}%");
            })
            ->when($this->selectedCategory, function ($q) {
                $q->where('category_id', $this->selectedCategory);
            });

        if ($query->count() === 1) {
            $product = $query->first();
            $this->dispatch('add-to-cart', product: $product);
            $this->searchProduct = '';
        }
    }

    public $selectedCategory = '';

    public $cart = [];

    protected $listeners = ['saveSale'];

    public function mount()
    {
        $this->businessId = auth()->user()->business_id;
        $this->checkCashDrawer();

        $this->defaultCustomer = Customer::where('business_id', $this->businessId)
            ->where('nama_pelanggan', 'LIKE', '%umum%')
            ->first();
    }

    public function checkCashDrawer()
    {
        $this->cashDrawer = cashDrawer::where('business_id', $this->businessId)
            ->where('user_id', auth()->id())
            ->where('status', 'OPEN')
            ->first();
    }

    public function openCashier()
    {
        $this->validate([
            'openingBalance' => 'required|numeric|min:0',
        ]);

        $this->cashDrawer = cashDrawer::create([
            'business_id' => $this->businessId,
            'user_id' => auth()->id(),
            'tanggal_buka' => now(),
            'saldo_awal' => $this->openingBalance,
            'status' => 'OPEN',
        ]);

        $this->dispatch('alert', type: 'success', message: 'Kasir berhasil dibuka!');
        $this->dispatch('close-modal', id: 'openCashierModal');
    }

    public function closeCashier()
    {
        if (! $this->cashDrawer) {
            return;
        }

        $salesTotal = Sale::where('business_id', $this->businessId)
            ->where('user_id', auth()->id())
            ->whereBetween('created_at', [$this->cashDrawer->tanggal_buka, now()])
            ->sum('dibayar');

        $expectedBalance = $this->cashDrawer->saldo_awal + $salesTotal;

        $this->cashDrawer->update([
            'tanggal_tutup' => now(),
            'saldo_akhir' => $this->closingBalanceManual,
            'saldo_akhir_aplikasi' => $expectedBalance,
            'selisih' => $this->closingBalanceManual - $expectedBalance,
            'catatan' => $this->cashDrawerNote,
            'status' => 'CLOSED',
        ]);

        $this->dispatch('alert', type: 'success', message: 'Kasir berhasil ditutup!');
        $this->dispatch('open-receipt', url: '/penjualan/cetak-struk-kasir/'.$this->cashDrawer->id);
        $this->cashDrawer = null;
        $this->dispatch('close-modal', id: 'closeCashierModal');
    }

    public function loadCustomers($query, $offset = 0)
    {
        $perPage = 5;
        $customers = Customer::where('business_id', $this->businessId)
            ->where('nama_pelanggan', 'LIKE', "%{$query}%")
            ->orWhere('kode_pelanggan', 'LIKE', "%{$query}%")
            ->with(['customerGroup.productPrices.product'])
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return ['data' => $customers];
    }

    public function saveSale($data)
    {
        if (! $this->cashDrawer) {
            $this->dispatch('alert', type: 'error', message: 'Kasir belum dibuka!');

            return;
        }

        if (empty($data['products'])) {
            $this->dispatch('alert', type: 'error', message: 'Keranjang kosong');

            return;
        }

        DB::beginTransaction();
        try {
            $user = auth()->user();
            $nomorPenjualan = 'INV-'.time();
            $tgl = now();

            $sale = $this->createSaleRecord($data, $user, $nomorPenjualan, $tgl);

            $this->processItemsAndStock($data['products'], $sale, $tgl, $nomorPenjualan);

            $this->processPayments($sale, $data, $user, $nomorPenjualan, $tgl);

            DB::commit();

            $this->dispatch('sale-stored');
            $this->dispatch('alert', type: 'success', message: 'Transaksi berhasil disimpan!');
            $this->dispatch('open-receipt', url: '/penjualan/cetak-struk/'.$sale->id);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: 'Error: '.$e->getMessage());
        }
    }

    private function createSaleRecord($data, $user, $nomorPenjualan, $tgl)
    {
        $pay = $this->parseNumber($data['bayar']);
        $grandTotal = $this->parseNumber($data['grandTotal']);
        $jenisPembayaran = $data['payment_method'] == 'credit' ? 'credit' : 'cash';
        $status = 'completed';

        if ($pay < $grandTotal) {
            $status = 'partial';
            $jenisPembayaran = 'credit';
        }

        $keterangan = $data['note'] ?? 'POS Transaction';

        return Sale::create([
            'business_id' => $this->businessId,
            'customer_id' => $data['customer_id'] ?: null,
            'user_id' => $user->id,
            'no_invoice' => $nomorPenjualan,
            'tanggal_transaksi' => $tgl,
            'jenis_pembayaran' => $jenisPembayaran,
            'subtotal' => $this->parseNumber($data['grandTotal']) + $this->calculateGlobalValue($data['globalDiskon'] ?? [], $data['grandTotal']),

            'jenis_diskon' => $data['globalDiskon']['jenis'] ?? 'nominal',
            'jumlah_diskon' => $this->parseNumber($data['globalDiskon']['jumlah'] ?? 0),
            'jenis_cashback' => $data['globalCashback']['jenis'] ?? 'nominal',
            'jumlah_cashback' => $this->parseNumber($data['globalCashback']['jumlah'] ?? 0),
            'jumlah_pajak' => 0,
            'total' => $grandTotal,
            'dibayar' => $pay,
            'kembalian' => $this->parseNumber($data['kembalian']),
            'jumlah_utang' => max(0, $grandTotal - $pay),
            'status' => $status,
            'keterangan' => $keterangan,
        ]);
    }

    private function calculateGlobalValue($setting, $baseTotal)
    {
        $amt = $this->parseNumber($setting['jumlah'] ?? 0);
        $type = $setting['jenis'] ?? 'nominal';

        if ($type == 'persen') {
            if ($amt >= 100) {
                return 0;
            }

            return $baseTotal * ($amt / (100 - $amt));
        }

        return $amt;
    }

    private function processItemsAndStock($products, $sale, $tgl, $nomorPenjualan)
    {
        $allBatchMovements = [];
        $timestamp = now();

        foreach ($products as $item) {
            $qty = $item['qty'];
            $productId = $item['id'];

            $needed = $qty;
            $totalHpp = 0;
            $currentProductBatchMovements = [];

            $batches = ProductBatch::where('product_id', $productId)
                ->where('status', 'ACTIVE')
                ->where('jumlah_saat_ini', '>', 0)
                ->orderBy('tanggal_pembelian', 'asc')
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {
                if ($needed <= 0) {
                    break;
                }
                $take = min($needed, $batch->jumlah_saat_ini);

                $batch->jumlah_saat_ini -= $take;
                if ($batch->jumlah_saat_ini == 0) {
                    $batch->status = 'DEPLETED';
                }
                $batch->save();

                $totalHpp += ($take * $batch->harga_satuan);
                $currentProductBatchMovements[] = [
                    'batch_id' => $batch->id,
                    'qty_taken' => $take,
                    'cost' => $batch->harga_satuan,
                ];
                $needed -= $take;
            }

            if ($needed > 0) {
                $productMaster = Product::find($productId);
                $fallbackCost = $productMaster->harga_beli ?? 0;
                $totalHpp += ($needed * $fallbackCost);
            }

            $avgHpp = ($qty > 0) ? ($totalHpp / $qty) : 0;
            $itemSubtotal = $this->parseNumber($item['price']) * $qty;
            $itemDiscount = isset($item['diskon']) ? $this->calculateItemDiscount($item) : 0;

            $detail = $sale->saleDetails()->create([
                'product_id' => $productId,
                'jumlah' => $qty,
                'harga_satuan' => $this->parseNumber($item['price']),
                'jenis_diskon' => 'nominal',
                'jumlah_diskon' => $itemDiscount,
                'jenis_cashback' => 'nominal',
                'jumlah_cashback' => 0,
                'subtotal' => $itemSubtotal - $itemDiscount,
                'hpp' => $avgHpp,
                'profit' => ($itemSubtotal - $itemDiscount) - $totalHpp,
            ]);

            foreach ($currentProductBatchMovements as $bm) {
                $allBatchMovements[] = [
                    'product_id' => $productId,
                    'batch_id' => $bm['batch_id'],
                    'qty_taken' => $bm['qty_taken'],
                    'cost' => $bm['cost'],
                    'detail_id' => $detail->id,
                ];
            }

            $stockMovement = StockMovement::create([
                'business_id' => $this->businessId,
                'product_id' => $productId,
                'tanggal_perubahan_stok' => $tgl,
                'jenis_perubahan' => 'sale',
                'jumlah_perubahan' => -$qty,
                'reference_id' => $sale->id,
                'reference_type' => 'sale',
                'catatan' => 'Penjualan POS '.$nomorPenjualan,
            ]);

            foreach ($allBatchMovements as &$bm) {
                if ($bm['product_id'] === $productId && ! isset($bm['stock_movement_id'])) {
                    $bm['stock_movement_id'] = $stockMovement->id;
                    $bm['business_id'] = $this->businessId;
                    $bm['jenis_transaksi'] = 'sale';
                    $bm['transaction_detail_id'] = $bm['detail_id'];
                    $bm['jumlah'] = $bm['qty_taken'];
                    $bm['harga_satuan'] = $bm['cost'];
                    $bm['tanggal_perubahan'] = $tgl;
                    $bm['created_at'] = $timestamp;
                    $bm['updated_at'] = $timestamp;
                }
            }
            unset($bm);

            Product::where('id', $productId)->decrement('stok_aktual', $qty);
        }

        $finalBatchMovements = [];
        foreach ($allBatchMovements as $bm) {
            if (isset($bm['stock_movement_id'])) {
                unset($bm['product_id'], $bm['qty_taken'], $bm['cost'], $bm['detail_id']);
                $finalBatchMovements[] = $bm;
            }
        }
        if (! empty($finalBatchMovements)) {
            BatchMovement::insert($finalBatchMovements);
        }
    }

    private function processPayments($sale, $data, $user, $nomorPenjualan, $tgl)
    {
        $pay = $this->parseNumber($data['bayar']);
        $grandTotal = $this->parseNumber($data['grandTotal']);
        $metodeBayar = $data['payment_method'];

        $jenisPembayaran = ($pay < $grandTotal) ? 'credit' : 'cash';

        if ($pay > $grandTotal) {
            $pay -= $this->parseNumber($data['kembalian']);
        }

        $kodeRekening = PaymentUtil::ambilRekening('sales', $jenisPembayaran, $metodeBayar, null);

        $details = $sale->saleDetails;
        $totalHppAll = $details->sum(function ($d) {
            return $d->hpp * $d->jumlah;
        });

        $totalDiskonAll = $this->calculateRealValue($data['globalDiskon'] ?? [], $data['grandTotal']);
        $totalCashbackAll = $this->calculateRealValue($data['globalCashback'] ?? [], $data['grandTotal']);

        $totalProfit = $pay - $totalHppAll;

        $payments = [];
        $timestamp = now();

        if ($totalHppAll > 0) {
            $payments[] = [
                'business_id' => $this->businessId,
                'user_id' => $user->id,
                'no_pembayaran' => $nomorPenjualan.'-HPP',
                'tanggal_pembayaran' => $tgl,
                'jenis_transaksi' => 'sale',
                'transaction_id' => $sale->id,
                'total_harga' => $totalHppAll,
                'metode_pembayaran' => $metodeBayar,
                'no_referensi' => null,
                'catatan' => 'HPP Penjualan POS',
                'rekening_debit' => $kodeRekening['sales']['rekening_debit'],
                'rekening_kredit' => $kodeRekening['sales']['rekening_kredit'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        if ($totalProfit > 0) {
            $payments[] = [
                'business_id' => $this->businessId,
                'user_id' => $user->id,
                'no_pembayaran' => $nomorPenjualan.'-PROFIT',
                'tanggal_pembayaran' => $tgl,
                'jenis_transaksi' => 'sale',
                'transaction_id' => $sale->id,
                'total_harga' => $totalProfit,
                'metode_pembayaran' => $metodeBayar,
                'no_referensi' => null,
                'catatan' => 'Laba Penjualan POS',
                'rekening_debit' => $kodeRekening['laba']['rekening_debit'],
                'rekening_kredit' => $kodeRekening['laba']['rekening_kredit'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        if ($totalDiskonAll > 0) {
            $payments[] = [
                'business_id' => $this->businessId,
                'user_id' => $user->id,
                'no_pembayaran' => $nomorPenjualan.'-DISC',
                'tanggal_pembayaran' => $tgl,
                'jenis_transaksi' => 'sale',
                'transaction_id' => $sale->id,
                'total_harga' => $totalDiskonAll,
                'metode_pembayaran' => 'internal',
                'catatan' => 'Diskon Global POS',
                'rekening_debit' => $kodeRekening['sales-diskon']['rekening_debit'] ?? '',
                'rekening_kredit' => $kodeRekening['sales-diskon']['rekening_kredit'] ?? '',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        if ($totalCashbackAll > 0) {
            $payments[] = [
                'business_id' => $this->businessId,
                'user_id' => $user->id,
                'no_pembayaran' => $nomorPenjualan.'-CSHBK',
                'tanggal_pembayaran' => $tgl,
                'jenis_transaksi' => 'sale',
                'transaction_id' => $sale->id,
                'total_harga' => $totalCashbackAll,
                'metode_pembayaran' => 'internal',
                'catatan' => 'Cashback Global POS',
                'rekening_debit' => $kodeRekening['sales-cashback']['rekening_debit'] ?? '',
                'rekening_kredit' => $kodeRekening['sales-cashback']['rekening_kredit'] ?? '',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        if (! empty($payments)) {
            \App\Models\Payment::insert($payments);
        }
    }

    private function calculateRealValue($setting, $baseInfo)
    {
        $amt = $this->parseNumber($setting['jumlah'] ?? 0);
        $type = $setting['jenis'] ?? 'nominal';

        if ($amt <= 0) {
            return 0;
        }

        if ($type == 'nominal') {
            return $amt;
        }

        // For percentage, calculate derived amount from baseInfo (Net)
        if ($amt >= 100) {
            return 0;
        }

        return $baseInfo * ($amt / (100 - $amt));
    }

    private function calculateItemDiscount($item)
    {

        if (! isset($item['diskon'])) {
            return 0;
        }
        $d = $item['diskon'];
        if ($d['jenis'] == 'nominal') {
            return $this->parseNumber($d['jumlah']);
        }

        return ($this->parseNumber($item['price']) * $item['qty'] * $this->parseNumber($d['jumlah'])) / 100;
    }

    private function parseNumber($val)
    {
        return (float) str_replace(',', '', $val);
    }

    #[Layout('layouts.app')]
    #[Title('Sale POS')]
    public function render()
    {
        $products = Product::where('business_id', $this->businessId)
            ->where('is_active', true)
            ->where('stok_aktual', '>', 0)
            ->where(function ($q) {
                $q->where('nama_produk', 'LIKE', "%{$this->searchProduct}%")
                    ->orWhere('sku', 'LIKE', "%{$this->searchProduct}%");
            })
            ->when($this->selectedCategory, function ($q) {
                $q->where('category_id', $this->selectedCategory);
            })
            ->paginate(20);

        $categories = Category::where('business_id', $this->businessId)->get();

        return view('livewire.sale-pos', ['products' => $products, 'categories' => $categories]);
    }
}
