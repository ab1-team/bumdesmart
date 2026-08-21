<?php

namespace App\Livewire;

use DB;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;

class TambahReturPenjualan extends Component
{
    public $title;

    public $businessId;

    public $sale = [];

    #[On('save-all')]
    #[Renderless]
    public function saveAll($data)
    {
        if (empty($data['retur_penjualan'])) {
            $this->dispatch('error', 'Tidak ada produk yang dipilih');

            return;
        }

        DB::beginTransaction();
        try {
            $salesReturn = \App\Models\SalesReturn::create([
                'business_id' => $this->businessId,
                'sale_id' => $data['sale_id'],
                'user_id' => auth()->user()->id,
                'no_return' => 'RETUR-JUAL-'.time(),
                'tanggal_return' => now(),
                'total_return' => $data['total_retur'],
                'alasan_return' => $data['alasan_retur'],
                'status' => 'approved',
            ]);

            foreach ($data['retur_penjualan'] as $retur) {
                $salesReturnDetail = \App\Models\SalesReturnDetail::create([
                    'sales_return_id' => $salesReturn->id,
                    'sale_detail_id' => $retur['sale_detail_id'],
                    'product_id' => $retur['product_id'],
                    'jumlah' => $retur['jumlah'],
                    'harga_satuan' => $retur['harga_satuan'],
                    'sub_total' => $retur['subtotal_retur'],
                ]);

                $stockMovement = \App\Models\StockMovement::create([
                    'business_id' => $this->businessId,
                    'product_id' => $retur['product_id'],
                    'tanggal_perubahan_stok' => now(),
                    'jenis_perubahan' => 'sales_retur',
                    'jumlah_perubahan' => $retur['jumlah'],
                    'reference_id' => $salesReturn->id,
                    'reference_type' => 'sales_return',
                    'catatan' => 'Retur Penjualan '.$data['sale_id'],
                ]);

                $amountToRestore = $retur['jumlah'];
                $usedBatches = \App\Models\BatchMovement::where('transaction_detail_id', $retur['sale_detail_id'])
                    ->where('jenis_transaksi', 'sale')
                    ->orderBy('id', 'desc')
                    ->get();

                if ($usedBatches->isNotEmpty()) {
                    foreach ($usedBatches as $usedBatch) {
                        if ($amountToRestore <= 0) {
                            break;
                        }

                        $restoreQty = $amountToRestore;
                        \App\Models\BatchMovement::create([
                            'business_id' => $this->businessId,
                            'batch_id' => $usedBatch->batch_id,
                            'stock_movement_id' => $stockMovement->id,
                            'tanggal_perubahan' => now(),
                            'jenis_transaksi' => 'sales_retur',
                            'transaction_detail_id' => $salesReturnDetail->id,
                            'jumlah' => $restoreQty,
                            'harga_satuan' => $usedBatch->harga_satuan,
                        ]);

                        \App\Models\ProductBatch::where('id', $usedBatch->batch_id)->increment('jumlah_saat_ini', $restoreQty);
                        \App\Models\ProductBatch::where('id', $usedBatch->batch_id)->update(['status' => 'ACTIVE']);

                        $amountToRestore -= $restoreQty;
                        break;
                    }
                } else {
                    $latestBatch = \App\Models\ProductBatch::where('product_id', $retur['product_id'])
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($latestBatch) {
                        \App\Models\BatchMovement::create([
                            'business_id' => $this->businessId,
                            'batch_id' => $latestBatch->id,
                            'stock_movement_id' => $stockMovement->id,
                            'tanggal_perubahan' => now(),
                            'jenis_transaksi' => 'sales_retur',
                            'transaction_detail_id' => $salesReturnDetail->id,
                            'jumlah' => $amountToRestore,
                            'harga_satuan' => $latestBatch->harga_satuan,
                        ]);
                        $latestBatch->increment('jumlah_saat_ini', $amountToRestore);
                    }
                }

                \App\Models\Product::where('id', $retur['product_id'])->increment('stok_aktual', $retur['jumlah']);
            }

            $totalRetur = $data['total_retur'];
            $sale = \App\Models\Sale::find($data['sale_id']);
            $piutangTersedia = $sale->jumlah_utang ?? 0;

            $potongPiutang = min($totalRetur, $piutangTersedia);
            $refundTunai = $totalRetur - $potongPiutang;

            if ($potongPiutang > 0) {
                \App\Models\Payment::create([
                    'business_id' => $this->businessId,
                    'user_id' => auth()->user()->id,
                    'no_pembayaran' => $salesReturn->no_return . '-P',
                    'tanggal_pembayaran' => $salesReturn->tanggal_return,
                    'jenis_transaksi' => 'sales_return',
                    'transaction_id' => $salesReturn->id,
                    'total_harga' => $potongPiutang,
                    'metode_pembayaran' => 'potong_piutang',
                    'no_referensi' => $sale->no_invoice,
                    'catatan' => 'Potong Piutang (Retur Penjualan ' . $salesReturn->no_return . ')',
                    'rekening_debit' => '4.1.01.03', // Retur Penjualan
                    'rekening_kredit' => '1.1.04.01', // Piutang Dagang
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $sale->decrement('jumlah_utang', $potongPiutang);
            }

            if ($refundTunai > 0) {
                \App\Models\Payment::create([
                    'business_id' => $this->businessId,
                    'user_id' => auth()->user()->id,
                    'no_pembayaran' => $salesReturn->no_return . '-R',
                    'tanggal_pembayaran' => $salesReturn->tanggal_return,
                    'jenis_transaksi' => 'sales_return',
                    'transaction_id' => $salesReturn->id,
                    'total_harga' => $refundTunai,
                    'metode_pembayaran' => 'tunai',
                    'no_referensi' => $sale->no_invoice,
                    'catatan' => 'Refund Tunai (Retur Penjualan ' . $salesReturn->no_return . ')',
                    'rekening_debit' => '4.1.01.03', // Retur Penjualan
                    'rekening_kredit' => '1.1.01.01', // Buku Kas Umum
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($sale->jumlah_utang <= 0) {
                $sale->update(['status' => 'completed']);
            }

            DB::commit();
            $this->dispatch('alert', type: 'success', message: 'Retur penjualan berhasil disimpan');
            $this->dispatch('reset-form');
            $this->dispatch('redirect', url: '/penjualan/daftar', timeout: 1000);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function mount($id = null)
    {
        $this->title = 'Tambah Retur Penjualan';
        $this->businessId = auth()->user()->business_id;

        $this->sale = \App\Models\Sale::with([
            'customer',
            'saleDetails.product',
            'saleDetails.salesReturnDetail',
        ])->find($id);
    }

    public function render()
    {
        return view('livewire.tambah-retur-penjualan', [
            'sale' => $this->sale,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
