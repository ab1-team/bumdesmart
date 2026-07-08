<?php

namespace App\Livewire;

use App\Traits\WithTable;
use App\Utils\TableUtil;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class DaftarPenjualan extends Component
{
    use WithTable;

    public $title;

    public $businessId;

    public function mount()
    {
        $this->businessId = auth()->user()->business_id;
        $this->bankAccounts = \App\Models\Account::where('business_id', $this->businessId)
            ->whereNotNull('no_rek_bank')
            ->get();

        $this->defaultTransferAccount = $this->bankAccounts->where('is_default_transfer', true)->first()?->no_rek_bank;
        $this->defaultQrisAccount = $this->bankAccounts->where('is_default_qris', true)->first()?->no_rek_bank;
    }

    public $detailSale;

    // Payment Form Properties
    public $nomorPembayaran;

    public $tanggalPembayaran;

    public $sudahDibayar = 0;

    public $jumlahPembayaran = 0;

    public $keterangan;

    public $kembalian = 0;

    public $sisaTagihan = 0;

    public $paymentList = [];

    public $metodePembayaran = 'cash';

    public $noRekening = '';

    public $bankAccounts = [];

    public $defaultTransferAccount = null;

    public $defaultQrisAccount = null;

    public function detailPenjualan($id)
    {
        $sale = \App\Models\Sale::with([
            'customer',
            'business',
            'saleDetails.product',
        ])->where('id', $id)->first();

        $this->detailSale = $sale;

        $this->dispatch('show-modal', modalId: 'detailPenjualanModal');
    }

    public function lihatPembayaran($id)
    {
        $sale = \App\Models\Sale::with([
            'payments' => function ($query) {
                $query->where('jenis_transaksi', 'sale')
                    ->orderBy('id', 'desc');
            },
        ])->where('id', $id)->first();

        $this->detailSale = $sale;

        // Group payments to merge HPP & Profit
        $grouped = [];
        foreach ($sale->payments as $payment) {
            $baseForGroup = $payment->no_pembayaran;
            $isSplit = false;

            // Check for split pattern
            if (str_ends_with($payment->no_pembayaran, '-HPP')) {
                $baseForGroup = substr($payment->no_pembayaran, 0, -4);
                $isSplit = true;
            } elseif (str_ends_with($payment->no_pembayaran, '-PROFIT')) {
                $baseForGroup = substr($payment->no_pembayaran, 0, -7);
                $isSplit = true;
            }

            if (! isset($grouped[$baseForGroup])) {
                $grouped[$baseForGroup] = [
                    'id' => $payment->id, // Use one ID as reference
                    'tanggal_pembayaran' => $payment->tanggal_pembayaran,
                    'metode_pembayaran' => $payment->metode_pembayaran,
                    'no_referensi' => $payment->no_referensi,
                    'total_harga' => 0,
                    'original_ids' => [],
                    'is_split' => $isSplit,
                ];
            }

            $grouped[$baseForGroup]['total_harga'] += $payment->total_harga;
            $grouped[$baseForGroup]['original_ids'][] = $payment->id;

            // Prefer 'PROFIT' or 'tunai' generic metadata over 'internal' HPP metadata if merging
            if ($payment->metode_pembayaran !== 'internal') {
                $grouped[$baseForGroup]['metode_pembayaran'] = $payment->metode_pembayaran;
            }
        }

        $this->paymentList = array_values($grouped); // Convert to indexed array

        $this->dispatch('show-modal', modalId: 'detailPembayaranModal');
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        $sale = \App\Models\Sale::with([
            'payments',
            'stockMovements.batchMovements',
        ])->where('id', $id)->first();

        if (! $sale) {
            return;
        }

        DB::beginTransaction();
        try {
            $updateProducts = [];
            $updateProductBatches = [];
            $batchMovementIds = [];
            $stockMovementIds = [];

            // Collect data untuk update & delete
            // Iterate StockMovements directly (linked to Sale via reference_id)
            foreach ($sale->stockMovements as $stockMovement) {
                $stockMovementIds[] = $stockMovement->id;

                foreach ($stockMovement->batchMovements as $batchMovement) {
                    $batchMovementIds[] = $batchMovement->id;

                    // Aggregate batch updates
                    if ($batchMovement->batch_id) {
                        $updateProductBatches[$batchMovement->batch_id]
                            = ($updateProductBatches[$batchMovement->batch_id] ?? 0)
                            + $batchMovement->jumlah;
                    }

                    // Aggregate product updates
                    $updateProducts[$stockMovement->product_id]
                        = ($updateProducts[$stockMovement->product_id] ?? 0)
                        + $batchMovement->jumlah;
                }
            }

            // ✅ BULK DELETE - 1 query saja
            if (! empty($batchMovementIds)) {
                DB::table('batch_movements')->whereIn('id', $batchMovementIds)->delete();
            }
            if (! empty($stockMovementIds)) {
                DB::table('stock_movements')->whereIn('id', $stockMovementIds)->delete();
            }

            // ✅ BULK UPDATE menggunakan CASE WHEN - 1 query per tabel
            if (! empty($updateProductBatches)) {
                $cases = [];
                $ids = [];
                foreach ($updateProductBatches as $batchId => $qty) {
                    $cases[] = "WHEN id = {$batchId} THEN jumlah_saat_ini + {$qty}";
                    $ids[] = $batchId;
                }
                $casesSql = implode(' ', $cases);
                $idsSql = implode(',', $ids);

                DB::statement("
                    UPDATE product_batches 
                    SET jumlah_saat_ini = CASE {$casesSql} END
                    WHERE id IN ({$idsSql})
                ");
            }

            if (! empty($updateProducts)) {
                $cases = [];
                $ids = [];
                foreach ($updateProducts as $productId => $qty) {
                    $cases[] = "WHEN id = {$productId} THEN stok_aktual + {$qty}";
                    $ids[] = $productId;
                }
                $casesSql = implode(' ', $cases);
                $idsSql = implode(',', $ids);

                DB::statement("
                    UPDATE products 
                    SET stok_aktual = CASE {$casesSql} END
                    WHERE id IN ({$idsSql})
                ");
            }

            // Delete related records
            $sale->saleDetails()->delete();
            $sale->payments()->delete();
            $sale->delete();

            DB::commit();
            $this->dispatch('alert', type: 'success', message: 'Penjualan berhasil dihapus');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: 'Gagal menghapus: '.$e->getMessage());
            \Log::error('Delete sale error: '.$e->getMessage());
        }
    }

    #[On('deletePayment')]
    public function deletePayment($id)
    {
        $payment = \App\Models\Payment::find($id);

        if (! $payment) {
            $this->dispatch('alert', type: 'error', message: 'Pembayaran tidak ditemukan');
            return;
        }

        $sale = \App\Models\Sale::find($payment->transaction_id);

        DB::beginTransaction();
        try {
            // Check if this is part of a split (HPP/PROFIT)
            $toDeleteIds = [$id];
            $baseNo = $payment->no_pembayaran;

            if (str_ends_with($baseNo, '-HPP')) {
                $mainBase = substr($baseNo, 0, -4);
                $partner = \App\Models\Payment::where('transaction_id', $sale->id)
                    ->where('no_pembayaran', $mainBase.'-PROFIT')
                    ->first();
                if ($partner) {
                    $toDeleteIds[] = $partner->id;
                }
            } elseif (str_ends_with($baseNo, '-PROFIT')) {
                $mainBase = substr($baseNo, 0, -7);
                $partner = \App\Models\Payment::where('transaction_id', $sale->id)
                    ->where('no_pembayaran', $mainBase.'-HPP')
                    ->first();
                if ($partner) {
                    $toDeleteIds[] = $partner->id;
                }
            }

            // Snapshot payment metadata before deletion (for potential piutang restoration)
            $deletedMeta = [
                'no_pembayaran' => $payment->no_pembayaran,
                'tanggal_pembayaran' => $payment->tanggal_pembayaran,
                'metode_pembayaran' => $payment->metode_pembayaran,
            ];

            // Calculate Total Value to deduct
            $totalDeletedValue = \App\Models\Payment::whereIn('id', $toDeleteIds)->sum('total_harga');

            \App\Models\Payment::whereIn('id', $toDeleteIds)->delete();

            // Refresh sale from DB
            $sale->refresh();

            // Recompute paid amount: subtract the deleted payment value from current paid
            $newPaid = max(0, $sale->dibayar - $totalDeletedValue);
            $newKembalian = max(0, $sale->kembalian - $totalDeletedValue);
            $newJumlahUtang = max(0, $sale->total - $newPaid);

            $status = 'partial';
            if ($newJumlahUtang <= 0) {
                $status = 'completed';
            } elseif ($newPaid <= 0) {
                $status = 'pending';
            }

            // Flip jenis_pembayaran back to 'credit' whenever invoice is no longer fully paid
            $jenisPembayaran = $sale->jenis_pembayaran;
            if ($newJumlahUtang > 0 && $jenisPembayaran !== 'credit') {
                $jenisPembayaran = 'credit';
            } elseif ($newJumlahUtang <= 0 && $jenisPembayaran === 'credit') {
                $jenisPembayaran = 'cash';
            }

            $sale->update([
                'dibayar' => $newPaid,
                'kembalian' => $newKembalian,
                'jumlah_utang' => $newJumlahUtang,
                'status' => $status,
                'jenis_pembayaran' => $jenisPembayaran,
            ]);

            // If the sale now has outstanding piutang, ensure the accounting piutang
            // entry (no_pembayaran = {invoice}-CR) is present. If missing or insufficient,
            // create / top-up a piutang row mirroring TambahPenjualan::processPayments (lines 678-697).
            if ($newJumlahUtang > 0) {
                $piutangNo = $sale->no_invoice.'-CR';
                $existingPiutang = \App\Models\Payment::where('transaction_id', $sale->id)
                    ->where('jenis_transaksi', 'sale')
                    ->where('metode_pembayaran', 'piutang')
                    ->where('no_pembayaran', $piutangNo)
                    ->first();

                // Total piutang currently recorded on the books for this sale
                $currentPiutangTotal = \App\Models\Payment::where('transaction_id', $sale->id)
                    ->where('jenis_transaksi', 'sale')
                    ->where('metode_pembayaran', 'piutang')
                    ->sum('total_harga');

                $delta = $newJumlahUtang - $currentPiutangTotal;

                if ($delta > 0) {
                    if ($existingPiutang) {
                        $existingPiutang->update([
                            'total_harga' => $existingPiutang->total_harga + $delta,
                        ]);
                    } else {
                        \App\Models\Payment::create([
                            'business_id' => $this->businessId,
                            'user_id' => auth()->user()->id,
                            'no_pembayaran' => $piutangNo,
                            'tanggal_pembayaran' => $deletedMeta['tanggal_pembayaran'] ?? now()->toDateString(),
                            'jenis_transaksi' => 'sale',
                            'transaction_id' => $sale->id,
                            'total_harga' => $delta,
                            'metode_pembayaran' => 'piutang',
                            'no_referensi' => null,
                            'catatan' => 'Piutang Penjualan '.$sale->no_invoice,
                            'rekening_debit' => '1.1.04.01', // Piutang
                            'rekening_kredit' => '4.1.01.01', // Pendapatan
                        ]);
                    }
                } elseif ($delta < 0 && $existingPiutang) {
                    // Piutang entries exceed outstanding amount (e.g. partial overpay scenario)
                    $existingPiutang->update([
                        'total_harga' => max(0, $existingPiutang->total_harga + $delta),
                    ]);
                }
            } else {
                // Fully paid: collapse any piutang accounting rows for this sale
                \App\Models\Payment::where('transaction_id', $sale->id)
                    ->where('jenis_transaksi', 'sale')
                    ->where('metode_pembayaran', 'piutang')
                    ->delete();
            }

            DB::commit();
            $this->dispatch('alert', type: 'success', message: 'Pembayaran berhasil dihapus dan piutang dikembalikan');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: 'Gagal menghapus pembayaran: '.$e->getMessage());
            \Log::error('Delete payment error: '.$e->getMessage());
            return;
        }

        // Refresh List (Re-run logic)
        $this->lihatPembayaran($sale->id);

        $this->dispatch('hide-modal', modalId: 'detailPembayaranModal');
    }

    public function tambahPembayaran($id)
    {
        $sale = \App\Models\Sale::with('payments')->where('id', $id)->first();
        $this->detailSale = $sale;

        // Reset form
        $this->nomorPembayaran = null;
        $this->tanggalPembayaran = date('Y-m-d');
        $this->keterangan = 'Pembayaran Utang Penjualan ' . $sale->no_invoice;
        $this->jumlahPembayaran = 0;
        $this->kembalian = 0;
        $this->metodePembayaran = 'cash';
        $this->noRekening = '';

        // Calculate
        $this->sudahDibayar = $sale->dibayar;
        $this->sisaTagihan = max(0, $sale->total - $this->sudahDibayar);

        $this->dispatch('show-modal', modalId: 'tambahPembayaranModal');
    }

    public function simpanPembayaran()
    {
        $this->validate([
            'jumlahPembayaran' => 'required|numeric|min:1',
            'tanggalPembayaran' => 'required|date',
        ]);

        $jumlahBayar = (float) str_replace(',', '', $this->jumlahPembayaran);

        $kembalian = 0;
        if ($jumlahBayar > $this->sisaTagihan) {
            $kembalian = $jumlahBayar - $this->sisaTagihan;
            $jumlahBayar = $this->sisaTagihan;
        }
        if (empty($this->nomorPembayaran)) {
            $this->nomorPembayaran = 'PAY-SALE-'.date('YmdHis');
        }

        $kodeRekening = \App\Utils\PaymentUtil::ambilRekening('sales', 'cash', $this->metodePembayaran, $this->noRekening);
        $rekeningKas = $kodeRekening['sales']['rekening_debit'];

        // Calculate Splits
        $totalHpp = $this->detailSale->saleDetails->sum('hpp');

        $alreadyPaid = $this->detailSale->dibayar;
        $remainingHpp = max(0, $totalHpp - $alreadyPaid);

        $payForHpp = 0;
        $payForProfit = 0;

        if ($jumlahBayar <= $remainingHpp) {
            $payForHpp = $jumlahBayar;
        } else {
            $payForHpp = $remainingHpp;
            $payForProfit = $jumlahBayar - $remainingHpp;
        }

        $timestamp = now();

        // 1. Payment for Piutang (Clearing Receivable)
        \App\Models\Payment::create([
            'business_id' => $this->businessId,
            'user_id' => auth()->user()->id,
            'no_pembayaran' => $this->nomorPembayaran,
            'tanggal_pembayaran' => $this->tanggalPembayaran,
            'jenis_transaksi' => 'sale',
            'transaction_id' => $this->detailSale->id,
            'total_harga' => $jumlahBayar,
            'metode_pembayaran' => $this->metodePembayaran,
            'no_referensi' => $this->noRekening ?: null,
            'catatan' => $this->keterangan ?: 'Pembayaran Piutang Penjualan',
            'rekening_debit' => $rekeningKas,
            'rekening_kredit' => '1.1.04.01', // Piutang
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        // Update Sale
        $totalDibayar = $this->sudahDibayar + $jumlahBayar;
        $status = 'partial';
        if ($totalDibayar >= $this->detailSale->total) {
            $status = 'completed';
        }

        \App\Models\Sale::where('id', $this->detailSale->id)->update([
            'status' => $status,
            'dibayar' => $totalDibayar + $kembalian,
            'kembalian' => $kembalian, // Update change
            'jumlah_utang' => max(0, $this->detailSale->total - $totalDibayar),
        ]);

        $this->dispatch('hide-modal', modalId: 'tambahPembayaranModal');
        $this->dispatch('alert', type: 'success', message: 'Pembayaran berhasil disimpan');
    }

    public function render()
    {
        $this->title = 'Daftar Penjualan';
        $this->businessId = auth()->user()->business_id;

        $query = \App\Models\Sale::where('business_id', $this->businessId)->with([
            'customer',
            'saleReturn',
            'payments',
        ]);

        $headers = [
            TableUtil::setTableHeader('id', '#', true, false),
            TableUtil::setTableHeader('no_invoice', 'No. Invoice', true, true),
            TableUtil::setTableHeader('tanggal_transaksi', 'Tanggal Transaksi', true, true),
            TableUtil::setTableHeader('customer.nama_pelanggan', 'Pelanggan', true, true),
            TableUtil::setTableHeader('status', 'Status', true, true),
            TableUtil::setTableHeader('total', 'Total Harga', false, false),
            TableUtil::setTableHeader('id', 'Total Pembayaran', false, false),
            TableUtil::setTableHeader('id', 'Sisa Pembayaran', false, false),
            TableUtil::setTableHeader('aksi', 'Aksi', false, false),
        ];

        $sales = TableUtil::paginate($this, $query, $headers, 10);

        return view('livewire.daftar-penjualan', [
            'sales' => $sales,
            'headers' => $headers,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
