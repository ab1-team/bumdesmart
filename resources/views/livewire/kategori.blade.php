<div>
    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between mb-3">
                <div class="col-md-3">
                    <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                        placeholder="🔍 Cari kategori...">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100" wire:click="create">
                        <i class="fas fa-plus"></i> Tambah Kategori
                    </button>
                </div>
            </div>

            <x-table :headers="$headers" :results="$categories" :sortColumn="$sortBy" :sortDirection="$sortDirection">
                @forelse ($categories as $category)
                    <tr>
                        <td>
                            {{ $loop->iteration + ($categories->currentPage() - 1) * $categories->perPage() }}
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <span class="material-symbols-outlined">
                                    {{ $category->icon }}
                                </span>
                                <span>{{ $category->nama_kategori }}</span>
                            </div>
                        </td>
                        <td>{{ $category->deskripsi }}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" wire:click="edit({{ $category->id }})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger"
                                wire:click="$dispatch('confirm-delete', {id: {{ $category->id }}})">
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

    <div class="modal fade" id="kategoriModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ $titleModal }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Nama Kategori</label>
                                <input type="text" class="form-control" wire:model="namaKategori"
                                    placeholder="Nama Kategori" />
                                @error('namaKategori')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Icon</label>
                                <div wire:ignore>
                                    <select class="form-select tom-select select-icon" id="icon" wire:model="icon">
                                        <option value=""></option>
                                        @foreach ($icons as $icon)
                                            <option value="{{ $icon }}">
                                                {{ ucwords(str_replace('_', ' ', $icon)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('icon')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Deskripsi</label>
                                <textarea class="form-control" rows="3" wire:model="deskripsi" placeholder="Deskripsi"></textarea>
                                @error('deskripsi')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary ms-auto" data-bs-dismiss="modal" wire:click="store">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
