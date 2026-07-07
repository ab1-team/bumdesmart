<?php

namespace App\Livewire\Master;

use App\Models\Owner;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Master Invoice')]
class MasterInvoice extends Component
{
    public $search = '';

    public $status = '';

    public $jenisPembayaran = '';

    public $rows = [];

    public $editingId;

    public $editingOwnerId;

    public $tanggalDiterima;

    public $saldo;

    public $metodePembayaran;

    public $keterangan;

    public $titleModal;

    public function mount()
    {
        $this->loadInvoices();
    }

    public function updatedSearch()
    {
        $this->loadInvoices();
    }

    public function updatedStatus()
    {
        $this->loadInvoices();
    }

    public function updatedJenisPembayaran()
    {
        $this->loadInvoices();
    }

    public function loadInvoices()
    {
        $rows = [];

        foreach (Owner::all() as $owner) {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            tenancy()->initialize($owner);

            try {
                $q = DB::table('invoices');

                if ($this->search) {
                    $q->where(function ($x) {
                        $x->where('no', 'like', '%'.$this->search.'%')
                            ->orWhere('jenis_pembayaran', 'like', '%'.$this->search.'%');
                    });
                }
                if ($this->status) {
                    $q->where('status', $this->status);
                }
                if ($this->jenisPembayaran) {
                    $q->where('jenis_pembayaran', $this->jenisPembayaran);
                }

                $items = $q->orderByDesc('tanggal_invoice')->get();

                foreach ($items as $item) {
                    $item->nama_usaha = $owner->nama_usaha;
                    $item->owner_id = $owner->id;
                    $rows[] = (array) $item;
                }
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        usort($rows, fn ($a, $b) => strcmp($b['tanggal_invoice'] ?? '', $a['tanggal_invoice'] ?? ''));
        $this->rows = $rows;
    }

    public function bayarInvoice($id, $ownerId)
    {
        $this->editingId = $id;
        $this->editingOwnerId = $ownerId;
        $this->titleModal = 'Pembayaran Invoice';

        $owner = Owner::findOrFail($ownerId);

        if (tenancy()->initialized) {
            tenancy()->end();
        }
        tenancy()->initialize($owner);

        try {
            $invoice = DB::table('invoices')->where('id', $id)->first();
            if ($invoice) {
                $this->tanggalDiterima = $invoice->tanggal_diterima ?: now()->toDateString();
                $this->saldo = $invoice->saldo ?: $invoice->tagihan;
                $this->metodePembayaran = $invoice->metode_pembayaran ?: '';
                $this->keterangan = $invoice->keterangan ?: '';
            }
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }

        $this->dispatch('show-modal', modalId: 'masterInvoiceBayarModal');
    }

    public function savePembayaran()
    {
        $this->validate([
            'tanggalDiterima' => 'required|date',
            'saldo' => 'required',
            'metodePembayaran' => 'required|string|max:100',
            'keterangan' => 'nullable|string',
        ]);

        $saldo = (int) preg_replace('/[^0-9]/', '', (string) $this->saldo);

        $owner = Owner::findOrFail($this->editingOwnerId);

        if (tenancy()->initialized) {
            tenancy()->end();
        }
        tenancy()->initialize($owner);

        try {
            DB::table('invoices')->where('id', $this->editingId)->update([
                'tanggal_diterima' => $this->tanggalDiterima,
                'saldo' => $saldo,
                'metode_pembayaran' => $this->metodePembayaran,
                'keterangan' => $this->keterangan,
                'status' => 'PAID',
                'updated_at' => now(),
            ]);
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }

        $this->dispatch('hide-modal', modalId: 'masterInvoiceBayarModal');
        $this->dispatch('alert', type: 'success', message: 'Pembayaran invoice berhasil disimpan, status otomatis PAID');
        $this->reset(['editingId', 'editingOwnerId', 'tanggalDiterima', 'saldo', 'metodePembayaran', 'keterangan']);
        $this->loadInvoices();
    }

    public function render()
    {
        return view('livewire.master.master-invoice');
    }
}