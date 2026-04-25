<?php

namespace App\Livewire\Penjualan;

use App\Models\Owner;
use App\Models\Sale;
use Livewire\Component;

class CetakStruk extends Component
{
    public $sale;

    public $owner;
    public $business;

    public function mount($id)
    {
        $this->sale = Sale::with(['saleDetails.product', 'customer', 'user', 'payments'])->findOrFail($id);

        $this->owner = tenant();
        $this->business = \App\Models\Business::where('id', $this->sale->user->business_id)->first() ?? \App\Models\Business::first();

        // Jika tidak ada owner sesuai domain, ambil owner pertama sebagai fallback
        if (! $this->owner) {
            $this->owner = Owner::first();
        }
    }

    public function render()
    {
        return view('livewire.penjualan.cetak-struk')->layout('layouts.empty', ['title' => 'Cetak Struk']);
    }
}
