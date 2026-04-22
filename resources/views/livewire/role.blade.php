<div>
    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between mb-3">
                <div class="col-md-3">
                    <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                        placeholder="🔍 Cari role atau deskripsi...">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100" wire:click="create">
                        <i class="fas fa-plus"></i> Tambah Role
                    </button>
                </div>
            </div>

            <x-table :headers="$headers" :results="$roles" :sortColumn="$sortBy" :sortDirection="$sortDirection">
                @forelse ($roles as $role)
                    <tr>
                        <td>{{ $loop->iteration + ($roles->currentPage() - 1) * $roles->perPage() }}</td>
                        <td>{{ $role->nama_role }}</td>
                        <td>{{ $role->deskripsi }}</td>
                        <td>
                            @if ($role->nama_role != 'owner')
                                <button class="btn btn-sm btn-primary" wire:click="edit({{ $role->id }})">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-danger"
                                    wire:click="$dispatch('confirm-delete', {id: {{ $role->id }}})">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            @endif
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

    <div class="modal fade" id="roleModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ $titleModal }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label class="form-label">Nama Role</label>
                            <input type="text" class="form-control" wire:model="namaRole" placeholder="Nama Role" />
                            @error('namaRole')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" rows="3" wire:model="deskripsi" placeholder="Deskripsi"></textarea>
                            @error('deskripsi')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <hr>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Akses Menu</label>
                            <div class="row">
                                @forelse ($availableMenus as $menu)
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="p-3 border rounded bg-light h-100">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    value="{{ $menu->id }}" wire:model="selectedMenus"
                                                    wire:click="toggleParent({{ $menu->id }})"
                                                    id="menu-{{ $menu->id }}">
                                                <label class="form-check-label fw-bold" for="menu-{{ $menu->id }}">
                                                    {{ $menu->title }}
                                                </label>
                                            </div>
                                            @if ($menu->children->count() > 0)
                                                <div class="ms-4 mt-2">
                                                    @foreach ($menu->children as $child)
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox"
                                                                value="{{ $child->id }}" wire:model="selectedMenus"
                                                                wire:click="toggleChild({{ $menu->id }})"
                                                                id="menu-{{ $child->id }}">
                                                            <label class="form-check-label"
                                                                for="menu-{{ $child->id }}">
                                                                {{ $child->title }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12 text-center text-muted py-3">
                                        <small>Data menu belum tersedia di database.</small>
                                    </div>
                                @endforelse
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
