<div>
    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between mb-3">
                <div class="col-md-4">
                    <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                        placeholder="Cari no atau jenis pembayaran...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Jenis Pembayaran</th>
                            <th>Tanggal Invoice</th>
                            <th>Tanggal Diterima</th>
                            <th>Tagihan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoices as $invoice)
                            <tr style="cursor:pointer;" onclick="window.open('/keuangan/invoice/{{ $invoice->id }}/cetak', '_blank')">
                                <td>{{ $invoice->jenis_pembayaran }}</td>
                                <td class="text-nowrap">
                                    {{ \Carbon\Carbon::parse($invoice->tanggal_invoice)->format('d/m/Y') }}
                                </td>
                                <td class="text-nowrap">
                                    {{ $invoice->tanggal_diterima ? \Carbon\Carbon::parse($invoice->tanggal_diterima)->format('d/m/Y') : '-' }}
                                </td>
                                <td class="fw-bold">
                                    Rp {{ number_format($invoice->tagihan, 0, ',', '.') }}
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
                                <td colspan="5" class="text-center py-4 text-muted">
                                    Tidak ada invoice ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</div>