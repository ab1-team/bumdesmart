<div>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="daftarStock">
            <div class="card">
                <div class="card-body">
                    <div class="row justify-content-between mb-3">
                        <div class="col-md-3">
                            <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                                placeholder="🔍 Cari No. Opname...">
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="{{ url('/stock/opname/tambah') }}" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus"
                                    width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M12 5l0 14"></path>
                                    <path d="M5 12l14 0"></path>
                                </svg>
                                Tambah Opname
                            </a>
                        </div>
                    </div>
                    <x-table :headers="$headers" :results="$opnames">
                        @forelse ($opnames as $opname)
                            <tr>
                                <td>
                                    {{ $loop->iteration + ($opnames->currentPage() - 1) * $opnames->perPage() }}
                                </td>
                                <td>{{ $opname->no_opname }}</td>
                                <td>{{ \Carbon\Carbon::parse($opname->tanggal_opname)->format('d/m/Y') }}</td>
                                <td>
                                    @if ($opname->status == 'draft')
                                        <span class="badge bg-secondary text-white">Draft</span>
                                    @elseif($opname->status == 'approved')
                                        <span class="badge bg-success text-white">Approved</span>
                                    @elseif($opname->status == 'rejected')
                                        <span class="badge bg-danger text-white">Rejected</span>
                                    @else
                                        <span class="badge bg-info text-white">{{ ucfirst($opname->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $opname->user->nama_lengkap ?? '-' }}</td>
                                <td>{{ $opname->catatan }}</td>
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
                                                wire:click="showDetail({{ $opname->id }})">
                                                Detail
                                            </a>
                                            <a class="dropdown-item" href="/keuangan/pelaporan/cetak?laporan=buktiStokOpname&id={{ $opname->id }}" target="_blank">
                                                Cetak Bukti SO
                                            </a>
                                            @if ($opname->status == 'draft')
                                                <a class="dropdown-item text-success" href="#"
                                                    wire:click="$dispatch('confirm-approve', {id: {{ $opname->id }}})">
                                                    Approve & Finalize
                                                </a>
                                                <a class="dropdown-item" href="{{ url('/stock/opname/edit/' . $opname->id) }}">
                                                    Edit
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item text-danger" href="#"
                                                    wire:click="$dispatch('confirm-delete', {id: {{ $opname->id }}})">
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
                                    <p>Tidak ada data stock opname</p>
                                </td>
                            </tr>
                        @endforelse
                    </x-table>
                </div>
            </div>
        </div>
    </div>
    @include('livewire.stock-opname-component.modal-detail-opname')
</div>
@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const _enforceFocus = bootstrap.Modal.prototype._enforceFocus
            bootstrap.Modal.prototype._enforceFocus = function() {}
        })

        window.addEventListener('confirm-approve', event => {
            Swal.fire({
                title: 'Apakah anda yakin?',
                text: "Stock opname akan difinalisasi dan stok produk akan diperbarui!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Approve!'
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.dispatch('approve-confirmed', {
                        id: event.detail.id
                    });
                }
            })
        })
    </script>
@endsection
