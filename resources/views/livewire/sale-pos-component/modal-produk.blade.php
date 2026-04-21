<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true"
    wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title d-flex align-items-center">
                    Pengaturan Produk
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <template x-if="selectedItem">
                    <div>
                        <div class="list-group-item mb-3 border-bottom pb-3">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="avatar avatar-lg"
                                        :style="`background-image: url(${selectedItem.image})`"></span>
                                </div>
                                <div class="col text-truncate">
                                    <a href="#" class="text-body fw-bold d-block fs-3"
                                        x-text="selectedItem.name"></a>
                                    <div class="text-secondary mt-n1"
                                        x-text="formatRupiah(selectedItem.price) + ' x ' + selectedItem.qty"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jumlah (Qty)</label>
                            <input type="number" step="any" class="form-control" x-model="selectedItem.qty"
                                placeholder="Masukkan Jumlah">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Diskon</label>
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-auto">
                                    <div class="form-selectgroup">
                                        <label class="form-selectgroup-item">
                                            <input type="radio" value="nominal" class="form-selectgroup-input"
                                                x-model="selectedItem.diskon.jenis">
                                            <span class="form-selectgroup-label px-2">Rp</span>
                                        </label>
                                        <label class="form-selectgroup-item">
                                            <input type="radio" value="persen" class="form-selectgroup-input"
                                                x-model="selectedItem.diskon.jenis">
                                            <span class="form-selectgroup-label px-2">%</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col">
                                    <input type="number" class="form-control" x-model="selectedItem.diskon.jumlah"
                                        placeholder="Jumlah Diskon">
                                </div>
                            </div>
                            <div class="form-hint text-success" x-show="calculateItemDiscount(selectedItem) > 0">
                                Potongan: <span x-text="formatRupiah(calculateItemDiscount(selectedItem))"></span>
                            </div>
                        </div>

                        <!-- Cashback Section -->
                        <div class="mb-3">
                            <label class="form-label">Cashback</label>
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-auto">
                                    <div class="form-selectgroup">
                                        <label class="form-selectgroup-item">
                                            <input type="radio" value="nominal" class="form-selectgroup-input"
                                                x-model="selectedItem.cashback.jenis">
                                            <span class="form-selectgroup-label px-2">Rp</span>
                                        </label>
                                        <label class="form-selectgroup-item">
                                            <input type="radio" value="persen" class="form-selectgroup-input"
                                                x-model="selectedItem.cashback.jenis">
                                            <span class="form-selectgroup-label px-2">%</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col">
                                    <input type="number" class="form-control" x-model="selectedItem.cashback.jumlah"
                                        placeholder="Jumlah Cashback">
                                </div>
                            </div>
                            <div class="form-hint text-info" x-show="calculateItemCashback(selectedItem) > 0">
                                Cashback: <span x-text="formatRupiah(calculateItemCashback(selectedItem))"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal">Selesai</button>
            </div>
        </div>
    </div>
</div>
