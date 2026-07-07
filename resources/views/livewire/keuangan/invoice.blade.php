<div>
    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between mb-3">
                <div class="col-md-4">
                    <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                        placeholder="Cari no atau jenis pembayaran...">
                </div>
                <div class="col-md-8">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <select class="form-select tom-select" id="filterJenisPembayaran"
                                wire:model.live="jenisPembayaran">
                                <option value="">Semua Pembayaran</option>
                                <option value="Biaya Lisensi Instalasi">Biaya Lisensi Instalasi</option>
                                <option value="Biaya Perpanjangan Maintenance dan Server">Biaya Perpanjangan Maintenance dan Server</option>
                                <option value="Biaya Bimbingan Teknis">Biaya Bimbingan Teknis</option>
                                <option value="Biaya Migrasi Ulang">Biaya Migrasi Ulang</option>
                                <option value="Biaya Aktivasi WA Gateway">Biaya Aktivasi WA Gateway</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select tom-select" id="filterStatusInvoice" wire:model.live="status">
                                <option value="">Semua Status</option>
                                <option value="PAID">PAID</option>
                                <option value="PARTIAL">PARTIAL</option>
                                <option value="UNPAID">UNPAID</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control litepicker" id="filterStartDate"
                                wire:model="startDate" placeholder="Dari tanggal" autocomplete="off">
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control litepicker" id="filterEndDate"
                                wire:model="endDate" placeholder="Sampai tanggal" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>

            <x-table :headers="$headers" :results="$invoices" :sortColumn="$sortBy" :sortDirection="$sortDirection">
                @forelse ($invoices as $invoice)
                    <tr style="cursor:pointer;" onclick="window.open('/keuangan/invoice/{{ $invoice->id }}/cetak', '_blank')">
                        <td class="text-center">
                            <span class="badge bg-blue-lt">{{ $invoice->no }}</span>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $invoice->jenis_pembayaran }}</span>
                        </td>
                        <td class="text-nowrap">
                            {{ \Carbon\Carbon::parse($invoice->tanggal_invoice)->format('d/m/Y') }}
                        </td>
                        <td class="text-nowrap fw-bold">
                            Rp {{ number_format($invoice->tagihan, 0, ',', '.') }}
                        </td>
                        <td class="text-nowrap">
                            Rp {{ number_format($invoice->saldo, 0, ',', '.') }}
                        </td>
                        <td>
                            @if ($invoice->status === 'PAID')
                                <span class="badge bg-success">PAID</span>
                            @elseif ($invoice->status === 'PARTIAL')
                                <span class="badge bg-info">PARTIAL</span>
                            @elseif ($invoice->status === 'UNPAID')
                                <span class="badge bg-warning">UNPAID</span>
                            @else
                                <span class="badge bg-secondary">{{ $invoice->status }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            Tidak ada invoice ditemukan.
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </div>
    </div>
</div>