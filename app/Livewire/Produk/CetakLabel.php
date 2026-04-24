<?php

namespace App\Livewire\Produk;

use App\Models\Product;
use Livewire\Component;

class CetakLabel extends Component
{
    public $products = [];
    public $type;
    public $size;
    public $qty;
    public $showPrice;
    public $showName;

    public function mount()
    {
        $ids = request()->query('ids');
        $this->type = request()->query('type', 'barcode');
        $this->size = request()->query('size', '107');
        $this->qty = request()->query('qty', 1);
        $this->showPrice = request()->query('price', 1) == 1;
        $this->showName = request()->query('name', 1) == 1;

        if ($ids) {
            $idArray = explode(',', $ids);
            $this->products = Product::whereIn('id', $idArray)->get();
        }
    }

    public function render()
    {
        return view('livewire.produk.cetak-label')
            ->layout('layouts.blank'); // We need a blank layout for printing
    }
}
