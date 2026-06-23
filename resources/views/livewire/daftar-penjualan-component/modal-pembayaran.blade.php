<div class="modal fade" id="detailPembayaranModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detail Pembayaran</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if (!empty($detailSale))
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
                        <tbody>
                            @foreach ($paymentList as $payment)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $payment['tanggal_pembayaran'] }}</td>
                                    <td>
                                        @if ($payment['metode_pembayaran'] == 'cash')
                                            <span class="badge text-light bg-success">Tunai</span>
                                        @elseif ($payment['metode_pembayaran'] == 'transfer')
                                            <span class="badge text-light bg-warning">Transfer</span>
                                        @else
                                            <span
                                                class="badge text-light bg-secondary">{{ $payment['metode_pembayaran'] }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $payment['no_referensi'] ?? '-' }}</td>
                                    <td>{{ number_format($payment['total_harga'], 2, ',', '.') }}</td>
                                    <td>
                                        <button class="btn btn-danger btn-sm"
                                            x-on:click="deletePayment({{ $payment['id'] }})">
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
