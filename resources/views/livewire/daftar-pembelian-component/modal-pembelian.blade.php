<div class="modal fade" id="detailPembelianModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detail Pembelian</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if (!empty($detailPurchase))
                    <ul class="list-group ">
                        <li class="list-group-item border-0 p-2 ps-0 pt-0">
                            <strong>Tanggal :</strong>
                            <span>
                                {{ date('Y-m-d', strtotime($detailPurchase->tanggal_pembelian)) }}
                            </span>
                        </li>
                        <li class="list-group-item border-0 p-2 ps-0 pt-0">
                            <strong>No. Pembelian :</strong>
                            <span>{{ $detailPurchase->no_pembelian }}</span>
                        </li>
                        <li class="list-group-item border-0 p-2 ps-0 pt-0">
                            <strong>Status :</strong>
                            <span>
                                @if ($detailPurchase->status == 'completed')
                                    <span class="badge text-light bg-success">Selesai</span>
                                @elseif ($detailPurchase->status == 'partial')
                                    <span class="badge text-light bg-warning">Sebagian</span>
                                @elseif ($detailPurchase->status == 'pending')
                                    <span class="badge text-light bg-danger">Pending</span>
                                @endif
                            </span>
                        </li>
                    </ul>

                    <div class="row justify-content-between mt-3">
                        <div class="col-md-3">
                            <div class="fw-bold">Supplier :</div>
                            <div>{{ $detailPurchase->supplier->nama_supplier }}</div>
                            <div>{{ $detailPurchase->supplier->no_hp }}</div>
                            <div>{{ $detailPurchase->supplier->alamat }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="fw-bold">Usaha :</div>
                            <div>{{ $detailPurchase->business->nama_usaha }}</div>
                            <div>{{ $detailPurchase->business->alamat }}</div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered mt-2">
                        <thead>
                            <tr>
                                <th width="5%">No.</th>
                                <th width="25%">Nama Produk</th>
                                <th width="15%">Harga Satuan</th>
                                <th width="10%">Jumlah</th>
                                <th width="15%">Diskon</th>
                                <th width="15%">Cashback</th>
                                <th width="15%">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($detailPurchase->purchaseDetails as $purchaseDetail)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>{{ $purchaseDetail->product->nama_produk }}</td>
                                    <td class="text-end">{{ number_format($purchaseDetail->harga_satuan, 0, ',', '.') }}</td>
                                    <td class="text-center">{{ $purchaseDetail->jumlah }}</td>
                                    <td class="text-end">
                                        @if ($purchaseDetail->jenis_diskon == 'persen')
                                            {{ $purchaseDetail->jumlah_diskon }}%
                                        @else
                                            {{ number_format($purchaseDetail->jumlah_diskon, 0, ',', '.') }}
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($purchaseDetail->jenis_cashback == 'persen')
                                            {{ $purchaseDetail->jumlah_cashback }}%
                                        @else
                                            {{ number_format($purchaseDetail->jumlah_cashback, 0, ',', '.') }}
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($purchaseDetail->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" class="text-end fw-bold">Total</td>
                                <td class="text-end fw-bold">{{ number_format($detailPurchase->total, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="6" class="text-end fw-bold">Diskon</td>
                                <td class="text-end fw-bold">
                                    @if ($detailPurchase->jenis_diskon == 'persen')
                                        {{ $detailPurchase->jumlah_diskon }}%
                                    @else
                                        {{ number_format($detailPurchase->jumlah_diskon, 0, ',', '.') }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6" class="text-end fw-bold">Cashback</td>
                                <td class="text-end fw-bold">
                                    @if ($detailPurchase->jenis_cashback == 'persen')
                                        {{ $detailPurchase->jumlah_cashback }}%
                                    @else
                                        {{ number_format($detailPurchase->jumlah_cashback, 0, ',', '.') }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6" class="text-end fw-bold">Total Keseluruhan</td>
                                <td class="text-end fw-bold">{{ number_format($detailPurchase->total, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="6" class="text-end fw-bold">Total Dibayar</td>
                                <td class="text-end fw-bold">{{ number_format($detailPurchase->dibayar, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6" class="text-end fw-bold">Kembalian</td>
                                <td class="text-end fw-bold">
                                    {{ number_format($detailPurchase->kembalian, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                        </table>
                    </div>

                    <div class="mt-3 fw-bold">Catatan :</div>
                    <div class="px-3 py-2 border rounded">
                        {{ $detailPurchase->keterangan != '' ? $detailPurchase->keterangan : '-' }}</div>

                @endif
            </div>
            <div class="modal-footer">
                @if (!empty($detailPurchase))
                    <a href="/pembelian/cetak-nota/{{ $detailPurchase->id }}" target="_blank" class="btn btn-dark me-2">
                        <span class="material-symbols-outlined me-1">description</span> Cetak Nota (A5)
                    </a>
                @endif
                <button type="button" class="btn ms-auto" data-bs-dismiss="modal">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
