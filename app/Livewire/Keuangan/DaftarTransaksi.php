<?php

namespace App\Livewire\Keuangan;

use App\Models\Payment;
use App\Models\Jurnal;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;

#[Title('Daftar Transaksi')]
class DaftarTransaksi extends Component
{
    use WithPagination;

    public $search = '';
    public $startDate;
    public $endDate;

    public $sortBy = 'tanggal_pembayaran';
    public $sortDirection = 'desc';

    public $headers = [
        ['key' => 'tanggal_pembayaran', 'label' => 'Tanggal', 'sortable' => true],
        ['key' => 'no_pembayaran', 'label' => 'No. Transaksi', 'sortable' => true],
        ['key' => 'rekening_debit', 'label' => 'Akun Debit', 'sortable' => true],
        ['key' => 'rekening_kredit', 'label' => 'Akun Kredit', 'sortable' => true],
        ['key' => 'total_harga', 'label' => 'Nominal', 'sortable' => true],
        ['key' => 'catatan', 'label' => 'Catatan', 'sortable' => true],
        ['key' => 'user_id', 'label' => 'User', 'sortable' => true],
        ['key' => 'action', 'label' => 'Aksi', 'sortable' => false],
    ];

    public function mount()
    {
        $this->startDate = date('Y-m-01');
        $this->endDate = date('Y-m-t');
    }

    public function setSortBy($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStartDate()
    {
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->resetPage();
    }

    public function delete($id)
    {
        $payment = Payment::where('business_id', auth()->user()->business_id)->findOrFail($id);

        DB::beginTransaction();
        try {
            if ($payment->jenis_transaksi === 'jurnal_umum') {
                Jurnal::find($payment->transaction_id)?->delete();
            } elseif ($payment->jenis_transaksi === 'inventaris') {
                Inventory::where('payment_id', $payment->id)->delete();
            }

            $payment->delete();

            DB::commit();
            $this->dispatch('alert', type: 'success', message: 'Transaksi berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $payments = Payment::where('business_id', auth()->user()->business_id)
            ->with(['accountDebit', 'accountKredit', 'user'])
            ->where(function($q) {
                $q->where('no_pembayaran', 'like', '%' . $this->search . '%')
                  ->orWhere('catatan', 'like', '%' . $this->search . '%')
                  ->orWhere('rekening_debit', 'like', '%' . $this->search . '%')
                  ->orWhere('rekening_kredit', 'like', '%' . $this->search . '%');
            })
            ->whereBetween('tanggal_pembayaran', [$this->startDate, $this->endDate])
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);

        return view('livewire.keuangan.daftar-transaksi', [
            'payments' => $payments
        ])->layout('layouts.app');
    }
}
