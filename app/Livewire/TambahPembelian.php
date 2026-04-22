<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Traits\WithTable;
use App\Utils\PaymentUtil;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class TambahPembelian extends Component
{
    use WithTable;

    public $title;

    public $businessId;

    // Form Fields (Livewire still tracks these for initial binding/validation if needed,
    // but primary interaction is client-side)
    public $nomorPembelian;

    public $tanggalPembelian;

    public $supplier;

    public $catatan;

    // Payment Fields
    public $jenisPembayaran = 'cash';

    public $noRekening;

    // Search Fields (Livewire handles the search queries)
    public $searchTerm = '';

    public $searchProduct = '';

    public $purchaseId;

    public $existingData = null;

    public function mount($id = null)
    {
        $this->title = $id ? 'Edit Pembelian' : 'Tambah Pembelian';
        $this->businessId = auth()->user()->business_id;

        if ($id) {
            $this->purchaseId = $id;
            $this->loadPurchaseData($id);
        } else {
            $this->tanggalPembelian = date('Y-m-d');
            $this->nomorPembelian = $this->generatePurchaseNumber();
            
            $this->existingData = [
                'nomorPembelian' => $this->nomorPembelian,
                'tanggalPembelian' => $this->tanggalPembelian,
                'jenisPembayaran' => 'cash',
            ];
        }
    }

    public function loadPurchaseData($id)
    {
        $purchase = Purchase::with(['purchaseDetails.product', 'supplier'])->find($id);

        if (! $purchase) {
            return redirect()->to('/pembelian/daftar');
        }

        $this->nomorPembelian = $purchase->no_pembelian;
        $this->tanggalPembelian = $purchase->tanggal_pembelian;
        $this->supplier = $purchase->supplier_id;
        $this->catatan = $purchase->keterangan; // Note: 'keterangan' usually stores notes, sometimes formatted
        // If keterangan has "[Transfer: ...]", we might want to strip it or keep it?
        // Current save logic appends transfer info. For editing, let's keep it simple or try to clean it if needed.
        // For now, raw load.

        $this->jenisPembayaran = $purchase->jenis_pembayaran;
        // Determine metodeBayar from payments? Or just default/guess?
        // Since we don't strictly track "metodeBayar" in purchase table (it's in payments),
        // we might leave it as 'tunai' or try to fetch from latest payment.
        // For simplicity in this iteration, we focus on the products and main info.

        $products = [];
        foreach ($purchase->purchaseDetails as $detail) {
            $batch = $detail->productBatch;
            $products[$detail->product_id] = [
                'id' => $detail->product_id,
                'nama_produk' => $detail->product->nama_produk,
                'gambar' => $detail->product->gambar,
                'sku' => $detail->product->sku,
                'harga_beli' => (string) $detail->harga_satuan, // Pass as string for formatting
                'jumlah_beli' => $detail->jumlah,
                'unit' => $detail->product->unit ? $detail->product->unit->nama_satuan : '-',
                'allow_decimal' => $detail->product->unit ? (bool)$detail->product->unit->desimal : false,
                'tanggal_kadaluarsa' => $batch ? ($batch->tanggal_kadaluarsa ? $batch->tanggal_kadaluarsa->format('Y-m-d') : '') : '',
                'diskon' => [
                    'jenis' => $detail->jenis_diskon,
                    'jumlah' => $detail->jumlah_diskon, // This often stores the Rate or Nominal depending on logic
                    'nominal' => $detail->jumlah_diskon, // Simplification: assuming nominal for display mainly
                ],
                'cashback' => [
                    'jenis' => $detail->jenis_cashback,
                    'jumlah' => $detail->jumlah_cashback,
                    'nominal' => $detail->jumlah_cashback,
                ],
                'subtotal' => (string) $detail->subtotal,
            ];
        }

        $this->existingData = [
            'nomorPembelian' => $purchase->no_pembelian,
            'tanggalPembelian' => $purchase->tanggal_pembelian,
            'supplier' => $purchase->supplier_id,
            'supplier_name' => $purchase->supplier ? $purchase->supplier->nama_supplier : '',
            'catatan' => $purchase->keterangan,
            'products' => $products,
            'jenisPajak' => $purchase->jumlah_pajak > 0 ? 'PPN' : 'tidak ada',
            'globalDiskon' => [
                'jenis' => $purchase->jenis_diskon,
                'jumlah' => $purchase->jumlah_diskon,
            ],
            'globalCashback' => [
                'jenis' => $purchase->jenis_cashback,
                'jumlah' => $purchase->jumlah_cashback,
            ],
            'jenisPembayaran' => $purchase->jenis_pembayaran,
            'bayar' => $purchase->dibayar,
            'kembalian' => $purchase->kembalian,
            'status' => $purchase->status,
        ];

        // Try to infer payment info from latest payment
        $payment = \App\Models\Payment::where('transaction_id', $purchase->id)
            ->where('jenis_transaksi', 'purchase')
            ->orderBy('id', 'desc')
            ->first();

        if ($payment) {
            $this->existingData['metodeBayar'] = $payment->metode_pembayaran;
            $this->existingData['noRekening'] = $payment->no_referensi;
        }
    }

    public function loadSuppliers($query, $offset = 0)
    {
        $perPage = 50;

        $suppliers = Supplier::select('id', 'nama_supplier')
            ->where('business_id', $this->businessId) // Ensure business scope
            ->where('nama_supplier', 'LIKE', "%{$query}%")
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $total = Supplier::where('business_id', $this->businessId)
            ->where('nama_supplier', 'LIKE', "%{$query}%")
            ->count();

        $hasMore = ($offset + $perPage) < $total;

        return [
            'data' => $suppliers,
            'after' => $hasMore ? ($offset + $perPage) : null,
        ];
    }

    public function loadSearchProducts($query, $offset = 0)
    {
        $perPage = 20;

        $productsQuery = Product::with('unit')
            ->where('business_id', $this->businessId)
            ->where(function ($q) use ($query) {
                $q->where('nama_produk', 'LIKE', "%{$query}%")
                    ->orWhere('sku', 'LIKE', "%{$query}%");
            })
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $total = Product::where('business_id', $this->businessId)
            ->where(function ($q) use ($query) {
                $q->where('nama_produk', 'LIKE', "%{$query}%")
                    ->orWhere('sku', 'LIKE', "%{$query}%");
            })
            ->count();

        $hasMore = ($offset + $perPage) < $total;

        $products = [];
        foreach ($productsQuery as $product) {
            $products[] = [
                'id' => $product->id,
                'nama_produk' => $product->nama_produk,
                'sku' => $product->sku,
                'harga_beli' => $product->harga_beli,
                'gambar' => $product->gambar,
                'unit' => $product->unit ? $product->unit->nama_satuan : '-',
                'allow_decimal' => $product->unit ? (bool)$product->unit->desimal : false,
            ];
        }

        return [
            'data' => $products,
            'after' => $hasMore ? ($offset + $perPage) : null,
        ];
    }

    #[On('save-all')]
    public function saveAll($data)
    {
        if (empty($data['products'])) {
            $this->dispatch('error', 'Belum ada produk yang dipilih');

            return;
        }

        $bayar = $this->parseNumber($data['bayar']);
        $total = $this->parseNumber($data['grandTotal']);

        $jenisPembayaran = $data['jenisPembayaran'];
        $status = $data['status'] ?? 'pending';

        // Enforce Server-Side Logic
        if ($bayar < $total) {
            // If underpaid, force credit unless it's a preorder
            if ($jenisPembayaran !== 'preorder') {
                $jenisPembayaran = 'credit';
            }
            $status = 'partial';
        } else {
            // If fully paid, force cash if it was credit
            if ($jenisPembayaran === 'credit') {
                $jenisPembayaran = 'cash';
            }

            if ($jenisPembayaran === 'preorder') {
                $status = 'paid';
            } else {
                $status = 'completed';
            }
        }

        DB::beginTransaction();
        try {
            $subtotal = $this->parseNumber($data['subtotal']);

            $globalDiskonVal = $this->parseNumber($data['globalDiskon']['jumlah']);
            $globalDiskonAmt = ($data['globalDiskon']['jenis'] === 'nominal')
                ? $globalDiskonVal
                : ($subtotal * $globalDiskonVal / 100);

            $taxable = max(0, $subtotal - $globalDiskonAmt);
            $taxAmount = ($data['jenisPajak'] === 'PPN') ? $taxable * 0.11 : 0;

            $keterangan = $data['catatan'] ?? '';
            if (! empty($data['noRekening'])) {
                $keterangan .= ' [Transfer: '.$data['noRekening'].']';
            }

            $no_pembelian = ($data['nomorPembelian'] != '') ? $data['nomorPembelian'] : $this->generatePurchaseNumber();

            // Handle Update vs Create
            if ($this->purchaseId) {
                // UPDATE: Update existing records and handle stock changes
                $purchase = Purchase::find($this->purchaseId);

                // 1. Reverse Stock for existing details if previously completed
                if ($purchase->status === 'completed') {
                    foreach ($purchase->purchaseDetails as $oldDetail) {
                        Product::where('id', $oldDetail->product_id)->decrement('stok_aktual', $oldDetail->jumlah);
                    }
                }

                // 2. Delete related records that are safe to delete (Movements & Payments)
                // We delete StockMovements & BatchMovements (Incoming) because we will regenerate them.
                $stockMovements = \App\Models\StockMovement::where('reference_id', $purchase->id)
                    ->where('reference_type', 'purchase')
                    ->get();
                $stockMovementIds = $stockMovements->pluck('id');

                \App\Models\BatchMovement::whereIn('stock_movement_id', $stockMovementIds)->delete();
                \App\Models\StockMovement::whereIn('id', $stockMovementIds)->delete();

                // Delete Payments (Safe to recreate)
                \App\Models\Payment::where('transaction_id', $purchase->id)
                    ->where('jenis_transaksi', 'purchase')
                    ->delete();

                // 3. Update Purchase Header
                $purchase->update([
                    'no_pembelian' => $no_pembelian,
                    'tanggal_pembelian' => $data['tanggalPembelian'],
                    'supplier_id' => $data['supplier'],
                    'jenis_pembayaran' => $jenisPembayaran,
                    'subtotal' => $subtotal,
                    'jenis_diskon' => $data['globalDiskon']['jenis'],
                    'jumlah_diskon' => $globalDiskonVal,
                    'jenis_cashback' => $data['globalCashback']['jenis'],
                    'jumlah_cashback' => $this->parseNumber($data['globalCashback']['jumlah']),
                    'jumlah_pajak' => $taxAmount,
                    'total' => $total,
                    'dibayar' => $bayar,
                    'kembalian' => $this->parseNumber($data['kembalian']),
                    'jumlah_utang' => max(0, $total - $bayar),
                    'status' => $status,
                    'keterangan' => $keterangan,
                ]);

                // 4. Handle Details and Batches (The tricky part)
                $existingDetails = $purchase->purchaseDetails->keyBy('product_id');
                $processedDetailIds = [];

                $batchMovementData = [];
                $timestamp = now();

                foreach ($data['products'] as $item) {
                    $productId = $item['id'];
                    $newQty = $item['jumlah_beli'];
                    $newPrice = $this->parseNumber($item['harga_beli']);

                    if (isset($existingDetails[$productId])) {
                        // UPDATE Existing Detail
                        $detail = $existingDetails[$productId];
                        $oldQty = $detail->jumlah;

                        $detail->update([
                            'jumlah' => $newQty,
                            'harga_satuan' => $newPrice,
                            'jenis_diskon' => $item['diskon']['jenis'] ?? 'nominal',
                            'jumlah_diskon' => $this->parseNumber($item['diskon']['jumlah'] ?? 0),
                            'jenis_cashback' => $item['cashback']['jenis'] ?? 'nominal',
                            'jumlah_cashback' => $this->parseNumber($item['cashback']['jumlah'] ?? 0),
                            'subtotal' => $this->parseNumber($item['subtotal']),
                        ]);

                        $processedDetailIds[] = $detail->id;

                        // Update Batch
                        $batch = \App\Models\ProductBatch::where('purchase_detail_id', $detail->id)->first();
                        if ($batch) {
                            $qtyDiff = $newQty - $oldQty;
                            $batch->update([
                                'jumlah_awal' => $newQty,
                                'jumlah_saat_ini' => $batch->jumlah_saat_ini + $qtyDiff, // Adjust current stock by delta
                                'harga_satuan' => $newPrice,
                                'tanggal_pembelian' => $data['tanggalPembelian'],
                                'tanggal_kadaluarsa' => $item['tanggal_kadaluarsa'] ?? null,
                            ]);
                        }

                    } else {
                        // CREATE New Detail & Batch
                        $detail = $purchase->purchaseDetails()->create([
                            'product_id' => $item['id'],
                            'jumlah' => $newQty,
                            'harga_satuan' => $newPrice,
                            'jenis_diskon' => $item['diskon']['jenis'] ?? 'nominal',
                            'jumlah_diskon' => $this->parseNumber($item['diskon']['jumlah'] ?? 0),
                            'jenis_cashback' => $item['cashback']['jenis'] ?? 'nominal',
                            'jumlah_cashback' => $this->parseNumber($item['cashback']['jumlah'] ?? 0),
                            'subtotal' => $this->parseNumber($item['subtotal']),
                        ]);

                        $batch = \App\Models\ProductBatch::create([
                            'business_id' => $this->businessId,
                            'product_id' => $item['id'],
                            'purchase_detail_id' => $detail->id,
                            'no_batch' => 'BATCH-'.$purchase->id.'-'.time().'-'.$item['id'],
                            'tanggal_pembelian' => $data['tanggalPembelian'],
                            'harga_satuan' => $newPrice,
                            'jumlah_awal' => $newQty,
                            'jumlah_saat_ini' => $newQty,
                            'tanggal_kadaluarsa' => $item['tanggal_kadaluarsa'] ?? null,
                            'status' => 'ACTIVE',
                        ]);
                    }

                    if ($status === 'completed') {
                        // Re-create Stock Movement & Batch Movement (Incoming)
                        $stockMovement = \App\Models\StockMovement::create([
                            'business_id' => $this->businessId,
                            'product_id' => $item['id'],
                            'tanggal_perubahan_stok' => $data['tanggalPembelian'],
                            'jenis_perubahan' => 'purchase',
                            'jumlah_perubahan' => $newQty,
                            'reference_id' => $purchase->id,
                            'reference_type' => 'purchase',
                            'catatan' => 'Pembelian via PO '.($no_pembelian ?? ''),
                        ]);

                        $batchMovementData[] = [
                            'business_id' => $this->businessId,
                            'batch_id' => isset($batch) ? $batch->id : null, // Should exist
                            'stock_movement_id' => $stockMovement->id,
                            'tanggal_perubahan' => $data['tanggalPembelian'],
                            'jenis_transaksi' => 'purchase',
                            'transaction_detail_id' => $detail->id,
                            'jumlah' => $newQty,
                            'harga_satuan' => $newPrice,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];

                        // Increment Stock (We already decremented OLD stock at start, now we add NEW stock)
                        Product::where('id', $item['id'])->increment('stok_aktual', $newQty);
                    }
                }

                // 5. Delete details that are no longer in the list
                $detailsToDelete = array_diff($existingDetails->pluck('id')->toArray(), $processedDetailIds);
                if (! empty($detailsToDelete)) {
                    // Try to delete. If batch is used, this might fail unless we check first.
                    // Ideally we check if batch has other movements.
                    // But for now, let's try. if integrity error, it means we can't delete it.
                    // Constraint is on batch link.
                    // Check if associated batches have outgoing movements?
                    $batchesToDelete = \App\Models\ProductBatch::whereIn('purchase_detail_id', $detailsToDelete)->pluck('id');
                    $usedBatches = \App\Models\BatchMovement::whereIn('batch_id', $batchesToDelete)
                        ->where('jenis_transaksi', '!=', 'purchase') // Assume anything not purchase is usage
                        ->exists();

                    if ($usedBatches) {
                        throw new \Exception('Tidak dapat menghapus produk yang sudah terjual/digunakan (Batch Constraint).');
                    }

                    \App\Models\BatchMovement::whereIn('batch_id', $batchesToDelete)->delete();
                    \App\Models\ProductBatch::whereIn('purchase_detail_id', $detailsToDelete)->delete();
                    $purchase->purchaseDetails()->whereIn('id', $detailsToDelete)->delete();
                }

            } else {
                // CREATE
                $purchase = Purchase::create([
                    'no_pembelian' => $no_pembelian,
                    'tanggal_pembelian' => $data['tanggalPembelian'],
                    'business_id' => $this->businessId,
                    'supplier_id' => $data['supplier'],
                    'user_id' => auth()->id(),
                    'jenis_pembayaran' => $jenisPembayaran,
                    'subtotal' => $subtotal,
                    'jenis_diskon' => $data['globalDiskon']['jenis'],
                    'jumlah_diskon' => $globalDiskonVal,
                    'jenis_cashback' => $data['globalCashback']['jenis'],
                    'jumlah_cashback' => $this->parseNumber($data['globalCashback']['jumlah']),
                    'jumlah_pajak' => $taxAmount,
                    'total' => $total,
                    'dibayar' => $bayar,
                    'kembalian' => $this->parseNumber($data['kembalian']),
                    'jumlah_utang' => max(0, $total - $bayar),
                    'status' => $status,
                    'keterangan' => $keterangan,
                ]);

                // Standard Create Logic for Details
                $batchMovementData = [];
                $timestamp = now();

                foreach ($data['products'] as $item) {
                    // 1. Create Purchase Detail (We need ID for the batch linkage)
                    $detail = $purchase->purchaseDetails()->create([
                        'product_id' => $item['id'],
                        'jumlah' => $item['jumlah_beli'],
                        'harga_satuan' => $this->parseNumber($item['harga_beli']),
                        'jenis_diskon' => $item['diskon']['jenis'] ?? 'nominal',
                        'jumlah_diskon' => $this->parseNumber($item['diskon']['jumlah'] ?? 0),
                        'jenis_cashback' => $item['cashback']['jenis'] ?? 'nominal',
                        'jumlah_cashback' => $this->parseNumber($item['cashback']['jumlah'] ?? 0),
                        'subtotal' => $this->parseNumber($item['subtotal']),
                    ]);

                    // 2. Create Product Batch (One by one to get ID)
                    $batch = \App\Models\ProductBatch::create([
                        'business_id' => $this->businessId,
                        'product_id' => $item['id'],
                        'purchase_detail_id' => $detail->id,
                        'no_batch' => 'BATCH-'.$purchase->id.'-'.time().'-'.$item['id'],
                        'tanggal_pembelian' => $data['tanggalPembelian'],
                        'harga_satuan' => $this->parseNumber($item['harga_beli']),
                        'jumlah_awal' => $item['jumlah_beli'],
                        'jumlah_saat_ini' => $item['jumlah_beli'],
                        'tanggal_kadaluarsa' => $item['tanggal_kadaluarsa'] ?? null,
                        'status' => 'ACTIVE',
                    ]);

                    if ($status === 'completed') {
                        // 3. Create Stock Movement
                        $stockMovement = \App\Models\StockMovement::create([
                            'business_id' => $this->businessId,
                            'product_id' => $item['id'],
                            'tanggal_perubahan_stok' => $data['tanggalPembelian'],
                            'jenis_perubahan' => 'purchase',
                            'jumlah_perubahan' => $item['jumlah_beli'],
                            'reference_id' => $purchase->id,
                            'reference_type' => 'purchase',
                            'catatan' => 'Pembelian via PO '.($no_pembelian ?? ''),
                        ]);

                        // 4. Create Batch Movement (Linkage)
                        $batchMovementData[] = [
                            'business_id' => $this->businessId,
                            'batch_id' => $batch->id,
                            'stock_movement_id' => $stockMovement->id,
                            'tanggal_perubahan' => $data['tanggalPembelian'],
                            'jenis_transaksi' => 'purchase', // Incoming
                            'transaction_detail_id' => $detail->id,
                            'jumlah' => $item['jumlah_beli'], // Positive for incoming in context of batch size? Or just magnitude?
                            // Usually BatchMovement tracks "change". For initial creation, it's the full amount.
                            'harga_satuan' => $this->parseNumber($item['harga_beli']),
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];

                        // 5. Update Actual Stock
                        Product::where('id', $item['id'])->increment('stok_aktual', $item['jumlah_beli']);
                    }
                }
            } // END ELSE (CREATE)

            // Bulk Insert Batch Movements
            if (! empty($batchMovementData)) {
                \App\Models\BatchMovement::insert($batchMovementData);
            }

            // 5. Create Payment Records (Double-Entry Accounting) - OPTIMIZED
            $kodeRekening = PaymentUtil::ambilRekening('purchase', $data['jenisPembayaran'], $data['metodeBayar'], $data['noRekening']);

            // Calculate actual discount and cashback amounts (GLOBAL ONLY)
            $globalDiskonVal = $this->parseNumber($data['globalDiskon']['jumlah']);
            $globalDiskonAmt = ($data['globalDiskon']['jenis'] === 'nominal')
                ? $globalDiskonVal
                : ($subtotal * $globalDiskonVal / 100);

            $globalCashbackVal = $this->parseNumber($data['globalCashback']['jumlah']);
            $globalCashbackAmt = ($data['globalCashback']['jenis'] === 'nominal')
                ? $globalCashbackVal
                : ($subtotal * $globalCashbackVal / 100);

            $totalDiskonAll = $globalDiskonAmt;
            $totalCashbackAll = $globalCashbackAmt;

            // Collect all payment data for bulk insert
            $payments = [];
            $timestamp = now();

            // 5a. Main Purchase Payment (Inventory Acquisition)
            $payments[] = [
                'business_id' => $this->businessId,
                'user_id' => auth()->user()->id,
                'no_pembayaran' => $no_pembelian,
                'tanggal_pembayaran' => $data['tanggalPembelian'],
                'jenis_transaksi' => 'purchase',
                'transaction_id' => $purchase->id,
                'total_harga' => ($bayar >= $total) ? $total : $bayar,
                'metode_pembayaran' => $data['metodeBayar'],
                'no_referensi' => $data['noRekening'],
                'catatan' => 'Pembayaran Pembelian '.$no_pembelian,
                'rekening_debit' => $kodeRekening['purchase']['rekening_debit'],
                'rekening_kredit' => $kodeRekening['purchase']['rekening_kredit'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            // 5b. Purchase Discount Received (Reduces Cost)
            if ($totalDiskonAll > 0) {
                $payments[] = [
                    'business_id' => $this->businessId,
                    'user_id' => auth()->user()->id,
                    'no_pembayaran' => $no_pembelian.'-DISC',
                    'tanggal_pembayaran' => $data['tanggalPembelian'],
                    'jenis_transaksi' => 'purchase',
                    'transaction_id' => $purchase->id,
                    'total_harga' => $totalDiskonAll,
                    'metode_pembayaran' => $data['metodeBayar'],
                    'no_referensi' => $data['noRekening'],
                    'catatan' => 'Diskon Pembelian Diterima',
                    'rekening_debit' => $kodeRekening['purchase-diskon']['rekening_debit'],
                    'rekening_kredit' => $kodeRekening['purchase-diskon']['rekening_kredit'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            // 5c. Cashback Received (Other Income)
            if ($totalCashbackAll > 0) {
                $payments[] = [
                    'business_id' => $this->businessId,
                    'user_id' => auth()->user()->id,
                    'no_pembayaran' => $no_pembelian.'-CSHBK',
                    'tanggal_pembayaran' => $data['tanggalPembelian'],
                    'jenis_transaksi' => 'purchase',
                    'transaction_id' => $purchase->id,
                    'total_harga' => $totalCashbackAll,
                    'metode_pembayaran' => $data['metodeBayar'],
                    'no_referensi' => $data['noRekening'],
                    'catatan' => 'Cashback Pembelian Diterima',
                    'rekening_debit' => $kodeRekening['purchase-cashback']['rekening_debit'],
                    'rekening_kredit' => $kodeRekening['purchase-cashback']['rekening_kredit'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            // Bulk insert all payments (OPTIMIZATION: 3 queries → 1 query)
            if (! empty($payments)) {
                \App\Models\Payment::insert($payments);
            }

            DB::commit();
            $this->dispatch('alert', type: 'success', message: 'Transaksi berhasil disimpan');
            $this->dispatch('reset-form');
            $this->dispatch('redirect', url: '/pembelian/daftar', timeout: 1000);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    private function parseNumber($value)
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (empty($value)) {
            return 0;
        }

        $str = trim($value);

        // If it contains a comma, it's definitely Indonesian format (dot=thousands, comma=decimal)
        if (strpos($str, ',') !== false) {
            $clean = str_replace('.', '', $str);
            $clean = str_replace(',', '.', $clean);
            return (float) $clean;
        }

        // If it contains a dot:
        if (strpos($str, '.') !== false) {
            $lastDotIdx = strrpos($str, '.');
            $remainingLength = strlen($str) - $lastDotIdx - 1;
            
            // In Indonesian, thousands dots are ALWAYS followed by 3 digits.
            if ($remainingLength !== 3) {
                return (float) $str;
            }
            
            // If there's another dot, it's thousands
            if (strpos($str, '.') !== $lastDotIdx) {
                return (float) str_replace('.', '', $str);
            }
            
            // Ambiguous 1.250 -> Treat as 1250 for Indonesian apps
            return (float) str_replace('.', '', $str);
        }

        return (float) $str;
    }

    private function generatePurchaseNumber()
    {
        $date = date('Y/m');
        $today = date('Y-m-d');
        $count = Purchase::where('business_id', $this->businessId)
            ->whereDate('tanggal_pembelian', $today)
            ->count() + 1;

        return 'PO/'.$date.'/'.str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        return view('livewire.tambah-pembelian')->layout('layouts.app', ['title' => $this->title]);
    }
}
