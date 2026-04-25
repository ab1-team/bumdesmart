<?php

namespace App\Livewire\Pembelian;

use App\Models\Owner;
use App\Models\Purchase;
use Livewire\Component;

class CetakNota extends Component
{
    public $purchase;

    public $owner;
    public $business;

    public function mount($id)
    {
        $this->purchase = Purchase::with(['purchaseDetails.product.unit', 'supplier', 'business', 'payments'])->findOrFail($id);

        $this->owner = tenant();
        $this->business = $this->purchase->business;

        if (! $this->owner) {
            $this->owner = Owner::first();
        }
    }

    public function render()
    {
        return view('livewire.pembelian.cetak-nota')->layout('layouts.empty', ['title' => 'Cetak Nota Pembelian']);
    }
}
