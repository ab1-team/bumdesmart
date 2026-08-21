<div>
    <div class="modal fade" id="masterInvoiceModal" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ $titleModal ?? 'Buat Invoice' }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="save">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">No Invoice</label>
                                <input type="text" class="form-control" wire:model="no" readonly />
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Invoice <span class="text-danger">*</span></label>
                                <input type="text" class="form-control litepicker" id="tanggalInvoice"
                                    wire:model="tanggalInvoice" placeholder="YYYY-MM-DD" autocomplete="off" />
                                @error('tanggalInvoice')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jenis Pembayaran <span class="text-danger">*</span></label>
                                <select class="form-select tom-select" id="jenisPembayaran" wire:model="jenisPembayaran">
                                    <option value="">-- Pilih Jenis Pembayaran --</option>
                                    <option value="Biaya Lisensi Instalasi">Biaya Lisensi Instalasi</option>
                                    <option value="Biaya Perpanjangan Maintenance dan Server">Biaya Perpanjangan Maintenance dan Server</option>
                                    <option value="Biaya Bimbingan Teknis">Biaya Bimbingan Teknis</option>
                                    <option value="Biaya Migrasi Ulang">Biaya Migrasi Ulang</option>
                                    <option value="Biaya Aktivasi WA Gateway">Biaya Aktivasi WA Gateway</option>
                                </select>
                                @error('jenisPembayaran')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select tom-select" id="statusInvoice" wire:model="status">
                                    <option value="UNPAID">UNPAID</option>
                                    <option value="PARTIAL">PARTIAL</option>
                                    <option value="PAID">PAID</option>
                                </select>
                                @error('status')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Tagihan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model="tagihan"
                                    x-mask:dynamic="$money($input, '.', ',', 0)" placeholder="0" />
                                @error('tagihan')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary ms-auto" wire:click="save">
                        Simpan Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>