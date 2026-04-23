<div>

    <form wire:submit="updateSettings">
        <div class="row row-cards">
            <!-- Data Bisnis / Toko -->
            <div class="col-md-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h3 class="card-title text-primary">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">store</span>
                            Informasi Bisnis & Toko
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label required font-weight-bold">Nama Toko / Unit Usaha</label>
                                <input type="text" class="form-control @error('nama_toko') is-invalid @enderror"
                                    wire:model="nama_toko" placeholder="Masukkan nama toko">
                                @error('nama_toko')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label required font-weight-bold">Alamat Lengkap</label>
                                <textarea class="form-control @error('alamat_toko') is-invalid @enderror" 
                                    wire:model="alamat_toko" rows="4" placeholder="Alamat lengkap operasional toko"></textarea>
                                @error('alamat_toko')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">No. Telepon / WhatsApp</label>
                                <input type="text" class="form-control @error('no_telp_toko') is-invalid @enderror"
                                    wire:model="no_telp_toko" placeholder="08xxxx">
                                @error('no_telp_toko')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Email Bisnis</label>
                                <input type="email" class="form-control @error('email_toko') is-invalid @enderror"
                                    wire:model="email_toko" placeholder="email@toko.com">
                                @error('email_toko')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Owner (Pusat) -->
            <div class="col-md-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light">
                        <h3 class="card-title text-primary">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">corporate_fare</span>
                            Identitas Pengelola (Owner)
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label required font-weight-bold">Nama Perusahaan Pengelola</label>
                            <input type="text" class="form-control @error('nama_perusahaan') is-invalid @enderror"
                                wire:model="nama_perusahaan" placeholder="Nama BUMDES / PT Pengelola">
                            @error('nama_perusahaan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Logo Perusahaan</label>
                            <div class="p-3 border rounded-3 bg-light text-center">
                                <div class="mb-3 d-flex justify-content-center">
                                    @if ($new_logo)
                                        <span class="avatar avatar-xl shadow-sm border border-primary"
                                            style="background-image: url({{ $new_logo->temporaryUrl() }}); width: 120px; height: 120px; background-size: contain;"></span>
                                    @elseif ($logo)
                                        <span class="avatar avatar-xl shadow-sm"
                                            style="background-image: url({{ asset('storage/' . $logo) }}); width: 120px; height: 120px; background-size: contain;"></span>
                                    @else
                                        <div class="avatar avatar-xl shadow-sm bg-white text-muted" style="width: 120px; height: 120px;">
                                            <span class="material-symbols-outlined" style="font-size: 48px;">image_not_supported</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="mt-3">
                                    <input type="file" class="form-control @error('new_logo') is-invalid @enderror"
                                        wire:model="new_logo" accept="image/*">
                                    <div class="form-hint text-muted mt-2 small">Format: JPG, PNG. Maksimal 2MB.</div>
                                    @error('new_logo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 text-end mt-3">
                <button type="submit" class="btn btn-primary px-4">
                    <span class="material-symbols-outlined me-2">save</span>
                    Simpan Pengaturan
                </button>
            </div>
        </div>
    </form>
</div>
