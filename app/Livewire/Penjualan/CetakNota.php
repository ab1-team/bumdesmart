<?php

namespace App\Livewire\Penjualan;

use App\Models\Owner;
use App\Models\Sale;
use Livewire\Component;

class CetakNota extends Component
{
    public $sale;

    public $owner;

    public function mount($id)
    {
        $this->sale = Sale::with(['saleDetails.product.unit', 'customer', 'user'])->findOrFail($id);

        $this->owner = tenant();

        // Jika tidak ada owner sesuai domain, ambil owner pertama sebagai fallback
        if (! $this->owner) {
            $this->owner = Owner::first();
        }
    }

    public function render()
    {
        return view('livewire.penjualan.cetak-nota')->layout('layouts.empty', ['title' => 'Cetak Nota Penjualan']);
    }
}
