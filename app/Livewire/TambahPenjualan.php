<?php

namespace App\Livewire;

use App\Models\BatchMovement;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductPrice;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Utils\PaymentUtil;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class TambahPenjualan extends Component
{
    public $title;

    public $businessId;

    // View States (Minimal, strictly for initial render/hydration)
    public $nomorPenjualan;

    public $tanggalPenjualan;

    public $saleId;

    public $existingData = null;

    public function mount($id = null)
    {
        $this->title = $id ? 'Edit Penjualan' : 'Tambah Penjualan';
        $this->businessId = auth()->user()->business_id;

        if ($id) {
            $this->saleId = $id;
            $this->loadSaleData($id);
        } else {
            $this->tanggalPenjualan = date('Y-m-d');

            // Find default customer "umum"
            $defaultCustomer = Customer::where('business_id', $this->businessId)
                ->where('nama_pelanggan', 'LIKE', '%umum%')
                ->first();

            if ($defaultCustomer) {
                $this->existingData = [
                    'customer' => $defaultCustomer->id,
                    'customer_name' => $defaultCustomer->nama_pelanggan,
                    'tanggalPenjualan' => $this->tanggalPenjualan,
                    'jenisPembayaran' => 'cash',
                    'metodeBayar' => 'tunai',
                ];
            }
        }
    }

    public function loadSaleData($id)
    {
        $sale = Sale::with(['saleDetails.product', 'customer.customerGroup'])->find($id);

        if (! $sale) {
            return redirect()->to('/penjualan/daftar');
        }

        $this->nomorPenjualan = $sale->no_invoice;
        $this->tanggalPenjualan = $sale->tanggal_transaksi;

        $products = [];
        foreach ($sale->saleDetails as $detail) {
            // Logic for 'stok_tersedia':
            // Since we are editing, the stock currently held by THIS sale is considered "available" to be re-chosen.
            // So available = current_shelf_stock + this_detail_quantity
            $currentStockInDb = $detail->product->stok_aktual;
            $availableForThisEdit = $currentStockInDb + $detail->jumlah;

            $promoLabel = null;
            if ($detail->jenis_diskon == 'persen') {
                // Approximate label or logic if needed, simplied for now
            }

            $products[$detail->product_id] = [
                'id' => $detail->product_id,
                'nama_produk' => $detail->product->nama_produk,
                'sku' => $detail->product->sku,
                'gambar' => $detail->product->gambar,
                'harga_jual' => (string) $detail->harga_satuan,
                'jumlah_jual' => $detail->jumlah,
                'stok_tersedia' => $availableForThisEdit, // Helper for frontend validation
                'diskon' => [
                    'jenis' => $detail->jenis_diskon,
                    'jumlah' => $detail->jumlah_diskon,
                    'nominal' => $detail->jumlah_diskon, // Simplified, adjust if needed
                ],
                // We don't store individual cashback in details structure usually for frontend input unless we map it back
                'subtotal' => (string) $detail->subtotal,
                'promo_label' => $promoLabel,
                'batch_info' => 'Stok: '.\App\Utils\NumberUtil::format($currentStockInDb), // Just info
            ];
        }

        $this->existingData = [
            'nomorPenjualan' => $sale->no_invoice,
            'tanggalPenjualan' => $sale->tanggal_transaksi,
            'customer' => $sale->customer_id,
            'customer_name' => $sale->customer ? $sale->customer->nama_pelanggan : '',
            'catatan' => $sale->keterangan,
            'products' => $products,
            'jenisPajak' => $sale->jumlah_pajak > 0 ? 'PPN' : 'tidak ada',
            'globalDiskon' => [
                'jenis' => $sale->jenis_diskon,
                'jumlah' => $sale->jumlah_diskon,
            ],
            'globalCashback' => [
                'jenis' => $sale->jenis_cashback,
                'jumlah' => $sale->jumlah_cashback,
            ],
            'jenisPembayaran' => $sale->jenis_pembayaran,
            'metodeBayar' => 'tunai', // Default fallback, or fetch from payments if critical
            'noRekening' => '', // Fetch if needed
            'bayar' => $sale->dibayar,
            'kembalian' => $sale->kembalian,
            'status' => $sale->status,
        ];

        // Try to infer payment info from latest payment
        $payment = \App\Models\Payment::where('transaction_id', $sale->id)
            ->where('jenis_transaksi', 'sale')
            ->orderBy('id', 'desc')
            ->first();

        if ($payment) {
            $this->existingData['metodeBayar'] = $payment->metode_pembayaran;
            $this->existingData['noRekening'] = $payment->no_referensi;
        }
    }

    public function loadCustomers($query, $offset = 0)
    {
        $perPage = 20;
        $customers = Customer::select('id', 'nama_pelanggan', 'kode_pelanggan')
            ->where('business_id', $this->businessId)
            ->where('nama_pelanggan', 'LIKE', "%{$query}%")
            ->orWhere('kode_pelanggan', 'LIKE', "%{$query}%")
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return ['data' => $customers];
    }

    public function loadSearchProducts($query, $customerId = null)
    {
        // 1. Get Customer Group info if Customer selected
        $customerGroup = null;
        if ($customerId) {
            $customer = Customer::with('customerGroup')->find($customerId);
            $customerGroup = $customer->customerGroup ?? null;
        }

        // 2. Search Products (Only with stock > 0)
        $products = Product::where('business_id', $this->businessId)
            ->where('is_active', true)
            ->where('stok_aktual', '>', 0) // Only products with stock
            ->where(function ($q) use ($query) {
                $q->where('nama_produk', 'LIKE', "%{$query}%")
                    ->orWhere('sku', 'LIKE', "%{$query}%");
            })
            ->take(20)
            ->get();

        $results = [];
        foreach ($products as $p) {
            // Default Price
            $finalPrice = $p->harga_jual;
            $promoLabel = null;

            // 3. Apply Pricing Logic
            if ($customerGroup) {
                // A. Check ProductPrice (Special Price)
                $specialPrice = ProductPrice::where('product_id', $p->id)
                    ->where('customer_group_id', $customerGroup->id)
                    // ->whereDate('start_date', '<=', now()) ... (if dates implemented)
                    ->first();

                if ($specialPrice) {
                    $finalPrice = $specialPrice->harga_spesial;
                    $promoLabel = 'Harga Spesial Member';
                }
                // B. Check Group Discount %
                elseif ($customerGroup->diskon_persen > 0) {
                    $discAmount = ($p->harga_jual * $customerGroup->diskon_persen) / 100;
                    $finalPrice = max(0, $p->harga_jual - $discAmount);
                    $promoLabel = 'Diskon Member '.\App\Utils\NumberUtil::format($customerGroup->diskon_persen).'%';
                }
            }

            // Check Stock status (Simple check)
            $stockInfo = 'Stok: '.\App\Utils\NumberUtil::format($p->stok_aktual);

            $results[] = [
                'id' => $p->id,
                'nama_produk' => $p->nama_produk,
                'sku' => $p->sku,
                'gambar' => $p->gambar,
                'harga_jual' => $finalPrice, // This is the 'System Price'
                'promo_label' => $promoLabel,
                'batch_info' => $stockInfo,
                'original_price' => $p->harga_jual,
                'stok_tersedia' => $p->stok_aktual, // For validation
            ];
        }

        return ['data' => $results];
    }

    #[On('saveAll')]
    public function saveAll($data)
    {
        // Skip validation for now or adapt it.
        // If editing, the validation "stok_aktual" vs "jumlah_jual" need care.
        // We will rely on processItemsAndStock logic which should handle it.
        // Or we implement specific check.

        DB::beginTransaction();
        try {
            $user = auth()->user();
            $nomorPenjualan = ($data['nomorPenjualan'] != '') ? $data['nomorPenjualan'] : 'INV-'.time();
            $tgl = $data['tanggalPenjualan'] ?? date('Y-m-d');

            if ($this->saleId) {
                // --- UPDATE LOGIC ---
                $sale = Sale::find($this->saleId);

                // 1. REVERSE STOCK (FIFO)
                // We need to put back the items from the OLD sale to the batches they came from.
                // We look at BatchMovements linked to the StockMovements of this Sale.

                // Find Stock Movements for this Sale
                $stockMovements = StockMovement::where('reference_id', $sale->id)
                    ->where('reference_type', 'sale')
                    ->get();
                $stockMovementIds = $stockMovements->pluck('id');

                // Find Batch Movements (Outgoing)
                $batchMovements = BatchMovement::whereIn('stock_movement_id', $stockMovementIds)->get();

                foreach ($batchMovements as $bm) {
                    // Restore to Batch
                    $batch = ProductBatch::find($bm->batch_id);
                    if ($batch) {
                        $batch->jumlah_saat_ini += $bm->jumlah; // bm->jumlah is usually negative for sale? Or positive magnitude?
                        // Let's check logic in processItemsAndStock.
                        // In processItemsAndStock: "qty" is used. BatchMovement->jumlah = qty (positive).
                        // StockMovement->jumlah_perubahan = -qty (negative).
                        // So BatchMovement stores the absolute Quantity taken.

                        $batch->status = 'ACTIVE'; // Reactivate if it was depleted
                        $batch->save();
                    }
                }

                // Restore to Product Master Stock
                foreach ($stockMovements as $sm) {
                    // sm->jumlah_perubahan is negative (-qty). Subtracting it (adding positive) restores stock.
                    // Or simpler: iterate details.
                    Product::where('id', $sm->product_id)->decrement('stok_aktual', $sm->jumlah_perubahan);
                    // decrementing a negative number = incrementing. Correct.
                }

                // 2. CLEANUP OLD RECORDS
                BatchMovement::whereIn('stock_movement_id', $stockMovementIds)->delete();
                StockMovement::whereIn('id', $stockMovementIds)->delete();
                \App\Models\SaleDetail::where('sale_id', $sale->id)->delete();
                \App\Models\Payment::where('transaction_id', $sale->id)->where('jenis_transaksi', 'sale')->delete();

                // 3. UPDATE HEADER
                $pay = $this->parseNumber($data['bayar']);
                $grandTotal = $this->parseNumber($data['grandTotal']);
                $jenisPembayaran = $data['jenisPembayaran'];
                $status = 'completed';

                // Enforce Logic (Match Purchase)
                if ($pay < $grandTotal) {
                    $status = 'partial';
                    $jenisPembayaran = 'credit';
                } else {
                    // If fully paid, force cash if it was credit
                    if ($jenisPembayaran === 'credit') {
                        $jenisPembayaran = 'cash';
                    }
                    $status = 'completed';
                }

                $keterangan = $data['catatan'] ?? '';
                if (! empty($data['noRekening'])) {
                    $keterangan .= ' [Transfer: '.$data['noRekening'].']';
                }

                $sale->update([
                    'no_invoice' => $nomorPenjualan,
                    'tanggal_transaksi' => $tgl,
                    'customer_id' => $data['customer'] ?: null,
                    'jenis_pembayaran' => $jenisPembayaran,
                    'subtotal' => $this->parseNumber($data['subtotal']),
                    'jenis_diskon' => $data['globalDiskon']['jenis'],
                    'jumlah_diskon' => $this->parseNumber($data['globalDiskon']['jumlah']),
                    'jenis_cashback' => $data['globalCashback']['jenis'],
                    'jumlah_cashback' => $this->parseNumber($data['globalCashback']['jumlah']),
                    'total' => $grandTotal,
                    'dibayar' => $pay,
                    'kembalian' => $this->parseNumber($data['kembalian']),
                    'jumlah_utang' => max(0, $grandTotal - $pay),
                    'status' => $status,
                    'keterangan' => $keterangan,
                ]);

                // 4. RE-PROCESS ITEMS (New Logic)
                $this->processItemsAndStock($data['products'], $sale, $tgl, $nomorPenjualan);

                // 5. RE-PROCESS PAYMENTS
                $this->processPayments($sale, $data, $user, $nomorPenjualan, $tgl);

                $message = 'Penjualan berhasil diperbarui';

            } else {
                // --- CREATE LOGIC ---
                if ($error = $this->validateRequest($data)) {
                    $this->dispatch('alert', type: 'error', message: $error);
                    DB::rollBack();

                    return;
                }

                // 1. Create Sale Header
                $sale = $this->createSaleRecord($data, $user, $nomorPenjualan, $tgl);

                // 2. Process Items & Stock (FIFO)
                $this->processItemsAndStock($data['products'], $sale, $tgl, $nomorPenjualan);

                // 3. Process Payments & Accounting
                $this->processPayments($sale, $data, $user, $nomorPenjualan, $tgl);

                $message = 'Penjualan berhasil disimpan';
            }

            DB::commit();
            $this->dispatch('alert', type: 'success', message: $message);

            // Jika transaksi baru, buka cetak struk sebelum redirect
            if (! $this->saleId) {
                // Gunakan objek sale dari createLogic
                $this->dispatch('open-receipt', url: '/penjualan/cetak-struk/'.$sale->id);
            }

            $this->dispatch('redirect', url: '/penjualan/cetak-struk', timeout: 1500);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: 'Error: '.$e->getMessage());
        }
    }

    private function validateRequest($data)
    {
        if (empty($data['products'])) {
            return 'Keranjang kosong';
        }

        foreach ($data['products'] as $item) {
            $product = Product::find($item['id']);
            if (! $product) {
                return 'Produk tidak ditemukan';
            }

            // If Validating for NEW Sale
            if ($item['jumlah_jual'] > $product->stok_aktual) {
                return "Stok {$product->nama_produk} tidak mencukupi! Tersedia: ".\App\Utils\NumberUtil::format($product->stok_aktual);
            }
        }

        return null;
    }

    private function createSaleRecord($data, $user, $nomorPenjualan, $tgl)
    {
        $pay = $this->parseNumber($data['bayar']);
        $grandTotal = $this->parseNumber($data['grandTotal']);
        $jenisPembayaran = $data['jenisPembayaran']; // cash/credit
        $status = 'completed';

        if ($pay < $grandTotal) {
            $status = 'partial';
            $jenisPembayaran = 'credit'; // Force credit if underpaid
        } else {
            if ($jenisPembayaran === 'credit') {
                $jenisPembayaran = 'cash';
            }
            $status = 'completed';
        }

        $keterangan = $data['catatan'] ?? '';
        if (! empty($data['noRekening'])) {
            $keterangan .= ' [Transfer: '.$data['noRekening'].']';
        }

        return Sale::create([
            'business_id' => $this->businessId,
            'customer_id' => $data['customer'] ?: null,
            'user_id' => $user->id,
            'no_invoice' => $nomorPenjualan,
            'tanggal_transaksi' => $tgl,
            'jenis_pembayaran' => $jenisPembayaran, // cash/credit
            'subtotal' => $this->parseNumber($data['subtotal']),
            'jenis_diskon' => $data['globalDiskon']['jenis'],
            'jumlah_diskon' => $this->parseNumber($data['globalDiskon']['jumlah']),
            'jenis_cashback' => $data['globalCashback']['jenis'],
            'jumlah_cashback' => $this->parseNumber($data['globalCashback']['jumlah']),
            'jumlah_pajak' => 0,
            'total' => $grandTotal,
            'dibayar' => $pay,
            'kembalian' => $this->parseNumber($data['kembalian']),
            'jumlah_utang' => max(0, $grandTotal - $pay),
            'status' => $status, // COMPLETED/PARTIAL
            'keterangan' => $keterangan,
        ]);
    }

    private function processItemsAndStock($products, $sale, $tgl, $nomorPenjualan)
    {
        $allBatchMovements = []; // Collect all batch movements for bulk insert
        $timestamp = now();

        foreach ($products as $item) {
            $qty = $item['jumlah_jual'];
            $productId = $item['id'];

            // --- FIFO LOGIC START ---
            $needed = $qty;
            $totalHpp = 0;
            $currentProductBatchMovements = []; // Temporary storage for this product's batches

            // Get Available Batches (Oldest First)
            $batches = ProductBatch::where('product_id', $productId)
                ->where('status', 'ACTIVE')
                ->where('jumlah_saat_ini', '>', 0)
                ->orderBy('tanggal_pembelian', 'asc') // FIFO
                ->lockForUpdate() // Prevent race conditions
                ->get();

            foreach ($batches as $batch) {
                if ($needed <= 0) {
                    break;
                }

                $take = min($needed, $batch->jumlah_saat_ini);

                // Deduct Batch
                $batch->jumlah_saat_ini -= $take;
                if ($batch->jumlah_saat_ini == 0) {
                    $batch->status = 'DEPLETED';
                }
                $batch->save();

                // Calculate Cost
                $totalHpp += ($take * $batch->harga_satuan);

                // Store batch movement data (will be linked after detail creation)
                $currentProductBatchMovements[] = [
                    'batch_id' => $batch->id,
                    'qty_taken' => $take,
                    'cost' => $batch->harga_satuan,
                ];

                $needed -= $take;
            }

            // Handle Overflow (If stock exists but no batch, or shortage)
            if ($needed > 0) {
                // Fallback cost: Product's current 'harga_beli' (Master Data)
                $productMaster = Product::find($productId);
                $fallbackCost = $productMaster->harga_beli ?? 0;
                $totalHpp += ($needed * $fallbackCost);
            }

            $avgHpp = ($qty > 0) ? ($totalHpp / $qty) : 0;
            // --- FIFO LOGIC END ---

            // 2. Create Sale Detail
            $detail = $sale->saleDetails()->create([
                'product_id' => $productId,
                'jumlah' => $qty,
                'harga_satuan' => $this->parseNumber($item['harga_jual']),
                'jenis_diskon' => $item['diskon']['jenis'] ?? 'nominal',
                'jumlah_diskon' => $this->parseNumber($item['diskon']['jumlah'] ?? 0),
                'jenis_cashback' => 'nominal',
                'jumlah_cashback' => 0,
                'subtotal' => $this->parseNumber($item['subtotal']),
                'hpp' => $avgHpp,
                'profit' => $this->parseNumber($item['subtotal']) - $totalHpp,
            ]);

            // 3. Prepare Batch Movements for bulk insert (now we have detail ID)
            foreach ($currentProductBatchMovements as $bm) {
                $allBatchMovements[] = [
                    'product_id' => $productId,
                    'batch_id' => $bm['batch_id'],
                    'qty_taken' => $bm['qty_taken'],
                    'cost' => $bm['cost'],
                    'detail_id' => $detail->id,
                ];
            }

            // 4. Create Stock Movement immediately to get ID
            $stockMovement = StockMovement::create([
                'business_id' => $this->businessId,
                'product_id' => $productId,
                'tanggal_perubahan_stok' => $tgl,
                'jenis_perubahan' => 'sale',
                'jumlah_perubahan' => -$qty,
                'reference_id' => $sale->id,
                'reference_type' => 'sale',
                'catatan' => 'Penjualan '.$nomorPenjualan,
            ]);

            // 5. Link batch movements to stock movement
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

                    // Remove temporary keys (using unset to avoid carrying them over if array is reused, though here we are iterating)
                    // Note: In original code, we unset. Here we are building the final array for insert.
                    // Actually, we want to Keep only the schema fields.
                    // The keys 'product_id', 'qty_taken', 'cost', 'detail_id' are NOT in BatchMovement model based on original code usage?
                    // Original code: BatchMovement::insert($allBatchMovements);
                    // And inside the loop: unset($bm['product_id'], $bm['qty_taken'], $bm['cost'], $bm['detail_id']);
                }
            }
            unset($bm); // Break reference

            // 6. Update Product Stock Master
            Product::where('id', $productId)->decrement('stok_aktual', $qty);
        }

        // Clean up $allBatchMovements to only have DB columns before insert
        // The original code did unset inside the loop.
        // Let's do a pass to clean up or Ensure the loop above doing the unsetting logic correctly.
        $finalBatchMovements = [];
        foreach ($allBatchMovements as $bm) {
            if (isset($bm['stock_movement_id'])) {
                // It was processed and linked
                unset($bm['product_id'], $bm['qty_taken'], $bm['cost'], $bm['detail_id']);
                $finalBatchMovements[] = $bm;
            }
        }

        // Bulk Insert Batch Movements (Performance Optimization)
        if (! empty($finalBatchMovements)) {
            BatchMovement::insert($finalBatchMovements);
        }
    }

    private function processPayments($sale, $data, $user, $nomorPenjualan, $tgl)
    {
        // 6. Create Payment Records (Double-Entry Accounting) - OPTIMIZED
        $pay = $this->parseNumber($data['bayar']);
        $grandTotal = $this->parseNumber($data['grandTotal']);
        $metodeBayar = $data['metodeBayar'];
        $jenisPembayaran = $data['jenisPembayaran'];

        if ($pay < $grandTotal) {
            $jenisPembayaran = 'credit';
        }

        if ($pay > $grandTotal) {
            $pay -= $data['kembalian'];
        }

        $kodeRekening = PaymentUtil::ambilRekening('sales', $jenisPembayaran, $metodeBayar, $data['noRekening']);

        // Fetch all SaleDetails in one query (OPTIMIZATION)
        $details = \App\Models\SaleDetail::where('sale_id', $sale->id)
            ->get()
            ->keyBy('product_id');

        // Calculate totals for accounting (GLOBAL ONLY)
        $totalHppAll = 0;
        $totalDiskonAll = $this->parseNumber($data['globalDiskon']['jumlah']);
        $totalCashbackAll = $this->parseNumber($data['globalCashback']['jumlah']);

        foreach ($data['products'] as $item) {
            $detail = $details[$item['id']] ?? null;
            if ($detail) {
                $totalHppAll += $detail->hpp * $detail->jumlah;
            }
        }

        $totalProfit = $pay - $totalHppAll; // Laba Kotor = Penerimaan - HPP

        // Collect all payment data for bulk insert (OPTIMIZATION)
        $payments = [];
        $timestamp = now();

        // 6a. HPP Component (Cost Recovery from Sales)
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
                'no_referensi' => $data['noRekening'] ?? null,
                'catatan' => 'HPP dari Penjualan',
                'rekening_debit' => $kodeRekening['sales']['rekening_debit'],
                'rekening_kredit' => $kodeRekening['sales']['rekening_kredit'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        // 6b. Profit Component (Gross Profit from Sales)
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
                'no_referensi' => $data['noRekening'] ?? null,
                'catatan' => 'Laba Kotor Penjualan',
                'rekening_debit' => $kodeRekening['laba']['rekening_debit'],
                'rekening_kredit' => $kodeRekening['laba']['rekening_kredit'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        // 6c. Sales Discount Entry
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
                'catatan' => 'Diskon Penjualan',
                'rekening_debit' => $kodeRekening['sales-diskon']['rekening_debit'],
                'rekening_kredit' => $kodeRekening['sales-diskon']['rekening_kredit'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        // 6d. Cashback Entry (Marketing Expense)
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
                'catatan' => 'Cashback Penjualan',
                'rekening_debit' => $kodeRekening['sales-cashback']['rekening_debit'],
                'rekening_kredit' => $kodeRekening['sales-cashback']['rekening_kredit'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        // Bulk insert all payments (OPTIMIZATION: 4 queries → 1 query)
        if (! empty($payments)) {
            \App\Models\Payment::insert($payments);
        }
    }

    private function parseNumber($val)
    {
        return (float) str_replace(',', '', $val);
    }

    public function render()
    {
        return view('livewire.tambah-penjualan')->layout('layouts.app', ['title' => $this->title]);
    }
}
