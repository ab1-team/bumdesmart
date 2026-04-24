<div>
    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between mb-3">
                <div class="col-md-3">
                    <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                        placeholder="🔍 Cari no. penjualan, tanggal, pelanggan, atau status...">
                </div>
                <div class="col-md-3">
                    <a href="/penjualan/tambah" class="btn btn-primary w-100">
                        <i class="fas fa-plus"></i> Tambah Penjualan
                    </a>
                </div>
            </div>

            <x-table :headers="$headers" :results="$sales" :sortColumn="$sortBy" :sortDirection="$sortDirection">
                @forelse ($sales as $sale)
                    <tr>
                        <td>{{ $loop->iteration + ($sales->currentPage() - 1) * $sales->perPage() }}</td>
                        <td>{{ $sale->no_invoice }}</td>
                        <td>
                            {{ date('Y-m-d', strtotime($sale->tanggal_transaksi)) }}
                        </td>
                        <td>{{ $sale->customer->nama_pelanggan }}</td>
                        <td>
                            @if ($sale->status == 'completed')
                                <span class="badge text-light bg-success">Selesai</span>
                            @elseif ($sale->status == 'partial')
                                <span class="badge text-light bg-warning">Sebagian</span>
                            @elseif ($sale->status == 'pending')
                                <span class="badge text-light bg-danger">Pending</span>
                            @endif
                        </td>
                        <td>{{ number_format($sale->total) }}</td>
                        <td>{{ number_format($sale->dibayar) }}</td>
                        <td>{{ number_format(max(0, $sale->total - $sale->dibayar)) }}</td>
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
                                        wire:click="detailPenjualan({{ $sale->id }})">
                                        Detail Penjualan
                                    </a>
                                    <a class="dropdown-item" href="/penjualan/cetak-struk/{{ $sale->id }}"
                                        target="_blank">
                                        Cetak Struk
                                    </a>
                                    <a class="dropdown-item" href="/penjualan/edit/{{ $sale->id }}">
                                        Edit
                                    </a>

                                    <a class="dropdown-item" href="#"
                                        wire:click="lihatPembayaran({{ $sale->id }})">
                                        Lihat Pembayaran
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    @if ($sale->total - $sale->payments->sum('total_harga') > 0)
                                        <a class="dropdown-item" href="#"
                                            wire:click="tambahPembayaran({{ $sale->id }})">
                                            Tambahkan Pembayaran
                                        </a>
                                    @endif
                                    <a class="dropdown-item" href="/penjualan/retur/{{ $sale->id }}">
                                        Retur Penjualan
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" href="#"
                                        wire:click="$dispatch('confirm-delete', {id: {{ $sale->id }}})">
                                        Hapus
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
    @include('livewire.daftar-penjualan-component.modal-pembayaran')
    @include('livewire.daftar-penjualan-component.modal-tambah-pembayaran')
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
