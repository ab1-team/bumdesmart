<?php

namespace App\Livewire;

use App\Traits\WithTable;
use App\Utils\TableUtil;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Storage;

class Produk extends Component
{
    use WithFileUploads, WithTable;

    public $title;

    public $titleModal;

    public $productId;

    public $businessId;

    public $kategori;

    public $merek;

    public $satuan;

    public $rakPenyimpanan;

    public $sku;

    public $barcode;

    public $namaProduk;

    public $hargaBeliDefault;

    public $hargaJualDefault;

    public $stokMinimal;

    public $gambar;

    public $displayGambar;

    public $activeTab = 'daftarProduk';

    public $aktif = 1;

    public $detailProduk;

    public $hargaJualMember = [];

    public $tanggalMulai = [];

    public $tanggalAkhir = [];
    
    // Label Printing
    public $selectedForLabels = []; // Array of Product IDs
    public $selectedProducts = []; // Checkbox selection
    public $selectAll = false;
    public $labelOptions = [
        'type' => 'barcode', // barcode or qrcode
        'size' => '107',    // 107, 103, 121
        'qty' => 1,
        'show_price' => true,
        'show_name' => true
    ];

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedProducts = \App\Models\Product::where('business_id', $this->businessId)
                ->pluck('id')
                ->map(fn($id) => (string) $id)
                ->toArray();
        } else {
            $this->selectedProducts = [];
        }
    }

    public function mount()
    {
        $this->businessId = auth()->user()->business_id;
    }

    protected function rules()
    {
        return [
            'sku' => [
                'required',
                Rule::unique('products', 'sku')->ignore($this->productId),
            ],
            'barcode' => 'nullable|string',
            'namaProduk' => 'required',
            'kategori' => 'required',
            'merek' => 'required',
            'satuan' => 'required',
            'rakPenyimpanan' => 'nullable',
            'hargaBeliDefault' => 'required',
            'hargaJualDefault' => 'required',
            'stokMinimal' => 'required',
            'gambar' => [
                'nullable',
                Rule::imageFile()->max(5120),
            ],
            'aktif' => 'required',
        ];
    }

    public function resetForm()
    {
        $this->reset('productId', 'sku', 'barcode', 'namaProduk', 'kategori', 'merek', 'satuan', 'rakPenyimpanan', 'hargaBeliDefault', 'hargaJualDefault', 'stokMinimal', 'gambar', 'aktif', 'displayGambar');
    }

    public function create()
    {
        $this->resetForm();
        $this->title = 'Tambah Produk';
        $this->activeTab = 'formProduk';

        $this->dispatch('switch-tab', tabId: 'formProduk');
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->resetValidation();
        $this->titleModal = 'Ubah Rak Penyimpanan';

        $product = \App\Models\Product::find($id);

        $this->sku = $product->sku;
        $this->barcode = $product->barcode;
        $this->namaProduk = $product->nama_produk;
        $this->kategori = $product->category_id;
        $this->merek = $product->brand_id;
        $this->satuan = $product->unit_id;
        $this->rakPenyimpanan = $product->shelf_id;
        $this->hargaBeliDefault = number_format($product->harga_beli);
        $this->hargaJualDefault = number_format($product->harga_jual);
        $this->stokMinimal = $product->stok_minimal;
        $this->aktif = $product->is_active;
        $this->productId = $product->id;

        $this->displayGambar = $product->gambar;
        $this->activeTab = 'formProduk';

        $this->dispatch('switch-tab', tabId: 'formProduk');
    }

    public function back()
    {
        $this->activeTab = 'daftarProduk';
        $this->dispatch('switch-tab', tabId: 'daftarProduk');
    }

    public function store()
    {
        // Generate SKU if empty
        if (empty($this->sku)) {
            $prefix = 'BM';
            $uniqueId = substr(time(), -6) . mt_rand(10, 99);
            $this->sku = $prefix . $uniqueId;
            
            // Ensure uniqueness (just in case)
            while (\App\Models\Product::where('sku', $this->sku)->exists()) {
                $uniqueId = substr(time(), -6) . mt_rand(10, 99);
                $this->sku = $prefix . $uniqueId;
            }
        }

        $this->validate();

        $data = [
            'business_id' => $this->businessId,
            'category_id' => $this->kategori,
            'brand_id' => $this->merek,
            'unit_id' => $this->satuan,
            'shelf_id' => $this->rakPenyimpanan,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'nama_produk' => $this->namaProduk,
            'harga_beli' => floatval(str_replace(',', '', $this->hargaBeliDefault)),
            'harga_jual' => floatval(str_replace(',', '', $this->hargaJualDefault)),
            'stok_minimal' => $this->stokMinimal,
            'stok_aktual' => 0,
            'metode_biaya' => 'FIFO',
            'biaya_rata_rata' => 0,
            'gambar' => 'products/no-image.png',
            'aktif' => $this->aktif,
        ];

        if ($this->productId) {
            $produkLama = \App\Models\Product::find($this->productId);

            $data['gambar'] = $produkLama->gambar;
            if ($this->gambar) {
                $data['gambar'] = $this->gambar->storeAs('products', time().'.'.$this->gambar->getClientOriginalExtension());
                if ($produkLama->gambar && $produkLama->gambar != 'products/no-image.png') {
                    Storage::delete($produkLama->gambar);
                }
            }

            $data['stok_aktual'] = $produkLama->stok_aktual;
            $data['biaya_rata_rata'] = $produkLama->biaya_rata_rata;

            \App\Models\Product::find($this->productId)->update($data);
            $message = 'Produk berhasil diubah';
        } else {
            if ($this->gambar) {
                $data['gambar'] = $this->gambar->storeAs('products', time().'.'.$this->gambar->getClientOriginalExtension());
            }

            \App\Models\Product::create($data);
            $message = 'Produk berhasil ditambahkan';
        }

        $this->activeTab = 'daftarProduk';
        $this->dispatch('switch-tab', tabId: 'daftarProduk');
        $this->dispatch('alert', type: 'success', message: $message);
        $this->resetForm();
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        $product = \App\Models\Product::find($id);
        if ($product->gambar && $product->gambar != 'products/no-image.png') {
            Storage::delete($product->gambar);
        }

        \App\Models\ProductPrice::where('product_id', $id)->delete();
        $product->delete();

        $this->dispatch('alert', type: 'success', message: 'Produk berhasil dihapus');
    }

    public function modalDetailProduk($id)
    {
        $this->detailProduk = \App\Models\Product::where('id', $id)->with([
            'category',
            'brand',
            'unit',
            'shelf',
            'productPrices',
        ])->first();

        $this->titleModal = $this->detailProduk->nama_produk;
        $this->productId = $id;

        $this->dispatch('show-modal', modalId: 'detailProdukModal');
    }

    public function modalHargaMember($id)
    {
        $this->reset('hargaJualMember', 'tanggalMulai', 'tanggalAkhir');
        $this->detailProduk = \App\Models\Product::where('id', $id)->with([
            'category',
            'brand',
            'unit',
            'shelf',
            'productPrices',
        ])->first();

        foreach ($this->detailProduk->productPrices as $productPrice) {
            $this->hargaJualMember[$productPrice->customer_group_id] = number_format($productPrice->harga_spesial);
            $this->tanggalMulai[$productPrice->customer_group_id] = $productPrice->tanggal_mulai;
            $this->tanggalAkhir[$productPrice->customer_group_id] = $productPrice->tanggal_akhir;
        }

        $this->titleModal = $this->detailProduk->nama_produk;
        $this->productId = $id;

        $this->dispatch('show-modal', modalId: 'hargaMemberModal');
    }

    public function simpanHargaMember()
    {
        $productPrices = [];
        $customerGroups = \App\Models\CustomerGroup::where('business_id', $this->businessId)->get();
        foreach ($customerGroups as $customerGroup) {
            if (isset($this->hargaJualMember[$customerGroup->id]) && $this->hargaJualMember[$customerGroup->id] > 0) {
                $hargaJualMember = $this->hargaJualMember[$customerGroup->id];
                $tanggalMulai = $this->tanggalMulai[$customerGroup->id] ?? null;
                $tanggalAkhir = $this->tanggalAkhir[$customerGroup->id] ?? null;

                $productPrices[] = [
                    'product_id' => $this->productId,
                    'customer_group_id' => $customerGroup->id,
                    'harga_spesial' => floatval(str_replace(',', '', $hargaJualMember)),
                    'tanggal_mulai' => $tanggalMulai,
                    'tanggal_akhir' => $tanggalAkhir,
                ];
            }
        }

        \App\Models\ProductPrice::where('product_id', $this->productId)->delete();
        \App\Models\ProductPrice::insert($productPrices);

        $this->dispatch('alert', type: 'success', message: 'Harga member berhasil disimpan');
        $this->dispatch('hide-modal', modalId: 'hargaMemberModal');
        $this->reset('hargaJualMember', 'tanggalMulai', 'tanggalAkhir');
    }

    public function modalCetakLabel($id = null)
    {
        if ($id) {
            $this->selectedForLabels = [$id];
        }
        
        $this->labelOptions['qty'] = 1;
        $this->dispatch('show-modal', modalId: 'cetakLabelModal');
    }

    public function modalCetakMassal()
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('alert', type: 'error', message: 'Pilih produk terlebih dahulu');
            return;
        }

        $this->selectedForLabels = $this->selectedProducts;
        $this->labelOptions['qty'] = 1;
        $this->dispatch('show-modal', modalId: 'cetakLabelModal');
    }

    public function openPrintLabels()
    {
        if (empty($this->selectedForLabels)) {
            $this->dispatch('alert', type: 'error', message: 'Pilih produk terlebih dahulu');
            return;
        }

        $ids = implode(',', $this->selectedForLabels);
        $type = $this->labelOptions['type'];
        $size = $this->labelOptions['size'];
        $qty = $this->labelOptions['qty'];
        $price = $this->labelOptions['show_price'] ? 1 : 0;
        $name = $this->labelOptions['show_name'] ? 1 : 0;

        $url = route('produk.cetak-label', [
            'ids' => $ids,
            'type' => $type,
            'size' => $size,
            'qty' => $qty,
            'price' => $price,
            'name' => $name
        ]);

        $this->dispatch('open-new-tab', url: $url);
        $this->dispatch('hide-modal', modalId: 'cetakLabelModal');
    }

    #[\Livewire\Attributes\Computed]
    public function categories()
    {
        return \App\Models\Category::where('business_id', $this->businessId)->get();
    }

    #[\Livewire\Attributes\Computed]
    public function brands()
    {
        return \App\Models\Brand::where('business_id', $this->businessId)->get();
    }

    #[\Livewire\Attributes\Computed]
    public function units()
    {
        return \App\Models\Unit::where('business_id', $this->businessId)->get();
    }

    #[\Livewire\Attributes\Computed]
    public function shelves()
    {
        return \App\Models\Shelves::where('business_id', $this->businessId)->get();
    }

    #[\Livewire\Attributes\Computed]
    public function customerGroups()
    {
        return \App\Models\CustomerGroup::where('business_id', $this->businessId)->get();
    }

    public function render()
    {
        $this->title = 'Produk';

        $query = \App\Models\Product::where('business_id', $this->businessId)->with([
            'category',
            'brand',
            'unit',
            'shelf',
            'productPrices',
        ]);

        $headers = [
            TableUtil::setTableHeader('selection', '<input type="checkbox" wire:model.live="selectAll" class="form-check-input">', false, false),
            TableUtil::setTableHeader('id', '#', false, false),
            TableUtil::setTableHeader('gambar', 'Gambar', false, false),
            TableUtil::setTableHeader('sku', 'SKU', true, true),
            TableUtil::setTableHeader('nama_produk', 'Nama Produk', true, true),
            TableUtil::setTableHeader('category.nama_kategori', 'Kategori', true, true),
            TableUtil::setTableHeader('brand.nama_brand', 'Merek', true, true),
            TableUtil::setTableHeader('shelf.nama_rak', 'Rak', true, true),
            TableUtil::setTableHeader('stok_aktual', 'Stok', true, true),
            TableUtil::setTableHeader('aksi', 'Aksi', false, false),
        ];

        $products = TableUtil::paginate($this, $query, $headers, 10);

        return view('livewire.produk', [
            'products' => $products,
            'headers' => $headers,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
