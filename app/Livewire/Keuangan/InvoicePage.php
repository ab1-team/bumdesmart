<?php

namespace App\Livewire\Keuangan;

use App\Models\Invoice;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;

#[Title('Invoice')]
class InvoicePage extends Component
{
    use WithPagination;

    public $search = '';
    public $startDate;
    public $endDate;
    public $jenisPembayaran = '';
    public $status = '';

    public $sortBy = 'tanggal_invoice';
    public $sortDirection = 'desc';

    public $headers = [
        ['key' => 'no', 'label' => 'No', 'sortable' => true],
        ['key' => 'jenis_pembayaran', 'label' => 'Jenis Pembayaran', 'sortable' => true],
        ['key' => 'tanggal_invoice', 'label' => 'Tanggal Invoice', 'sortable' => true],
        ['key' => 'tagihan', 'label' => 'Tagihan', 'sortable' => true],
        ['key' => 'status', 'label' => 'Status', 'sortable' => true],
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

    public function updatedJenisPembayaran()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function render()
    {
        $invoices = Invoice::with('business.owner')
            ->whereHas('business', function ($q) {
                $q->where('id', auth()->user()->business_id);
            })
            ->where(function ($q) {
                $q->where('no', 'like', '%' . $this->search . '%')
                    ->orWhere('jenis_pembayaran', 'like', '%' . $this->search . '%');
            })
            ->when($this->jenisPembayaran, function ($q) {
                $q->where('jenis_pembayaran', $this->jenisPembayaran);
            })
            ->when($this->status, function ($q) {
                $q->where('status', $this->status);
            })
            ->whereBetween('tanggal_invoice', [$this->startDate, $this->endDate])
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);

        return view('livewire.keuangan.invoice', [
            'invoices' => $invoices
        ])->layout('layouts.app');
    }
}