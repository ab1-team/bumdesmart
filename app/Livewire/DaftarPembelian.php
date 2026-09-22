<?php

namespace App\Livewire;

use App\Traits\WithTable;
use App\Utils\PaymentUtil;
use App\Utils\TableUtil;
use DB;
use Livewire\Attributes\On;
use Livewire\Component;

class DaftarPembelian extends Component
{
    use WithTable;

    public $title;

    public $businessId;

    public $detailPurchase = [];
    public $bankAccounts = [];
    public $defaultTransferAccount = null;
    public $defaultQrisAccount = null;

    public function mount()
    {
        $this->businessId = auth()->user()->business_id;
        $this->bankAccounts = \App\Models\Account::where('business_id', $this->businessId)
            ->whereNotNull('no_rek_bank')
            ->get();
            
        $this->defaultTransferAccount = $this->bankAccounts->where('is_default_transfer', true)->first()?->no_rek_bank;
        $this->defaultQrisAccount = $this->bankAccounts->where('is_default_qris', true)->first()?->no_rek_bank;
    }

    // Payment Form Properties
    public $nomorPembayaran;

    public $tanggalPembayaran;

    public $sudahDibayar = 0;

    public $jumlahPembayaran = 0;

    public $keterangan;

    public $kembalian = 0;

    public $sisaTagihan = 0;

    public $metodePembayaran = 'cash';

    public $noRekening = '';

    public function detailPembelian($id)
    {
        $purchase = \App\Models\Purchase::with([
            'supplier',
            'business',
            'purchaseDetails.product',
        ])->where('id', $id)->first();

        $this->detailPurchase = $purchase;

        $this->dispatch('show-modal', modalId: 'detailPembelianModal');
    }

    public function lihatPembayaran($id)
    {
        // Load purchase with all payments related to this purchase transaction.
        // Exclude only accounting entries (piutang, diskon, cashback) - these are not actual cash payments.
        $purchase = \App\Models\Purchase::with([
            'payments' => function ($query) {
                $query->where('jenis_transaksi', 'purchase')
                    ->whereNotIn('metode_pembayaran', ['piutang', 'diskon', 'cashback'])
                    ->orderBy('tanggal_pembayaran', 'desc')
                    ->orderBy('id', 'desc');
            },
        ])->where('id', $id)->first();

        $this->detailPurchase = $purchase;

        $this->dispatch('show-modal', modalId: 'detailPembayaranModal');
    }

    #[On('deletePayment')]
    public function deletePayment($id)
    {
        $payment = \App\Models\Payment::where('id', $id)->first();
        $purchaseId = $payment->transaction_id;

        $sisaBayar = \App\Models\Payment::where('transaction_id', $purchaseId)
            ->where('id', '!=', $id)
            ->whereNotIn('metode_pembayaran', ['piutang', 'diskon', 'cashback'])
            ->sum('total_harga');

        $payment->delete();

        $totalPurchase = \App\Models\Purchase::where('id', $purchaseId)->value('total');
        $newUtang = $totalPurchase - $sisaBayar;

        // Determine status:
        // - completed if fully paid
        // - partial if there are still payments but some debt remains
        // - utang if no payments at all (or debt remains and zero paid)
        $status = 'utang';
        if ($sisaBayar >= $totalPurchase) {
            $status = 'completed';
        } elseif ($sisaBayar > 0 && $sisaBayar < $totalPurchase) {
            $status = 'partial';
        }

        \App\Models\Purchase::where('id', $purchaseId)->update([
            'dibayar' => $sisaBayar,
            'jumlah_utang' => max(0, $newUtang),
            'kembalian' => 0,
            'status' => $status,
        ]);

        $this->detailPurchase = \App\Models\Purchase::with('payments')->where('id', $purchaseId)->first();

        $this->dispatch('hide-modal', modalId: 'detailPembayaranModal');
        $this->dispatch('alert', type: 'success', message: 'Pembayaran berhasil dihapus');
        $this->dispatch('$refresh');
    }

    public function tambahPembayaran($id)
    {
        $purchase = \App\Models\Purchase::with('payments')->where('id', $id)->first();
        $this->detailPurchase = $purchase;

        // Reset form
        $this->nomorPembayaran = null; // Auto-generate if empty
        $this->tanggalPembayaran = date('Y-m-d');
        $this->keterangan = 'Pembayaran Utang Pembelian PO ' . $purchase->no_pembelian;
        $this->jumlahPembayaran = 0;
        $this->kembalian = 0;
        $this->metodePembayaran = 'cash';
        $this->noRekening = '';

        // Calculate paid and remaining (exclude piutang, diskon, cashback - these are accounting entries)
        $actualPayments = $purchase->payments->whereNotIn('metode_pembayaran', ['piutang', 'diskon', 'cashback']);
        $this->sudahDibayar = $actualPayments->sum('total_harga');
        $this->sisaTagihan = $purchase->total - $this->sudahDibayar;

        $this->dispatch('show-modal', modalId: 'tambahPembayaranModal');
    }

    public function simpanPembayaran()
    {
        $this->validate([
            'jumlahPembayaran' => 'required|numeric|min:0.01',
            'tanggalPembayaran' => 'required|date',
        ]);

        // Parse Indonesian-formatted number ("824.596,12" -> 824596.12)
        $jumlahBayar = \App\Utils\NumberUtil::parse($this->jumlahPembayaran);

        // Limit payment amount to remaining debt
        $jumlahBayarInput = $jumlahBayar;
        $kembalian = 0;

        if ($jumlahBayar > $this->sisaTagihan) {
            $jumlahBayar = $this->sisaTagihan;
            $kembalian = $jumlahBayarInput - $this->sisaTagihan;
        }

        // Auto generate number if empty
        if (empty($this->nomorPembayaran)) {
            $this->nomorPembayaran = 'PAY-'.date('YmdHis');
        }

        $rekening = PaymentUtil::ambilRekening('purchase', 'cash', $this->metodePembayaran, $this->noRekening);

        $payment = \App\Models\Payment::create([
            'business_id' => $this->businessId,
            'user_id' => auth()->user()->id,
            'no_pembayaran' => $this->nomorPembayaran,
            'tanggal_pembayaran' => $this->tanggalPembayaran,
            'jenis_transaksi' => 'purchase',
            'transaction_id' => $this->detailPurchase->id,
            'total_harga' => $jumlahBayar,
            'metode_pembayaran' => $this->metodePembayaran,
            'no_referensi' => $this->noRekening ?: null,
            'catatan' => $this->keterangan,
            'rekening_debit' => '2.1.01.01', // Hutang
            'rekening_kredit' => $rekening['purchase']['rekening_kredit'], // Kas/Bank
        ]);

        $actualPayments = \App\Models\Payment::where('transaction_id', $this->detailPurchase->id)
            ->whereNotIn('metode_pembayaran', ['piutang', 'diskon', 'cashback'])
            ->get();
        $totalDibayar = $this->sudahDibayar + $jumlahBayar;
        // Status logic:
        // - completed if fully paid
        // - partial if partially paid (some payment remains but debt also remains)
        // - utang if no payments at all
        $status = 'utang';
        if ($totalDibayar >= $this->detailPurchase->total) {
            $status = 'completed';
        } elseif ($totalDibayar > 0 && $totalDibayar < $this->detailPurchase->total) {
            $status = 'partial';
        }

        \App\Models\Purchase::where('id', $this->detailPurchase->id)->update([
            'status' => $status,
            'dibayar' => $totalDibayar,
            'jumlah_utang' => max(0, $this->detailPurchase->total - $totalDibayar),
            'kembalian' => 0,
        ]);

        $this->detailPurchase = \App\Models\Purchase::with('payments')->where('id', $this->detailPurchase->id)->first();

        $this->dispatch('hide-modal', modalId: 'tambahPembayaranModal');
        $this->dispatch('alert', type: 'success', message: 'Pembayaran berhasil disimpan');
        $this->dispatch('$refresh');
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        $purchase = \App\Models\Purchase::with([
            'payments',
            'purchaseDetails.productBatch',
            'stockMovement.batchMovements',
        ])->where('id', $id)->first();

        DB::beginTransaction();
        try {
            $updateProducts = [];
            $deleteProductBatchs = [];

            foreach ($purchase->purchaseDetails as $purchaseDetail) {
                if ($purchaseDetail->productBatch) {
                    $updateProducts[$purchaseDetail->productBatch->product_id] = $purchaseDetail->productBatch->jumlah_saat_ini;
                    $deleteProductBatchs[] = $purchaseDetail->productBatch->id;
                }
            }

            $deleteBatchMovements = [];
            foreach ($purchase->stockMovement as $stockMovement) {
                foreach ($stockMovement->batchMovements as $batchMovements) {
                    $deleteBatchMovements[] = $batchMovements->id;
                }
            }

            foreach ($updateProducts as $productId => $jumlah) {
                \App\Models\Product::where('id', $productId)
                    ->decrement('stok_aktual', $jumlah);
            }

            // NEW: Check if batches are used before deleting
            $usedBatches = \App\Models\BatchMovement::whereIn('batch_id', $deleteProductBatchs)
                ->where('jenis_transaksi', '!=', 'purchase')
                ->exists();

            if ($usedBatches) {
                throw new \Exception('Tidak dapat menghapus pembelian karena produk dalam batch ini sudah terjual atau digunakan.');
            }

            // Delete ALL batch movements associated with these batches (safe because we checked usage above)
            // This prevents FK errors if the relation traversal missed some
            \App\Models\BatchMovement::whereIn('batch_id', $deleteProductBatchs)->delete();

            // \App\Models\BatchMovement::whereIn('id', $deleteBatchMovements)->delete(); // Replaced by strict batch_id delete
            \App\Models\ProductBatch::whereIn('id', $deleteProductBatchs)->delete();

            $purchase->purchaseDetails()->delete();
            $purchase->stockMovement()->delete();
            $purchase->payments()->delete();
            $purchase->delete();

            DB::commit();
            $this->dispatch('alert', type: 'success', message: 'Pembelian berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function render()
    {
        $this->title = 'Daftar Pembelian';
        $this->businessId = auth()->user()->business_id;

        $query = \App\Models\Purchase::where('business_id', $this->businessId)->with([
            'supplier',
            'purchaseReturn',
            'payments' => function ($query) {
                $query->where(function ($query) {
                    $query->where('rekening_debit', '1.1.03.01')->where('rekening_kredit', 'like', '1.1.01%');
                })->orWhere(function ($query) {
                    $query->where('rekening_debit', '2.1.01.01')->where('rekening_kredit', 'like', '1.1.01%');
                });
            },
        ]);

        $headers = [
            TableUtil::setTableHeader('id', '#', false, false),
            TableUtil::setTableHeader('no_pembelian', 'No. Pembelian', true, true),
            TableUtil::setTableHeader('tanggal_pembelian', 'Tanggal Pembelian', true, true),
            TableUtil::setTableHeader('supplier.nama_supplier', 'Supplier', true, true),
            TableUtil::setTableHeader('status', 'Status', true, true),
            TableUtil::setTableHeader('total', 'Total Pembelian', false, false),
            TableUtil::setTableHeader('id', 'Total Pembayaran', false, false),
            TableUtil::setTableHeader('id', 'Sisa Pembayaran', false, false),
            TableUtil::setTableHeader('aksi', 'Aksi', false, false),
        ];

        $purchases = TableUtil::paginate($this, $query, $headers, 10);

        return view('livewire.daftar-pembelian', [
            'purchases' => $purchases,
            'headers' => $headers,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
