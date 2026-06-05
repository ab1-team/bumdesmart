<div>
    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between mb-3">
                <div class="col-md-4">
                    <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                        placeholder="🔍 Cari no. bayar, akun, atau catatan...">
                </div>
                <div class="col-md-6">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <input type="date" class="form-control" wire:model.live="startDate">
                        </div>
                        <div class="col-md-6">
                            <input type="date" class="form-control" wire:model.live="endDate">
                        </div>
                    </div>
                </div>
            </div>

            <x-table :headers="$headers" :results="$payments" :sortColumn="$sortBy" :sortDirection="$sortDirection">
                @forelse ($payments as $payment)
                    <tr>
                        <td class="text-nowrap">
                            {{ \Carbon\Carbon::parse($payment->tanggal_pembayaran)->format('d/m/Y') }}
                        </td>
                        <td class="text-nowrap">
                            <span class="badge bg-blue-lt">{{ $payment->no_pembayaran }}</span>
                            <div class="text-muted small">{{ $payment->jenis_transaksi }}</div>
                        </td>
                        <td>
                            <div class="fw-bold">{{ $payment->rekening_debit }}</div>
                            <div class="text-muted small">{{ $payment->accountDebit->nama ?? '-' }}</div>
                        </td>
                        <td>
                            <div class="fw-bold">{{ $payment->rekening_kredit }}</div>
                            <div class="text-muted small">{{ $payment->accountKredit->nama ?? '-' }}</div>
                        </td>
                        <td class="text-nowrap fw-bold">
                            Rp {{ number_format($payment->total_harga, 0, ',', '.') }}
                        </td>
                        <td>
                            {{ $payment->catatan }}
                        </td>
                        <td>
                            {{ $payment->user->nama_lengkap ?? '-' }}
                        </td>
                        <td>
                            <button x-data
                                x-on:click="Swal.fire({
                                    title: 'Hapus Transaksi?',
                                    text: 'Tindakan ini tidak dapat dibatalkan!',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#d33',
                                    cancelButtonColor: '#3085d6',
                                    confirmButtonText: 'Ya, Hapus!',
                                    cancelButtonText: 'Batal'
                                }).then((result) => { if (result.isConfirmed) { $wire.delete({{ $payment->id }}) } })"
                                class="btn btn-sm btn-outline-danger"
                                title="Hapus Transaksi">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            Tidak ada transaksi ditemukan.
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>
    </div>
</div>
