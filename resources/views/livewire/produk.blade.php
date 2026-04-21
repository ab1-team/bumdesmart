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
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" wire:click="create">
                                <i class="fas fa-plus"></i> Tambah Produk
                            </button>
                        </div>
                    </div>

                    <x-table :headers="$headers" :results="$products" :sortColumn="$sortBy" :sortDirection="$sortDirection">
                        @forelse ($products as $product)
                            <tr>
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
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="material-symbols-outlined">
                                                more_vert
                                            </span>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="#"
                                                wire:click="edit({{ $product->id }})">
                                                Edit
                                            </a>
                                            <a class="dropdown-item" href="#"
                                                wire:click="modalDetailProduk({{ $product->id }})">
                                                Detail Produk
                                            </a>
                                            <a class="dropdown-item" href="#"
                                                wire:click="modalHargaMember({{ $product->id }})">
                                                Harga Member
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger" href="#"
                                                wire:click="$dispatch('confirm-delete', {id: {{ $product->id }}})">
                                                Hapus
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
</div>
