<div>
    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between mb-3">
                <div class="col-md-3">
                    <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                        placeholder="🔍 Cari no. pembelian, tanggal, supplier, atau status...">
                </div>
                <div class="col-md-3">
                    <a href="/pembelian/tambah" class="btn btn-primary w-100">
                        <i class="fas fa-plus"></i> Tambah Pembelian
                    </a>
                </div>
            </div>

            <x-table :headers="$headers" :results="$purchases" :sortColumn="$sortBy" :sortDirection="$sortDirection">
                @forelse ($purchases as $purchase)

                    <tr>
                        <td>{{ $loop->iteration + ($purchases->currentPage() - 1) * $purchases->perPage() }}</td>
                        <td>
                            <span>{{ $purchase->no_pembelian }}</span>
                            @if ($purchase->purchaseReturn->count() > 0)
                                <a href="/pembelian/daftar-retur?purchase_id={{ $purchase->id }}"
                                    class="badge text-light bg-danger">
                                    <span class="material-symbols-outlined">
                                        reset_tv
                                    </span>
                                </a>
                            @endif
                        </td>
                        <td>{{ $purchase->tanggal_pembelian }}</td>
                        <td>{{ $purchase->supplier->nama_supplier }}</td>
                        <td>
                            @if ($purchase->status == 'completed')
                                <span class="badge text-light bg-success">Selesai</span>
                            @elseif ($purchase->status == 'partial')
                                <span class="badge text-light bg-warning">Sebagian</span>
                            @elseif ($purchase->status == 'pending')
                                <span class="badge text-light bg-danger">Pending</span>
                            @endif
                        </td>
                        <td>{{ number_format($purchase->total, 0, ',', '.') }}</td>
                        <td>{{ number_format($purchase->dibayar, 0, ',', '.') }}</td>
                        <td>{{ number_format($purchase->jumlah_utang, 0, ',', '.') }}</td>
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
                                        wire:click="detailPembelian({{ $purchase->id }})">
                                        <span class="material-symbols-outlined me-2">visibility</span> Detail Pembelian
                                    </a>
                                    <a class="dropdown-item" href="/pembelian/edit/{{ $purchase->id }}">
                                        <span class="material-symbols-outlined me-2">edit</span> Edit
                                    </a>
                                    <a class="dropdown-item" href="/pembelian/cetak-nota/{{ $purchase->id }}" target="_blank">
                                        <span class="material-symbols-outlined me-2">print</span> Cetak Nota (A5)
                                    </a>

                                    <a class="dropdown-item" href="#"
                                        wire:click="lihatPembayaran({{ $purchase->id }})">
                                        <span class="material-symbols-outlined me-2">history</span> Lihat Pembayaran
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    @if ($purchase->jumlah_utang > 0)
                                        <a class="dropdown-item" href="#"
                                            wire:click="tambahPembayaran({{ $purchase->id }})">
                                            <span class="material-symbols-outlined me-2">payments</span> Tambahkan Pembayaran
                                        </a>
                                    @endif
                                    <a class="dropdown-item" href="/pembelian/retur/{{ $purchase->id }}">
                                        <span class="material-symbols-outlined me-2">undo</span> Retur Pembelian
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" href="#"
                                        wire:click="$dispatch('confirm-delete', {id: {{ $purchase->id }}})">
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

    @include('livewire.daftar-pembelian-component.modal-pembelian')
    @include('livewire.daftar-pembelian-component.modal-pembayaran')
    @include('livewire.daftar-pembelian-component.modal-tambah-pembayaran')
</div>

@section('script')
    <script>
        function deletePayment(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('deletePayment', {
                        id
                    });
                }
            });
        }
    </script>
@endsection
