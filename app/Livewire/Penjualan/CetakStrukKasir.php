<?php

namespace App\Livewire\Penjualan;

use App\Models\cashDrawer;
use App\Models\Owner;
use App\Models\Sale;
use Livewire\Component;

class CetakStrukKasir extends Component
{
    public $cashDrawer;
    public $owner;
    public $salesTotal;

    public function mount($id)
    {
        $this->cashDrawer = cashDrawer::with(['user', 'business'])->findOrFail($id);
        
        // Fallback owner info
        $this->owner = Owner::first();

        // Calculate total sales during this session
        $this->salesTotal = Sale::where('business_id', $this->cashDrawer->business_id)
            ->where('user_id', $this->cashDrawer->user_id)
            ->whereBetween('created_at', [$this->cashDrawer->tanggal_buka, $this->cashDrawer->tanggal_tutup ?? now()])
            ->sum('dibayar');
    }

    public function render()
    {
        return view('livewire.penjualan.cetak-struk-kasir')->layout('layouts.empty', ['title' => 'Struk Tutup Kasir']);
    }
}
