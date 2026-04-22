<div>
    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between mb-3">
                <div class="col-md-3">
                    <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                        placeholder="🔍 Cari user...">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100" wire:click="create">
                        <i class="fas fa-plus"></i> Tambah User
                    </button>
                </div>
            </div>

            <x-table :headers="$headers" :results="$users" :sortColumn="$sortBy" :sortDirection="$sortDirection">
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $loop->iteration + ($users->currentPage() - 1) * $users->perPage() }}</td>
                        <td>{{ $user->nama_lengkap }}</td>
                        <td>{{ $user->no_hp }}</td>
                        <td>{{ $user->role->nama_role }}</td>
                        <td>
                            @if ($user->role->nama_role != 'owner')
                                <button class="btn btn-sm btn-primary" wire:click="edit({{ $user->id }})">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-danger"
                                    wire:click="$dispatch('confirm-delete', {id: {{ $user->id }}})">
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

    <div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ $titleModal }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" wire:model="namaLengkap"
                                    placeholder="Nama Lengkap" />
                                @error('namaLengkap')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Role</label>
                                <select class="form-select tom-select" id="role" wire:model="role">
                                    <option value=""></option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->nama_role }}</option>
                                    @endforeach
                                </select>
                                @error('role')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Inisial</label>
                                <input type="text" class="form-control" wire:model="inisial" placeholder="Inisial" />
                                @error('inisial')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">No HP.</label>
                                <input type="number" class="form-control" wire:model="noHp" placeholder="No HP" />
                                @error('noHp')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" wire:model="username"
                                    placeholder="Username" />
                                @error('username')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" wire:model="password"
                                    placeholder="Password" />
                                @error('password')
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

@section('script')
    <script>
        document.addEventListener('livewire:navigated', () => {
            initTomSelect();
        });

        window.addEventListener('show-modal', event => {
            if (event.detail.modalId === 'userModal') {
                setTimeout(() => {
                    initTomSelect();
                }, 100);
            }
        });

        function initTomSelect() {
            document.querySelectorAll('.tom-select').forEach(el => {
                if (el.tomselect) return;

                new TomSelect(el, {
                    plugins: ['dropdown_input'],
                    onChange: function(value) {
                        @this.set(el.getAttribute('wire:model'), value);
                    }
                });
            });
        }

        // Handle Livewire updates
        document.addEventListener('livewire:load', () => {
            initTomSelect();
        });

        document.addEventListener('DOMContentLoaded', () => {
            initTomSelect();
        });
    </script>
@endsection
