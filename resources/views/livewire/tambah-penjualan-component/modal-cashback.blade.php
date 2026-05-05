<div class="modal fade" id="cashbackModal" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg">
            <div class="modal-header">
                <h4 class="modal-title d-flex align-items-center">
                    Cashback Produk
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="list-group-item mb-3">
                    <div class="row">
                        <div class="col-auto">
                            <img :src="'/storage/' + modalProduct.gambar" alt="Gambar Produk" class="avatar avatar-1">
                        </div>
                        <div class="col text-truncate">
                            <a href="#" class="text-body fw-bold d-block" x-text="modalProduct.nama_produk"></a>
                            <div class="text-secondary text-truncate mt-n1"
                                x-text="'Rp ' + formatRupiah(parseFormatted(modalProduct.harga_jual))"></div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small">Cashback</label>
                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-auto">
                            <div class="form-selectgroup">
                                <label class="form-selectgroup-item">
                                    <input type="radio" value="nominal" class="form-selectgroup-input"
                                        x-model="modalProduct.cashback.jenis">
                                    <span class="form-selectgroup-label px-2">Rp</span>
                                </label>
                                <label class="form-selectgroup-item">
                                    <input type="radio" value="persen" class="form-selectgroup-input"
                                        x-model="modalProduct.cashback.jenis">
                                    <span class="form-selectgroup-label px-2">%</span>
                                </label>
                            </div>
                        </div>
                        <div class="col">
                            <input type="text" class="form-control" x-mask:dynamic="$money($input, ',', '.', 2)"
                                x-model="modalProduct.cashback.jumlah">
                        </div>
                    </div>
                </div>

                <!-- Live Cashback Calculation Preview -->
                <div class="card bg-info-lt" x-show="parseFormatted(modalProduct.cashback.jumlah) > 0" x-transition>
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Cashback per item:</span>
                            <span class="fw-bold fs-3 text-info">
                                <span
                                    x-text="'Rp ' + formatRupiah(
                                    modalProduct.cashback.jenis === 'nominal' 
                                    ? parseFormatted(modalProduct.cashback.jumlah) 
                                    : (parseFormatted(modalProduct.harga_jual) * parseFormatted(modalProduct.cashback.jumlah) / 100)
                                )"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary ms-auto" x-on:click="saveCashback">
                    Simpan Cashback
                </button>
            </div>
        </div>
    </div>
</div>
