<?php

namespace App\Livewire\Penjualan;

use App\Models\Owner;
use App\Models\Sale;
use Livewire\Component;

class CetakStruk extends Component
{
    public $sale;

    public $owner;

    public function mount($id)
    {
        $this->sale = Sale::with(['saleDetails.product', 'customer', 'user'])->findOrFail($id);

        // Cek domain untuk mengambil data Owner (opsional, karena aplikasi mungkin multi-tenant/domain)
        $domain = request()->getHost();
        $this->owner = Owner::where('domain', $domain)->orWhere('domain_alternatif', $domain)->first();

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
