<?php

namespace App\Livewire\Master;

use App\Models\Owner;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Buat Invoice')]
class MasterInvoiceCreate extends Component
{
    public $ownerId;

    public $businessId;

    public $namaUsaha;

    public $no;

    public $jenisPembayaran;

    public $tanggalInvoice;

    public $tagihan;

    public $status = 'UNPAID';

    public $titleModal;

    protected function rules()
    {
        return [
            'jenisPembayaran' => 'required|string|max:100',
            'tanggalInvoice'  => 'required|date',
            'tagihan'         => 'required',
            'status'          => 'required|in:UNPAID,PAID,PARTIAL',
        ];
    }

    #[On('open-invoice-modal')]
    public function openInvoiceModal($ownerId, $businessId, $namaUsaha)
    {
        $this->resetForm();
        $this->resetValidation();

        $this->ownerId   = $ownerId;
        $this->businessId = $businessId;
        $this->namaUsaha = $namaUsaha;
        $this->titleModal = 'Buat Invoice - '.$namaUsaha;

        $owner = Owner::findOrFail($ownerId);

        if (tenancy()->initialized) {
            tenancy()->end();
        }
        tenancy()->initialize($owner);

        try {
            $lastNo = DB::table('invoices')->where('business_id', $businessId)->max('no');
            $this->no = ($lastNo ?? 0) + 1;
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }

        $this->tanggalInvoice = now()->toDateString();
        $this->dispatch('show-modal', modalId: 'masterInvoiceModal');
    }

    public function resetForm()
    {
        $this->reset('ownerId', 'businessId', 'namaUsaha', 'no', 'jenisPembayaran', 'tanggalInvoice', 'tagihan', 'status');
    }

    public function save()
    {
        $this->validate();

        $tagihan = (int) preg_replace('/[^0-9]/', '', (string) $this->tagihan);

        $owner = Owner::findOrFail($this->ownerId);

        if (tenancy()->initialized) {
            tenancy()->end();
        }
        tenancy()->initialize($owner);

        try {
            DB::table('invoices')->insert([
                'business_id'      => $this->businessId,
                'no'               => $this->no,
                'jenis_pembayaran' => $this->jenisPembayaran,
                'tanggal_invoice'  => $this->tanggalInvoice,
                'tagihan'          => $tagihan,
                'saldo'            => 0,
                'status'           => $this->status,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }

        $this->dispatch('hide-modal', modalId: 'masterInvoiceModal');
        $this->dispatch('alert', type: 'success', message: 'Invoice berhasil dibuat untuk '.$this->namaUsaha);
        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.master.master-invoice-create');
    }
}