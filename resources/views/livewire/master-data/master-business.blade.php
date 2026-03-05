<div>
    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between mb-3">
                <div class="col-md-3">
                    <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                        placeholder="🔍 Cari business/owner...">
                </div>
                <div class="col-md-3">
                    @if (count($ownersList) > 0)
                        <button class="btn btn-primary w-100" wire:click="create">
                            <i class="fas fa-plus"></i> Tambah Business
                        </button>
                    @else
                        <button class="btn btn-secondary w-100" disabled title="Tambahkan owner terlebih dahulu">
                            <i class="fas fa-plus"></i> Tambah Business
                        </button>
                    @endif
                </div>
            </div>

            @if (count($ownersList) === 0)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Belum ada owner terdaftar. <a href="/master/owner">Tambah owner terlebih dahulu</a> sebelum membuat
                    business.
                </div>
            @endif

            <x-table :headers="$headers" :results="$businesses" :sortColumn="$sortBy" :sortDirection="$sortDirection">
                @forelse ($businesses as $business)
                    <tr>
                        <td>{{ $loop->iteration + ($businesses->currentPage() - 1) * $businesses->perPage() }}</td>
                        <td>{{ optional($business->owner)->nama_usaha }}</td>
                        <td>{{ $business->nama_usaha }}</td>
                        <td>{{ $business->email }}</td>
                        <td>{{ $business->no_telp }}</td>
                        <td>{{ $business->alamat }}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" wire:click="edit({{ $business->id }})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger"
                                wire:click="$dispatch('confirm-delete', {id: {{ $business->id }}})">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
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

    <!-- Modal Form -->
    <div class="modal fade" id="masterBusinessModal" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ $titleModal }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="store">
                        <div class="row">

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Owner / Principal <span class="text-danger">*</span></label>
                                <select class="form-select" wire:model="ownerId">
                                    <option value="">-- Pilih Owner --</option>
                                    @foreach ($ownersList as $owId => $owName)
                                        <option value="{{ $owId }}">{{ $owName }}</option>
                                    @endforeach
                                </select>
                                @error('ownerId')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Business (Toko/Usaha) <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model="businessName"
                                    placeholder="Nama Business" />
                                @error('businessName')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" wire:model="email" placeholder="Email" />
                                @error('email')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">No. Telepon / HP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model="phone"
                                    placeholder="No HP/Telp" />
                                @error('phone')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                                <textarea class="form-control" wire:model="address" placeholder="Alamat lengkap" rows="3"></textarea>
                                @error('address')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary ms-auto" wire:click="store">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
