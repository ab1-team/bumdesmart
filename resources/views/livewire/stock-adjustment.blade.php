<div>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="daftarStockAdjustment">
            <div class="card">
                <div class="card-body">

                    <div class="row justify-content-between mb-3">
                        <div class="col-md-3">
                            <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                                placeholder="🔍 Cari Stock Adjustment...">
                        </div>
                    </div>

                    <x-table :headers="$headers" :results="$adjustments">
                        @forelse ($adjustments as $adj)
                            <tr>
                                <td>
                                    {{ $loop->iteration + ($adjustments->currentPage() - 1) * $adjustments->perPage() }}
                                </td>
                                <td>{{ $adj->no_penyesuaian }}</td>
                                <td>{{ $adj->tanggal_penyesuaian }}</td>
                                <td>
                                    <span class="badge bg-blue text-blue-fg">{{ $adj->jenis_penyesuaian }}</span>
                                </td>
                                <td>
                                    @if ($adj->status == 'draft')
                                        <span class="badge bg-secondary text-secondary-fg">Draft</span>
                                    @elseif($adj->status == 'approved')
                                        <span class="badge bg-success text-success-fg">Approved</span>
                                    @else
                                        <span class="badge">{{ $adj->status }}</span>
                                    @endif
                                </td>
                                <td>{{ $adj->user->nama_lengkap ?? '-' }}</td>
                                <td>{{ $adj->catatan }}</td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-info dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" data-bs-boundary="viewport" data-bs-popper-config='{"strategy":"fixed"}'>
                                            Aksi
                                        </button>

                                        <div class="dropdown-menu">
                                            {{-- <a class="dropdown-item" href="#">Detail</a> --}}

                                            @if ($adj->status == 'draft')
                                                <a class="dropdown-item" href="#"
                                                    wire:click.prevent="$dispatch('alert', { type: 'info', message: 'Fitur Edit segera hadir' })">
                                                    Edit
                                                </a>
                                                <a class="dropdown-item text-success" href="#"
                                                    wire:click.prevent="$dispatch('approve-confirmed', { id: {{ $adj->id }} })">
                                                    Approve & Finalize
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item text-danger" href="#"
                                                    wire:click.prevent="$dispatch('confirm-delete', { id: {{ $adj->id }} })">
                                                    Hapus
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($headers) }}" class="text-center text-muted">
                                    <i class="fas fa-inbox fa-3x mb-2"></i>
                                    <p>Tidak ada data stock adjustment</p>
                                </td>
                            </tr>
                        @endforelse
                    </x-table>

                </div>
            </div>
        </div>
    </div>

    @include('livewire.stock-adjustment-component.modal-detail-stok')
    @include('livewire.stock-adjustment-component.modal-detail-stok')
    {{-- @include('livewire.stock-adjustment-component.modal-edit-stok') --}}
</div>

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const _enforceFocus = bootstrap.Modal.prototype._enforceFocus
            bootstrap.Modal.prototype._enforceFocus = function() {}
        })
    </script>
@endsection
