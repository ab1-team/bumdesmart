<?php

namespace App\Livewire;

use DB;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;

class TambahReturPembelian extends Component
{
    public $title;

    public $businessId;

    public $purchase = [];

    #[On('save-all')]
    #[Renderless]
    public function saveAll($data)
    {
        if (empty($data['retur_pembelian'])) {
            $this->dispatch('error', 'Tidak ada produk yang dipilih');

            return;
        }

        DB::beginTransaction();
        try {
            $purchaseRetur = \App\Models\PurchasesReturn::create([
                'business_id' => $this->businessId,
                'purchase_id' => $data['purchase_id'],
                'user_id' => auth()->user()->id,
                'no_return' => 'RETUR-'.time(),
                'tanggal_return' => now(),
                'total_return' => $data['total_retur'],
                'alasan_return' => $data['alasan_retur'],
                'status' => 'approved',
            ]);

            foreach ($data['retur_pembelian'] as $retur) {
                $purchaseReturnDetail = \App\Models\PurchasesReturnDetail::create([
                    'purchases_return_id' => $purchaseRetur->id,
                    'purchase_detail_id' => $retur['purchase_detail_id'],
                    'product_id' => $retur['product_id'],
                    'jumlah' => $retur['jumlah'],
                    'harga_satuan' => $retur['harga_satuan'],
                    'sub_total' => $retur['subtotal_retur'],
                ]);

                $stockMovement = \App\Models\StockMovement::create([
                    'business_id' => $this->businessId,
                    'product_id' => $retur['product_id'],
                    'tanggal_perubahan_stok' => now(),
                    'jenis_perubahan' => 'purchase_retur',
                    'jumlah_perubahan' => $retur['jumlah'] * -1,
                    'reference_id' => $purchaseRetur->id,
                    'reference_type' => 'purchases_return',
                    'catatan' => 'Retur pembelian',
                ]);

                $batchMovement = \App\Models\BatchMovement::create([
                    'business_id' => $this->businessId,
                    'batch_id' => $retur['product_batch_id'],
                    'stock_movement_id' => $stockMovement->id,
                    'tanggal_perubahan' => now(),
                    'jenis_transaksi' => 'purchase_retur',
                    'transaction_detail_id' => $purchaseReturnDetail->id,
                    'jumlah' => $retur['jumlah'],
                    'harga_satuan' => $retur['harga_satuan'],
                ]);

                \App\Models\ProductBatch::where('id', $retur['product_batch_id'])->decrement('jumlah_saat_ini', $retur['jumlah']);
                \App\Models\Product::where('id', $retur['product_id'])->decrement('stok_aktual', $retur['jumlah']);
            }

            $totalRetur = $data['total_retur'];
            $purchase = \App\Models\Purchase::find($data['purchase_id']);
            $hutangTersedia = $purchase->jumlah_utang ?? 0;

            $potongHutang = min($totalRetur, $hutangTersedia);
            $refundTunai = $totalRetur - $potongHutang;

            if ($potongHutang > 0) {
                \App\Models\Payment::create([
                    'business_id' => $this->businessId,
                    'user_id' => auth()->user()->id,
                    'no_pembayaran' => $purchaseRetur->no_return . '-P',
                    'tanggal_pembayaran' => $purchaseRetur->tanggal_return,
                    'jenis_transaksi' => 'purchase_return',
                    'transaction_id' => $purchaseRetur->id,
                    'total_harga' => $potongHutang,
                    'metode_pembayaran' => 'potong_hutang',
                    'no_referensi' => $purchase->no_pembelian,
                    'catatan' => 'Potong Hutang (Retur Pembelian ' . $purchaseRetur->no_return . ')',
                    'rekening_debit' => '2.1.01.01', // Utang Pembelian (Liability)
                    'rekening_kredit' => '1.1.03.01', // Persediaan (Asset)
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $purchase->decrement('jumlah_utang', $potongHutang);
            }

            if ($refundTunai > 0) {
                \App\Models\Payment::create([
                    'business_id' => $this->businessId,
                    'user_id' => auth()->user()->id,
                    'no_pembayaran' => $purchaseRetur->no_return . '-R',
                    'tanggal_pembayaran' => $purchaseRetur->tanggal_return,
                    'jenis_transaksi' => 'purchase_return',
                    'transaction_id' => $purchaseRetur->id,
                    'total_harga' => $refundTunai,
                    'metode_pembayaran' => 'tunai',
                    'no_referensi' => $purchase->no_pembelian,
                    'catatan' => 'Refund Tunai (Retur Pembelian ' . $purchaseRetur->no_return . ')',
                    'rekening_debit' => '1.1.01.01', // Buku Kas Umum
                    'rekening_kredit' => '1.1.03.01', // Persediaan (Asset)
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($purchase->jumlah_utang <= 0) {
                $purchase->update(['status' => 'completed']);
            }

            DB::commit();
            $this->dispatch('alert', type: 'success', message: 'Retur pembelian berhasil disimpan');
            $this->dispatch('reset-form');
            $this->dispatch('redirect', url: '/pembelian/daftar-retur', timeout: 1000);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function mount($id = null)
    {
        $this->title = 'Tambah Retur Pembelian';
        $this->businessId = auth()->user()->business_id;

        $this->purchase = \App\Models\Purchase::with([
            'supplier',
            'purchaseDetails.product',
            'purchaseDetails.productBatch',
            'purchaseDetails.purchasesReturnDetail',
        ])->find($id);
    }

    public function render()
    {
        return view('livewire.tambah-retur-pembelian', [
            'purchase' => $this->purchase,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
