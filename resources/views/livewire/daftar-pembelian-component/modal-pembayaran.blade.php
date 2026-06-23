<div class="modal fade" id="detailPembayaranModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detail Pembayaran</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if (!empty($detailPurchase))
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tanggal</th>
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
                                    <td>
                                        @if ($payment->rekening_debit == '1.1.03.01')
                                            <span class="badge text-light bg-success">Cash</span>
                                        @elseif ($payment->rekening_debit == '2.1.01.01')
                                            <span class="badge text-light bg-warning">Transfer</span>
                                        @endif
                                    </td>
                                    <td>{{ $payment->no_referensi }}</td>
                                    <td>{{ number_format($payment->total_harga, 2, ',', '.') }}</td>
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
                    </table>
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
