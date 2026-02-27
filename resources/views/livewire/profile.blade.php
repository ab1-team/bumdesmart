<div>
    <div class="row row-cards">
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body text-center py-5">
                    <span class="avatar avatar-xl mb-4 rounded-circle shadow-sm"
                        style="width: 120px; height: 120px;
                        @if ($foto) background-image: url({{ $foto->temporaryUrl() }})
                        @elseif($fotoPath) background-image: url({{ asset('storage/' . $fotoPath) }}) @endif">
                        @if (!$foto && !$fotoPath)
                            <span style="font-size: 2.5rem;">{{ strtoupper(substr($nama_lengkap, 0, 2)) }}</span>
                        @endif
                    </span>
                    <h3 class="m-0 mb-1">{{ $nama_lengkap }}</h3>
                    <div class="text-muted mb-4">{{ '@' . $username }}</div>

                    <div>
                        <input type="file" id="foto-upload" wire:model="foto" class="d-none" accept="image/*">
                        <label for="foto-upload" class="btn btn-outline-primary btn-pill">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                <path d="M13.5 6.5l4 4" />
                            </svg>
                            Ubah Foto
                        </label>
                        @error('foto')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                        <div wire:loading wire:target="foto" class="mt-2 text-muted small">Mengunggah...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-8">
            <form wire:submit.prevent="updateProfile" class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Dasar</h3>
                </div>
                <div class="card-body">
                    @if (session()->has('success'))
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <div class="d-flex">
                                <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M5 12l5 5l10 -10"></path>
                                    </svg>
                                </div>
                                <div>
                                    {{ session('success') }}
                                </div>
                            </div>
                            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                        </div>
                    @endif

                    <div class="row row-cards">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Nama Lengkap</label>
                                <input type="text" class="form-control @error('nama_lengkap') is-invalid @enderror"
                                    wire:model="nama_lengkap" placeholder="Masukkan nama lengkap">
                                @error('nama_lengkap')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Username</label>
                                <input type="text" class="form-control @error('username') is-invalid @enderror"
                                    wire:model="username" placeholder="Masukkan username">
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nomor HP</label>
                                <input type="text" class="form-control @error('no_hp') is-invalid @enderror"
                                    wire:model="no_hp" placeholder="Contoh: 08123456789">
                                @error('no_hp')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6"></div>
                    </div>

                    <h3 class="card-title mt-4 border-bottom py-2">Keamanan Akun</h3>
                    <div class="row row-cards">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Password Baru</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                    wire:model="password" placeholder="Kosongkan jika tidak diubah">
                                <small class="form-hint">Kosongkan kolom ini jika Anda tidak ingin mengubah
                                    password.</small>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" class="form-control" wire:model="password_confirmation"
                                    placeholder="Ulangi Password Baru">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end bg-transparent">
                    <button type="submit" class="btn btn-primary">
                        <span wire:loading.remove wire:target="updateProfile">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24"
                                height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" />
                                <path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                <path d="M14 4l0 4l-6 0l0 -4" />
                            </svg>
                            Simpan Perubahan
                        </span>
                        <span wire:loading wire:target="updateProfile">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
