<div class="modal fade" id="detailPembayaranModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detail Pembayaran</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if (!empty($detailPurchase))
                    @if ($detailPurchase->payments->count() > 0)
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Tanggal</th>
                                    <th>No. Pembayaran</th>
                                    <th>Metode</th>
                                    <th>Rekening</th>
                                    <th>Jumlah</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($detailPurchase->payments as $payment)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $payment->tanggal_pembayaran }}</td>
                                        <td><small>{{ $payment->no_pembayaran }}</small></td>
                                        <td>
                                            @if ($payment->metode_pembayaran == 'cash')
                                                <span class="badge text-light bg-success">Tunai</span>
                                            @elseif ($payment->metode_pembayaran == 'transfer')
                                                <span class="badge text-light bg-warning">Transfer</span>
                                            @elseif ($payment->metode_pembayaran == 'qris')
                                                <span class="badge text-light bg-info">QRIS</span>
                                            @else
                                                <span class="badge text-light bg-secondary">{{ ucfirst($payment->metode_pembayaran) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $payment->no_referensi ?: '-' }}</td>
                                        <td>{{ \App\Utils\NumberUtil::format($payment->total_harga, 2, true) }}</td>
                                        <td>
                                            <button class="btn btn-danger btn-sm"
                                                x-on:click="deletePayment({{ $payment->id }})">
                                                <span class="material-symbols-outlined">
                                                    delete
                                                </span>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Total</td>
                                    <td class="fw-bold">{{ \App\Utils\NumberUtil::format($detailPurchase->payments->sum('total_harga'), 2, true) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-2"></i>
                            <p>Belum ada pembayaran untuk pembelian ini</p>
                        </div>
                    @endif
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn ms-auto" data-bs-dismiss="modal">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
