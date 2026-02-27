<div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true"
    wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="checkoutModalLabel">Checkout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Total Display -->
                <div class="text-center mb-4">
                    <small class="text-secondary text-uppercase fw-bold">Total Pembayaran</small>
                    <div class="display-5 fw-bold text-primary" x-text="formatRupiah(subtotal)"></div>
                </div>

                <!-- Customer Selection -->
                <div class="mb-4" wire:ignore>
                    <label class="form-label">Customer</label>
                    <div class="w-100 position-relative">
                        <select class="form-select" id="customerSearch" x-model="customerSearch" wire:ignore>
                            <option value=""></option>
                        </select>
                    </div>
                </div>

                <!-- Payment Input -->
                <div class="mb-3">
                    <label class="form-label">Jumlah Bayar</label>
                    <input type="text" class="form-control" x-mask:dynamic="$money($input)"
                        x-model="formatRupiah(checkOut.bayar)" placeholder="0" id="paymentInput" />
                    <!-- Quick Amounts -->
                    <div class="mt-2 d-flex gap-2 flex-wrap justify-content-center">
                        <button type="button" class="btn btn-sm btn-outline-primary"
                            @click="checkOut.bayar = subtotal">Uang Pas</button>

                        <!-- Dynamic Suggestions -->
                        <template x-for="amount in getSuggestedAmounts()" :key="amount">
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                @click="checkOut.bayar = amount" x-show="amount > subtotal"
                                x-text="formatRupiah(amount)"></button>
                        </template>
                    </div>
                </div>

                <!-- Change Display -->
                <div class="card bg-muted-lt">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Kembalian</span>
                            <span class="fs-2 fw-bold" :class="calculateChange() < 0 ? 'text-danger' : 'text-success'"
                                x-text="formatRupiah(calculateChange())"></span>
                        </div>
                        <div class="text-end text-danger small" x-show="calculateChange() < 0">
                            (Kurang Bayar - Masuk Hutang)
                        </div>
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label class="form-label">Catatan</label>
                    <textarea class="form-control" rows="2" x-model="checkOut.note" placeholder="Catatan transaksi..."></textarea>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary ms-auto" @click="submitSale"
                    :disabled="!checkOut.bayar && checkOut.bayar !== 0">
                    Proses Transaksi
                </button>
            </div>
        </div>
    </div>
</div>
