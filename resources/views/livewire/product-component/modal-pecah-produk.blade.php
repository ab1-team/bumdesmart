<div wire:ignore.self class="modal modal-blur fade" id="pecahProdukModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg">
            <div class="modal-status-top bg-primary"></div>
            <div class="modal-header">
                <h5 class="modal-title">
                    <span class="material-symbols-outlined me-2 text-primary">content_cut</span>
                    {{ $titleModal }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form wire:submit.prevent="simpanPecahProduk">
                <div class="modal-body">
                    @if($detailProduk)
                        <div class="alert alert-info bg-light border-0 shadow-sm mb-4">
                            <div class="d-flex">
                                <div class="me-3">
                                    <span class="material-symbols-outlined text-info fs-1">info</span>
                                </div>
                                <div>
                                    <h4 class="alert-title mb-1">Informasi Produk Asal (Bulk)</h4>
                                    <div class="text-secondary">
                                        Stok Saat Ini: <strong>{{ \App\Utils\NumberUtil::format($detailProduk->stok_aktual) }} {{ $detailProduk->unit->nama_satuan }}</strong><br>
                                        Harga Beli: <strong>Rp {{ \App\Utils\NumberUtil::format($detailProduk->harga_beli) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($retailSku && \App\Models\Product::where('parent_id', $productId)->exists())
                            <div class="alert alert-warning border-0 shadow-sm mb-3">
                                <div class="d-flex">
                                    <span class="material-symbols-outlined me-2">sync</span>
                                    <div>
                                        <strong>Produk Eceran Terdeteksi!</strong><br>
                                        Pemecahan ini akan **menambah stok** ke produk: <em>{{ $retailNamaProduk }}</em>.
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label required">Nama Produk Eceran</label>
                                <input type="text" class="form-control" wire:model="retailNamaProduk" placeholder="Contoh: Semen 5kg">
                                @error('retailNamaProduk') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Satuan Eceran</label>
                                <div wire:ignore>
                                    <select class="form-select tom-select" id="retailSatuanId" wire:model="retailSatuanId">
                                        <option value="">Pilih Satuan</option>
                                        @foreach($this->units as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->nama_satuan }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('retailSatuanId') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Hasil Pecah per 1 {{ $detailProduk->unit->nama_satuan }}</label>
                                <div class="input-group">
                                    <input type="number" step="any" class="form-control text-center" wire:model.live="retailHasilPecah">
                                    <span class="input-group-text">Item</span>
                                </div>
                                <small class="text-muted">Contoh: 1 Sak jadi 10 Pack</small>
                                @error('retailHasilPecah') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Jumlah yang Dipecah</label>
                                <div class="input-group">
                                    <input type="number" step="any" class="form-control text-center" wire:model.live="retailJumlahPecahBulk">
                                    <span class="input-group-text">{{ $detailProduk->unit->nama_satuan }}</span>
                                </div>
                                <small class="text-muted">Maks: {{ \App\Utils\NumberUtil::format($detailProduk->stok_aktual) }}</small>
                                @error('retailJumlahPecahBulk') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Harga Jual Eceran</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control" wire:model="retailHargaJual" x-mask:dynamic="$money($input, ',', '.', 2)" x-on:focus="$el.select()">
                                </div>
                                @error('retailHargaJual') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">SKU Eceran (Opsional)</label>
                                <input type="text" class="form-control" wire:model="retailSku" placeholder="Otomatis jika kosong">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Barcode Eceran (Opsional)</label>
                                <input type="text" class="form-control" wire:model="retailBarcode">
                            </div>
                        </div>

                        <div class="card bg-primary-lt border-0 mt-3">
                            <div class="card-body p-3">
                                <h4 class="mb-2">Ringkasan Hasil Pecah:</h4>
                                <div class="row text-center">
                                    <div class="col-6 border-end">
                                        <div class="text-uppercase text-secondary small fw-bold">Total Stok Baru</div>
                                        <div class="fs-2 fw-bold text-primary">{{ \App\Utils\NumberUtil::format($retailHasilPecah * (float)$retailJumlahPecahBulk) }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-uppercase text-secondary small fw-bold">Harga Beli Baru</div>
                                        <div class="fs-2 fw-bold text-primary">Rp {{ \App\Utils\NumberUtil::format($retailHasilPecah > 0 ? $detailProduk->harga_beli / $retailHasilPecah : 0) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 ms-auto shadow-sm" wire:loading.attr="disabled">
                        <span class="material-symbols-outlined me-2">check_circle</span>
                        Simpan & Pecah Produk
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
