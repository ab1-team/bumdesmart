<div>
    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between mb-3">
                <div class="col-md-3">
                    <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                        placeholder="🔍 Cari no. retur, no. penjualan, atau status...">
                </div>
            </div>

            <x-table :headers="$headers" :results="$salesReturn" :sortColumn="$sortBy" :sortDirection="$sortDirection">
                @forelse ($salesReturn as $retur)
                    <tr>
                        <td>
                            {{ $loop->iteration + ($salesReturn->currentPage() - 1) * $salesReturn->perPage() }}
                        </td>
                        <td>
                            {{ date('Y-m-d', strtotime($retur->tanggal_return)) }}
                        </td>
                        <td>{{ $retur->no_return }}</td>
                        <td>
                            <a href="#" wire:click="detailPenjualan({{ $retur->sale_id }})">
                                {{ $retur->sale->no_invoice }}
                            </a>
                        </td>
                        <td>
                            @if ($retur->status == 'approved')
                                <span class="badge text-light bg-success">Disetujui</span>
                            @elseif ($retur->status == 'pending')
                                <span class="badge text-light bg-warning">Pending</span>
                            @elseif ($retur->status == 'rejected')
                                <span class="badge text-light bg-danger">Ditolak</span>
                            @endif
                        </td>
                        <td>{{ number_format($retur->total_return, 0, ',', '.') }}</td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-info dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false" data-bs-boundary="viewport" data-bs-popper-config='{"strategy":"fixed"}'>
                                    <span class="material-symbols-outlined">
                                        more_vert
                                    </span>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="#"
                                        wire:click="detailReturPenjualan({{ $retur->id }})">
                                        <span class="material-symbols-outlined me-2">visibility</span> Detail Retur Penjualan
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" href="#"
                                        wire:click="$dispatch('confirm-delete', {id: {{ $retur->id }}})">
                                        <span class="material-symbols-outlined me-2">delete</span> Hapus
                                    </a>
                                </div>
                            </div>
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

    @include('livewire.daftar-penjualan-component.modal-penjualan')
    @include('livewire.daftar-retur-penjualan-component.detail-retur')
</div>
