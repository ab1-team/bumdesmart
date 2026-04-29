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
    
    // Pecah Produk Properties
    public $retailNamaProduk;
    public $retailSatuanId;
    public $retailHasilPecah = 0; // Multiplier (e.g., 10 ecer per 1 bulk)
    public $retailJumlahPecahBulk = 0; // Amount of bulk to break (e.g., 1 sak)
    public $retailHargaJual = 0;
    public $retailSku;
    public $retailBarcode;
    
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

    public $importFile;
    public $importStep = 'idle';

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
        $this->stokMinimal = 0;
        $this->barcode = '0';
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

        if (!$product) {
            $this->dispatch('alert', type: 'error', message: 'Produk tidak ditemukan');
            return;
        }

        // 1. Cek apakah sudah ada transaksi nyata (Penjualan atau Pembelian)
        $hasSales = \DB::table('sale_details')->where('product_id', $id)->exists();
        $hasPurchases = \DB::table('purchase_details')->where('product_id', $id)->exists();

        if ($hasSales || $hasPurchases) {
            $this->dispatch('alert', type: 'error', message: 'Produk tidak bisa dihapus karena sudah memiliki riwayat transaksi penjualan atau pembelian. Silakan nonaktifkan saja produk ini.');
            return;
        }

        \DB::beginTransaction();
        try {
            // 2. Hapus data pendukung
            // Hapus Batch Movements terkait batch produk ini
            $batchIds = \App\Models\ProductBatch::where('product_id', $id)->pluck('id');
            \App\Models\BatchMovement::whereIn('batch_id', $batchIds)->delete();
            
            // Hapus Batch
            \App\Models\ProductBatch::where('product_id', $id)->delete();
            
            // Hapus Stock Movements
            \App\Models\StockMovement::where('product_id', $id)->delete();
            
            // Hapus Harga Spesial/Member
            \App\Models\ProductPrice::where('product_id', $id)->delete();

            // 3. Hapus Gambar jika ada
            if ($product->gambar && $product->gambar != 'products/no-image.png') {
                \Storage::delete($product->gambar);
            }

            // 4. Hapus Produk utama
            $product->delete();

            \DB::commit();
            $this->dispatch('alert', type: 'success', message: 'Produk dan data terkait berhasil dihapus');
        } catch (\Exception $e) {
            \DB::rollBack();
            $this->dispatch('alert', type: 'error', message: 'Gagal menghapus produk: ' . $e->getMessage());
        }
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

    public function modalPecahProduk($id)
    {
        $this->reset('retailNamaProduk', 'retailSatuanId', 'retailHasilPecah', 'retailJumlahPecahBulk', 'retailHargaJual', 'retailSku', 'retailBarcode');
        
        $this->detailProduk = \App\Models\Product::find($id);
        
        if (!$this->detailProduk || $this->detailProduk->stok_aktual <= 0) {
            $this->dispatch('alert', type: 'error', message: 'Produk tidak ditemukan atau stok kosong');
            return;
        }

        if ($this->detailProduk->parent_id) {
            $this->dispatch('alert', type: 'error', message: 'Produk eceran tidak dapat dipecah lagi');
            return;
        }

        $this->productId = $id;
        
        // Cek apakah sudah ada produk eceran yang terhubung
        $existingRetail = \App\Models\Product::where('parent_id', $id)->first();
        if ($existingRetail) {
            $this->retailNamaProduk = $existingRetail->nama_produk;
            $this->retailSatuanId = $existingRetail->unit_id;
            $this->retailSku = $existingRetail->sku;
            $this->retailBarcode = $existingRetail->barcode;
            $this->retailHargaJual = number_format($existingRetail->harga_jual);
            // Hint for Hasil Pecah (can be recalculated from previous)
            $this->retailHasilPecah = $existingRetail->harga_beli > 0 ? ($this->detailProduk->harga_beli / $existingRetail->harga_beli) : 0;
        } else {
            $this->retailNamaProduk = $this->detailProduk->nama_produk . ' (Eceran)';
        }

        $this->retailJumlahPecahBulk = 1;
        $this->titleModal = 'Pecah Produk: ' . $this->detailProduk->nama_produk;
        
        $this->dispatch('show-modal', modalId: 'pecahProdukModal');
    }

    public function simpanPecahProduk()
    {
        $this->validate([
            'retailNamaProduk' => 'required',
            'retailSatuanId' => 'required',
            'retailHasilPecah' => 'required|numeric|min:0.01',
            'retailJumlahPecahBulk' => 'required|numeric|min:0.01|max:' . $this->detailProduk->stok_aktual,
            'retailHargaJual' => 'required',
        ]);

        \DB::beginTransaction();
        try {
            $bulkProduct = \App\Models\Product::find($this->productId);
            $hargaBeliEceran = $bulkProduct->harga_beli / $this->retailHasilPecah;
            $totalStokEceranBaru = $this->retailHasilPecah * $this->retailJumlahPecahBulk;

            // Cari produk eceran eksis
            $retailProduct = \App\Models\Product::where('parent_id', $this->productId)->first();

            if ($retailProduct) {
                // Update produk eceran yang sudah ada
                $retailProduct->stok_aktual += $totalStokEceranBaru;
                $retailProduct->harga_beli = $hargaBeliEceran; // Update harga beli jika bulk berubah
                $retailProduct->harga_jual = floatval(str_replace(',', '', $this->retailHargaJual));
                $retailProduct->save();
                $message = 'Stok produk eceran berhasil ditambahkan';
            } else {
                // Buat produk eceran baru
                if (empty($this->retailSku)) {
                    $prefix = 'BM';
                    $uniqueId = substr(time(), -6) . mt_rand(10, 99);
                    $this->retailSku = $prefix . $uniqueId;
                    while (\App\Models\Product::where('sku', $this->retailSku)->exists()) {
                        $uniqueId = substr(time(), -6) . mt_rand(10, 99);
                        $this->retailSku = $prefix . $uniqueId;
                    }
                }

                $retailProduct = \App\Models\Product::create([
                    'business_id' => $this->businessId,
                    'parent_id' => $bulkProduct->id, // Set relationship
                    'category_id' => $bulkProduct->category_id,
                    'brand_id' => $bulkProduct->brand_id,
                    'unit_id' => $this->retailSatuanId,
                    'shelf_id' => $bulkProduct->shelf_id,
                    'sku' => $this->retailSku,
                    'barcode' => $this->retailBarcode,
                    'nama_produk' => $this->retailNamaProduk,
                    'harga_beli' => $hargaBeliEceran,
                    'harga_jual' => floatval(str_replace(',', '', $this->retailHargaJual)),
                    'stok_minimal' => 0,
                    'stok_aktual' => $totalStokEceranBaru,
                    'metode_biaya' => 'FIFO',
                    'biaya_rata_rata' => $hargaBeliEceran,
                    'gambar' => 'products/no-image.png',
                    'is_active' => 1,
                ]);
                $message = 'Produk eceran baru berhasil dibuat';
            }

            // 3. Kurangi stok bulk
            $bulkProduct->stok_aktual -= $this->retailJumlahPecahBulk;
            $bulkProduct->save();

            // 4. Catat Stock Movement Utama
            $smBulk = \App\Models\StockMovement::create([
                'business_id' => $this->businessId,
                'product_id' => $bulkProduct->id,
                'tanggal_perubahan_stok' => now(),
                'jenis_perubahan' => 'Pecah (Bulk)',
                'jumlah_perubahan' => -$this->retailJumlahPecahBulk,
                'reference_id' => $retailProduct->id,
                'reference_type' => 'conversion_to_retail',
                'catatan' => 'Pecah ke produk: ' . $retailProduct->nama_produk,
            ]);

            $smRetail = \App\Models\StockMovement::create([
                'business_id' => $this->businessId,
                'product_id' => $retailProduct->id,
                'tanggal_perubahan_stok' => now(),
                'jenis_perubahan' => 'Pecah (Retail)',
                'jumlah_perubahan' => $totalStokEceranBaru,
                'reference_id' => $bulkProduct->id,
                'reference_type' => 'conversion_from_bulk',
                'catatan' => 'Pecahan dari produk: ' . $bulkProduct->nama_produk,
            ]);

            // 5. Kelola Batch (FIFO)
            $remainingToBreak = $this->retailJumlahPecahBulk;
            $bulkBatches = \App\Models\ProductBatch::where('product_id', $bulkProduct->id)
                ->where('jumlah_saat_ini', '>', 0)
                ->where('status', 'ACTIVE')
                ->orderBy('id', 'asc') // FIFO
                ->get();

            foreach ($bulkBatches as $bBatch) {
                if ($remainingToBreak <= 0) break;

                $takeFromThisBatch = min($bBatch->jumlah_saat_ini, $remainingToBreak);
                
                // Update Batch Bulk
                $bBatch->jumlah_saat_ini -= $takeFromThisBatch;
                if ($bBatch->jumlah_saat_ini == 0) $bBatch->status = 'EMPTY';
                $bBatch->save();

                // Catat Batch Movement Bulk
                \App\Models\BatchMovement::create([
                    'business_id' => $this->businessId,
                    'batch_id' => $bBatch->id,
                    'stock_movement_id' => $smBulk->id,
                    'tanggal_perubahan' => now(),
                    'jenis_transaksi' => 'CONVERSION_OUT',
                    'jumlah' => -$takeFromThisBatch,
                    'harga_satuan' => $bBatch->harga_satuan,
                ]);

                // Buat Batch Baru untuk Retail
                $retailBatchQty = $takeFromThisBatch * $this->retailHasilPecah;
                // Gunakan hargaBeliEceran yang sudah dihitung di awal (misal 30.000 / 12 = 2.500)
                $retailBatchHargaSatuan = $hargaBeliEceran;

                $rBatch = \App\Models\ProductBatch::create([
                    'business_id' => $this->businessId,
                    'product_id' => $retailProduct->id,
                    'purchase_detail_id' => $bBatch->purchase_detail_id,
                    'no_batch' => $bBatch->no_batch . '-R',
                    'tanggal_pembelian' => $bBatch->tanggal_pembelian,
                    'harga_satuan' => $retailBatchHargaSatuan,
                    'jumlah_awal' => $retailBatchQty,
                    'jumlah_saat_ini' => $retailBatchQty,
                    'tanggal_kadaluarsa' => $bBatch->tanggal_kadaluarsa,
                    'status' => 'ACTIVE',
                ]);

                // Catat Batch Movement Retail
                \App\Models\BatchMovement::create([
                    'business_id' => $this->businessId,
                    'batch_id' => $rBatch->id,
                    'stock_movement_id' => $smRetail->id,
                    'tanggal_perubahan' => now(),
                    'jenis_transaksi' => 'CONVERSION_IN',
                    'jumlah' => $retailBatchQty,
                    'harga_satuan' => $retailBatchHargaSatuan,
                ]);

                $remainingToBreak -= $takeFromThisBatch;
            }

            \DB::commit();
            
            $this->dispatch('alert', type: 'success', message: $message);
            $this->dispatch('hide-modal', modalId: 'pecahProdukModal');
            $this->activeTab = 'daftarProduk';
            
        } catch (\Exception $e) {
            \DB::rollBack();
            $this->dispatch('alert', type: 'error', message: 'Gagal memecah produk: ' . $e->getMessage());
        }
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

    public function openImport()
    {
        $this->importFile = null;
        $this->importStep = 'idle';
        $this->dispatch('show-modal', modalId: 'importModal');
    }

    public function processImport()
    {
        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $this->importStep = 'processing';

        try {
            $path = $this->importFile->getRealPath();
            $file = fopen($path, 'r');
            
            // Skip header
            $header = fgetcsv($file);
            
            \DB::beginTransaction();

            // Cache Master Data to avoid repetitive queries
            $categories = \App\Models\Category::where('business_id', $this->businessId)->pluck('id', 'nama_kategori')->toArray();
            $brands     = \App\Models\Brand::where('business_id', $this->businessId)->pluck('id', 'nama_brand')->toArray();
            $units      = \App\Models\Unit::where('business_id', $this->businessId)->pluck('id', 'nama_satuan')->toArray();

            $productUpserts  = [];
            $successCount    = 0;
            $now             = now();

            while (($row = fgetcsv($file)) !== FALSE) {
                if (count($row) < 5) continue;

                $namaProduk = $row[0];
                $kategori   = $row[1] ?: 'General';
                $brand      = $row[2] ?: 'N/A';
                $satuan     = $row[3] ?: 'Pcs';
                $sku        = $row[4] ?: 'SKU-' . strtoupper(\Str::random(8));
                $barcode    = $row[5] ?: (string) mt_rand(100000, 999999) . mt_rand(1000000, 9999999);
                $hargaBeli  = (float) str_replace(['.', ','], ['', '.'], $row[6] ?? 0);
                $hargaJual  = (float) str_replace(['.', ','], ['', '.'], $row[7] ?? 0);
                $stok       = (float) str_replace(['.', ','], ['', '.'], $row[8] ?? 0);

                // Find/Create Master Data from Cache
                if (!isset($categories[$kategori])) {
                    $cat = \App\Models\Category::create(['nama_kategori' => $kategori, 'business_id' => $this->businessId, 'icon' => 'box']);
                    $categories[$kategori] = $cat->id;
                }
                if (!isset($brands[$brand])) {
                    $brd = \App\Models\Brand::create(['nama_brand' => $brand, 'business_id' => $this->businessId]);
                    $brands[$brand] = $brd->id;
                }
                if (!isset($units[$satuan])) {
                    $unt = \App\Models\Unit::create(['nama_satuan' => $satuan, 'business_id' => $this->businessId, 'inisial_satuan' => $satuan]);
                    $units[$satuan] = $unt->id;
                }

                $productUpserts[] = [
                    'business_id'     => $this->businessId,
                    'sku'             => $sku,
                    'barcode'         => $barcode,
                    'category_id'     => $categories[$kategori],
                    'brand_id'        => $brands[$brand],
                    'unit_id'         => $units[$satuan],
                    'nama_produk'     => $namaProduk,
                    'harga_beli'      => $hargaBeli,
                    'harga_jual'      => $hargaJual,
                    'stok_aktual'     => $stok,
                    'metode_biaya'    => 'FIFO',
                    'biaya_rata_rata' => $hargaBeli,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
                $successCount++;
            }

            foreach (array_chunk($productUpserts, 100) as $chunk) {
                foreach ($chunk as $pData) {
                    \App\Models\Product::updateOrCreate(
                        ['business_id' => $pData['business_id'], 'sku' => $pData['sku']],
                        $pData
                    );
                }
            }

            // Re-fetch product IDs to link Batches and Movements
            $allProducts = \App\Models\Product::where('business_id', $this->businessId)
                ->whereIn('sku', array_column($productUpserts, 'sku'))
                ->pluck('id', 'sku');

            $batchInserts    = [];
            $movementInserts = [];

            foreach ($productUpserts as $pData) {
                $sku = $pData['sku'];
                $stok = $pData['stok_aktual'];
                if (isset($allProducts[$sku])) {
                    $pId = $allProducts[$sku];
                    
                    $batchInserts[] = [
                        'business_id'        => $this->businessId,
                        'product_id'         => $pId,
                        'no_batch'           => 'MIGRATION-' . date('Ymd'),
                        'tanggal_pembelian'  => $now,
                        'harga_satuan'       => $pData['harga_beli'],
                        'jumlah_awal'        => $stok,
                        'jumlah_saat_ini'    => $stok,
                        'tanggal_kadaluarsa' => null,
                        'status'             => 'ACTIVE',
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ];

                    $movementInserts[] = [
                        'business_id'            => $this->businessId,
                        'product_id'             => $pId,
                        'tanggal_perubahan_stok' => $now,
                        'jenis_perubahan'        => 'adjustment',
                        'jumlah_perubahan'       => $stok,
                        'reference_id'           => 0,
                        'reference_type'         => 'migration',
                        'catatan'                => 'Migrasi data awal sistem',
                        'created_at'             => $now,
                        'updated_at'             => $now,
                    ];
                }
            }

            if (!empty($batchInserts)) {
                \App\Models\ProductBatch::insert($batchInserts);
            }
            if (!empty($movementInserts)) {
                \App\Models\StockMovement::insert($movementInserts);
            }

            \DB::commit();
            fclose($file);

            $this->dispatch('hide-modal', modalId: 'importModal');
            $this->dispatch('alert', type: 'success', message: $successCount . ' data produk berhasil diimport!');
            $this->reset('importFile', 'importStep');
        } catch (\Exception $e) {
            \DB::rollBack();
            $this->dispatch('alert', type: 'error', message: 'Gagal import: ' . $e->getMessage());
        }

        $this->importStep = 'idle';
    }

    public function downloadTemplate()
    {
        return response()->streamDownload(function () {
            echo "Nama Produk,Kategori,Brand,Satuan,SKU,Barcode,Harga Beli,Harga Jual,Stok\n";
            echo "Indomie Goreng,Makanan & Snack,Indofood,Pcs,IDM-001,8998866200293,2500,3000,100\n";
            echo "Aqua 600ml,Minuman,Danone,Pcs,AQUA-600,8886008101053,2800,3500,500\n";
        }, 'template_import_produk.csv');
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
