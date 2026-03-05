<div>
    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between mb-3">
                <div class="col-md-3">
                    <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                        placeholder="🔍 Cari owner...">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100" wire:click="create">
                        <i class="fas fa-plus"></i> Tambah Owner
                    </button>
                </div>
            </div>

            <x-table :headers="$headers" :results="$owners" :sortColumn="$sortBy" :sortDirection="$sortDirection">
                @forelse ($owners as $owner)
                    <tr>
                        <td>{{ $loop->iteration + ($owners->currentPage() - 1) * $owners->perPage() }}</td>
                        <td>{{ $owner->nama_usaha }}</td>
                        <td>{{ $owner->tanggal_penggunaan }}</td>
                        <td>{{ $owner->domain ?? '-' }}</td>
                        <td>{{ $owner->domain_alternatif ?? '-' }}</td>
                        <td>
                            <span class="badge bg-blue-lt">{{ $owner->businesses_count }} business</span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" wire:click="edit({{ $owner->id }})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger"
                                wire:click="$dispatch('confirm-delete', {id: {{ $owner->id }}})">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($headers) }}" class="text-center text-muted">
                            <i class="fas fa-inbox fa-3x mb-2"></i>
                            <p>Tidak ada owner yang ditemukan</p>
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="masterOwnerModal" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ $titleModal }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="store">
                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Owner / Principal <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model="namaUsaha"
                                    placeholder="Nama owner atau penanggung jawab" />
                                @error('namaUsaha')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Mulai Penggunaan <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" wire:model="tanggalPenggunaan" />
                                @error('tanggalPenggunaan')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Domain Utama</label>
                                <input type="text" class="form-control" wire:model="domain"
                                    placeholder="contoh: toko-bumdes.com" />
                                @error('domain')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Domain Alternatif</label>
                                <input type="text" class="form-control" wire:model="domainAlternatif"
                                    placeholder="contoh: www.toko-bumdes.com (opsional)" />
                                @error('domainAlternatif')
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
