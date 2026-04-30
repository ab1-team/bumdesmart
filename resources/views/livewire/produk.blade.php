<div>
    <div class="d-none">
        <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
            <li class="nav-item">
                <a href="#daftarProduk" class="nav-link {{ $activeTab === 'daftarProduk' ? 'active' : '' }}"
                    data-bs-toggle="tab">Daftar Produk</a>
            </li>
            <li class="nav-item">
                <a href="#formProduk" class="nav-link {{ $activeTab === 'formProduk' ? 'active' : '' }}"
                    data-bs-toggle="tab">Form Produk</a>
            </li>
        </ul>
    </div>

    <div class="tab-content">
        <div class="tab-pane {{ $activeTab === 'daftarProduk' ? 'active show' : '' }}" id="daftarProduk">
            <div class="card">
                <div class="card-body">
                    <div class="row justify-content-between mb-3">
                        <div class="col-md-3">
                            <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                                placeholder="🔍 Cari produk...">
                        </div>
                        <div class="col-md-5 d-flex gap-2">
                            @if(count($selectedProducts) > 0)
                                <button class="btn btn-outline-primary" wire:click="modalCetakMassal">
                                    <span class="material-symbols-outlined me-1">print</span>
                                    Cetak ({{ count($selectedProducts) }})
                                </button>
                            @endif
                            <button class="btn btn-outline-warning" wire:click="openStockOpnameImport">
                                <i class="fas fa-clipboard-check me-1"></i> Stok Opname Awal
                            </button>
                            <button class="btn btn-outline-info" wire:click="openImport">
                                <i class="fas fa-file-import me-1"></i> Import Excel
                            </button>
                            <button class="btn btn-primary flex-fill" wire:click="create">
                                <i class="fas fa-plus"></i> Tambah Produk
                            </button>
                        </div>
                    </div>

                    <x-table :headers="$headers" :results="$products" :sortColumn="$sortBy" :sortDirection="$sortDirection">
                        @forelse ($products as $product)
                            <tr>
                                <td>
                                    <input type="checkbox" wire:model.live="selectedProducts" value="{{ $product->id }}" class="form-check-input">
                                </td>
                                <td>
                                    {{ $loop->iteration + ($products->currentPage() - 1) * $products->perPage() }}
                                </td>
                                <td>
                                    <img src="{{ $product->gambar && $product->gambar != 'products/no-image.png' ? asset('storage/' . $product->gambar) : 'https://placehold.co/400x400?text=No+Image' }}"
                                        alt="{{ $product->nama_produk }}"
                                        style="width: 50px; height: 50px; object-fit: cover;">
                                </td>
                                <td>{{ $product->sku }}</td>
                                <td>{{ $product->nama_produk }}</td>
                                <td>{{ $product->category->nama_kategori }}</td>
                                <td>{{ $product->brand->nama_brand }}</td>
                                <td>{{ $product->shelf->nama_rak ?? 'Tidak ada' }}</td>
                                <td>{{ \App\Utils\NumberUtil::format($product->stok_aktual) }}</td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-info dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false" data-bs-boundary="viewport" data-bs-popper-config='{"strategy":"fixed"}'>
                                            <span class="material-symbols-outlined">
                                                more_vert
                                            </span>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="#"
                                                wire:click="edit({{ $product->id }})">
                                                <span class="material-symbols-outlined me-2">edit</span> Edit
                                            </a>
                                            <a class="dropdown-item" href="#"
                                                wire:click="modalDetailProduk({{ $product->id }})">
                                                <span class="material-symbols-outlined me-2">visibility</span> Detail Produk
                                            </a>
                                            <a class="dropdown-item" href="#"
                                                wire:click="modalHargaMember({{ $product->id }})">
                                                <span class="material-symbols-outlined me-2">group</span> Harga Member
                                            </a>
                                            @if($product->stok_aktual > 0 && !$product->parent_id)
                                                <a class="dropdown-item text-primary" href="#"
                                                    wire:click="modalPecahProduk({{ $product->id }})">
                                                    <span class="material-symbols-outlined me-1">content_cut</span>
                                                    Pecah Produk
                                                </a>
                                            @endif
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#"
                                                wire:click="modalCetakLabel({{ $product->id }})">
                                                <span class="material-symbols-outlined me-1">print</span>
                                                Cetak Label
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger" href="#"
                                                wire:click="$dispatch('confirm-delete', {id: {{ $product->id }}})">
                                                <span class="material-symbols-outlined me-2">delete</span> Hapus
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($headers) }}" class="text-center text-muted">
                                    <i class="fas fa-inbox fa-3x mb-2"></i>
                                    <p>Tidak ada data yang ditemukan</p>
                                </td>
                            </tr>
                        @endforelse
                    </x-table>
                </div>
            </div>
        </div>
        <div class="tab-pane {{ $activeTab === 'formProduk' ? 'active show' : '' }}" id="formProduk">
            @if ($activeTab === 'formProduk')
                <form wire:submit.prevent="store">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">SKU / Kode Produk</label>
                                            <input type="text" class="form-control" wire:model="sku" name="sku"
                                                placeholder="SKU" />
                                            @error('sku')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Barcode</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" wire:model="barcode" name="barcode"
                                                    placeholder="Barcode" />
                                                <button class="btn btn-icon btn-primary" type="button" @click="openScanner('barcode')">
                                                    <span class="material-symbols-outlined">qr_code_scanner</span>
                                                </button>
                                            </div>
                                            @error('barcode')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nama Produk</label>
                                            <input type="text" class="form-control" wire:model="namaProduk"
                                                name="namaProduk" placeholder="Nama Produk" />
                                            @error('namaProduk')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Kategori</label>
                                            <div wire:ignore>
                                                <select wire:model="kategori" id="kategori" class="form-select tom-select">
                                                    <option value="">Pilih Kategori</option>
                                                    @foreach ($this->categories as $category)
                                                        <option value="{{ $category->id }}">{{ $category->nama_kategori }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('kategori')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Merek</label>
                                            <div wire:ignore>
                                                <select wire:model="merek" id="merek" class="form-select tom-select">
                                                    <option value="">Pilih Merek</option>
                                                    @foreach ($this->brands as $brand)
                                                        <option value="{{ $brand->id }}">{{ $brand->nama_brand }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('merek')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Satuan</label>
                                            <div wire:ignore>
                                                <select wire:model="satuan" id="satuan" class="form-select tom-select">
                                                    <option value="">Pilih Satuan</option>
                                                    @foreach ($this->units as $unit)
                                                        <option value="{{ $unit->id }}">{{ $unit->nama_satuan }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('satuan')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Rak Penyimpanan</label>
                                            <div wire:ignore>
                                                <select wire:model="rakPenyimpanan" id="rakPenyimpanan"
                                                    class="form-select tom-select">
                                                    <option value="">Pilih Rak Penyimpanan</option>
                                                    @foreach ($this->shelves as $shelf)
                                                        <option value="{{ $shelf->id }}">{{ $shelf->nama_rak }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('rakPenyimpanan')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Harga Beli Default</label>
                                            <input type="text" class="form-control" wire:model="hargaBeliDefault"
                                                name="hargaBeliDefault" x-mask:dynamic="$money($input)"
                                                placeholder="Harga Beli Default" />
                                            @error('hargaBeliDefault')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Harga Jual Default</label>
                                            <input type="text" class="form-control" wire:model="hargaJualDefault"
                                                name="hargaJualDefault" x-mask:dynamic="$money($input)"
                                                placeholder="Harga Jual Default" />
                                            @error('hargaJualDefault')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Stok Minimal</label>
                                            <input type="number" step="any" class="form-control" wire:model="stokMinimal"
                                                name="stokMinimal" placeholder="Stok Minimal" />
                                            @error('stokMinimal')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Aktif</label>
                                            <div class="form-selectgroup">
                                                <label class="form-selectgroup-item">
                                                    <input type="radio" value="1"
                                                        class="form-selectgroup-input" wire:model="aktif"
                                                        {{ $aktif != 0 ? 'checked' : '' }} />
                                                    <span class="form-selectgroup-label">Ya</span>
                                                </label>
                                                <label class="form-selectgroup-item">
                                                    <input type="radio" value="0"
                                                        class="form-selectgroup-input" wire:model="aktif"
                                                        {{ $aktif == 0 ? 'checked' : '' }} />
                                                    <span class="form-selectgroup-label">Tidak</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="mb-3 d-flex justify-content-center">
                                                <img src="{{ $gambar ? $gambar->temporaryUrl() : ($displayGambar && $displayGambar != 'products/no-image.png' ? asset('storage/' . $displayGambar) : 'https://placehold.co/400x400?text=No+Image') }}"
                                                    alt="Gambar" class="img-fluid"
                                                    style="width: 200px; height: 200px; object-fit: cover;">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Gambar</label>
                                                <input type="file" class="form-control" wire:model="gambar"
                                                    name="gambar" placeholder="Gambar" />
                                                @error('gambar')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <button type="button" class="btn"
                                                    wire:click="back">Cancel</button>
                                                <button type="submit" class="btn btn-primary ms-auto">
                                                    Simpan
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    @include('livewire.product-component.modal-detail-produk')
    @include('livewire.product-component.modal-harga-member')
    @include('livewire.product-component.modal-pecah-produk')
    
    <!-- Modal Cetak Label -->
    <div class="modal modal-blur fade" id="cetakLabelModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg">
                <div class="modal-status-top bg-primary"></div>
                <div class="modal-header">
                    <h5 class="modal-title">
                        <span class="material-symbols-outlined me-2 text-primary">print</span>
                        Cetak Label Barcode / QR
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small uppercase fw-bold">Tipe Kode</label>
                            <div class="form-selectgroup w-100">
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" wire:model="labelOptions.type" value="barcode" class="form-selectgroup-input">
                                    <span class="form-selectgroup-label">Barcode</span>
                                </label>
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" wire:model="labelOptions.type" value="qrcode" class="form-selectgroup-input">
                                    <span class="form-selectgroup-label">QR Code</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small uppercase fw-bold">Ukuran (T&J)</label>
                            <select class="form-select" wire:model="labelOptions.size">
                                <optgroup label="Stiker T&J">
                                    <option value="107">No. 107 (18x50mm)</option>
                                    <option value="108">No. 108 (18x38mm)</option>
                                    <option value="103">No. 103 (32x64mm)</option>
                                    <option value="121">No. 121 (38x75mm)</option>
                                    <option value="A4_3_9">A4 (3 Kolom x 9 Baris)</option>
                                </optgroup>
                                <optgroup label="Label Rak (Kertas/Karton)">
                                    <option value="shelf_80_40">Label Rak (80x40mm)</option>
                                    <option value="shelf_90_55">Label Rak (90x55mm) - Kartu Nama</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label text-muted small uppercase fw-bold">Opsi Tampilan</label>
                            <div class="row">
                                <div class="col-6">
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" wire:model="labelOptions.show_price">
                                        <span class="form-check-label">Tampilkan Harga</span>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" wire:model="labelOptions.show_name">
                                        <span class="form-check-label">Tampilkan Nama</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-muted small uppercase fw-bold">Jumlah Cetak per Produk</label>
                            <div class="input-group">
                                <input type="number" class="form-control text-center" wire:model="labelOptions.qty" min="1">
                                <span class="input-group-text">Lembar / Label</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary px-4 ms-auto" wire:click="openPrintLabels">
                        <span class="material-symbols-outlined me-2">print</span>
                        Cetak Sekarang
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scanner Modal -->
    <div class="modal modal-blur fade" id="scannerModal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg overflow-hidden">
                <div class="modal-status-top bg-primary"></div>
                <div class="modal-header">
                    <h5 class="modal-title">
                        <span class="material-symbols-outlined me-2 text-primary">qr_code_scanner</span>
                        Scan Barcode
                    </h5>
                    <button type="button" class="btn-close" @click="closeScanner()"></button>
                </div>
                <div class="modal-body p-0 position-relative border-top border-bottom">
                    <div id="reader" style="width: 100%; min-height: 350px; background: #1d273b;"></div>
                    <div class="scanner-overlay">
                        <div class="scanner-laser"></div>
                        <div class="scanner-frame"></div>
                    </div>
                </div>
                <div class="modal-footer bg-light d-flex justify-content-between py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="toggleCamera()">
                        <span class="material-symbols-outlined me-2">cached</span> Ganti Kamera
                    </button>
                    <button type="button" class="btn btn-primary px-4 shadow-sm" @click="closeScanner()">
                        <span class="material-symbols-outlined me-2">done_all</span> Selesai
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Import --}}
    <div wire:ignore.self class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Data Produk & Stok</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih File (CSV)</label>
                        <input type="file" class="form-control" wire:model="importFile" accept=".csv">
                        <div wire:loading wire:target="importFile" class="text-info small">Memproses file...</div>
                        @error('importFile')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-2"></i>
                        Pastikan file Anda mengikuti format yang benar. 
                        <a href="javascript:void(0)" wire:click="downloadTemplate" class="fw-bold text-decoration-underline">Unduh Template Disini</a>
                    </div>

                    @if ($importStep === 'processing')
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary mb-2"></div>
                            <p>Sedang mengimport data... Mohon tunggu.</p>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" wire:click="processImport"
                        wire:loading.attr="disabled" {{ !$importFile ? 'disabled' : '' }}>
                        Mulai Import
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Import Stok Opname Awal --}}
    <div wire:ignore.self class="modal fade" id="importSoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning-lt">
                    <h5 class="modal-title">Import Penyesuaian Stok Awal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih File (CSV)</label>
                        <input type="file" class="form-control" wire:model="importSoFile" accept=".csv">
                        <div wire:loading wire:target="importSoFile" class="text-info small">Memproses file...</div>
                        @error('importSoFile')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="alert alert-warning small">
                        <i class="fas fa-info-circle me-2"></i>
                        Gunakan fitur ini untuk menyesuaikan stok fisik produk pada saat awal penggunaan aplikasi. 
                        Pastikan file Anda mengikuti format: <strong>No, Kode Produk, Nama Produk, Sistem, Fisik, Keterangan</strong>.
                        <br>
                        <a href="javascript:void(0)" wire:click="downloadSoTemplate" class="fw-bold text-decoration-underline">Unduh Template Penyesuaian</a>
                    </div>

                    @if ($soImportStep === 'processing')
                        <div class="text-center py-3">
                            <div class="spinner-border text-warning mb-2"></div>
                            <p>Sedang memproses penyesuaian stok... Mohon tunggu.</p>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-warning" wire:click="processStockOpnameImport"
                        wire:loading.attr="disabled" {{ !$importSoFile ? 'disabled' : '' }}>
                        Mulai Penyesuaian
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let html5QrCode;
        let currentCameraId;
        let cameras = [];
        let targetField = '';

        function openScanner(field) {
            targetField = field;
            $('#scannerModal').modal('show');
            
            // Wait for modal animation to finish before starting camera
            setTimeout(() => {
                Html5Qrcode.getCameras().then(devices => {
                    if (devices && devices.length) {
                        cameras = devices;
                        currentCameraId = devices[devices.length - 1].id;
                        startScanner(currentCameraId);
                    }
                }).catch(err => {
                    console.error("Gagal mendapatkan kamera:", err);
                    alert("Kamera tidak ditemukan atau izin ditolak.");
                });
            }, 500);
        }

        async function startScanner(cameraId) {
            try {
                if (html5QrCode && html5QrCode.isScanning) {
                    await html5QrCode.stop();
                }
                initScanner(cameraId);
            } catch (err) {
                console.error("Gagal stop/start scanner:", err);
                initScanner(cameraId);
            }
        }

        function initScanner(cameraId) {
            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode("reader");
            }
            
            html5QrCode.start(
                cameraId, 
                { 
                    fps: 10, 
                    qrbox: { width: 250, height: 250 },
                    aspectRatio: 1.0
                },
                (decodedText) => {
                    // Success!
                    if (targetField === 'barcode') {
                        @this.set('barcode', decodedText);
                    } else if (targetField === 'sku') {
                        @this.set('sku', decodedText);
                    }
                    
                    // Visual feedback
                    const reader = document.getElementById('reader');
                    reader.style.border = '5px solid #2fb344';
                    setTimeout(() => {
                        if(reader) reader.style.border = 'none';
                    }, 500);
                    
                    // Beep sound
                    try {
                        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2216/2216-preview.mp3');
                        audio.play();
                    } catch(e) {}

                    // Close scanner
                    closeScanner();
                },
                (errorMessage) => { /* scanning... */ }
            ).catch(err => {
                console.error("Gagal inisialisasi scanner:", err);
            });
        }

        function toggleCamera() {
            if (cameras.length < 2) return;
            let currentIndex = cameras.findIndex(c => c.id === currentCameraId);
            let nextIndex = (currentIndex + 1) % cameras.length;
            currentCameraId = cameras[nextIndex].id;
            startScanner(currentCameraId);
        }

        async function closeScanner() {
            try {
                if (html5QrCode) {
                    if (html5QrCode.isScanning) {
                        await html5QrCode.stop();
                    }
                    await html5QrCode.clear();
                }
            } catch (err) {
                console.error("Gagal menutup kamera:", err);
            } finally {
                $('#scannerModal').modal('hide');
                // Force clear element just in case
                const reader = document.getElementById('reader');
                if (reader) reader.innerHTML = '';
                html5QrCode = null;
            }
        }

        document.addEventListener('livewire:init', () => {
            Livewire.on('open-new-tab', (event) => {
                window.open(event.url, '_blank');
            });
        });
    </script>
    <style>
        .scanner-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            pointer-events: none;
            z-index: 5;
        }
        .scanner-frame {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 250px; height: 250px;
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            box-shadow: 0 0 0 4000px rgba(0, 0, 0, 0.3);
        }
        .scanner-laser {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 230px; height: 2px;
            background: #ff3b30;
            box-shadow: 0 0 15px #ff3b30;
            animation: scan 2s infinite ease-in-out;
        }
        @keyframes scan {
            0%, 100% { top: calc(50% - 110px); }
            50% { top: calc(50% + 110px); }
        }
    </style>
</div>
