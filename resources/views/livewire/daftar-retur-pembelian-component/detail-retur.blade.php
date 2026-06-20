<div class="modal fade" id="detailReturModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detail Retur Pembelian</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if (!empty($detailRetur))
                    <ul class="list-group ">
                        <li class="list-group-item border-0 p-2 ps-0 pt-0">
                            <strong>Tanggal :</strong>
                            <span>
                                {{ $detailRetur->tanggal_return }}
                            </span>
                        </li>
                        <li class="list-group-item border-0 p-2 ps-0 pt-0">
                            <strong>No. Retur :</strong>
                            <span>{{ $detailRetur->no_return }}</span>
                        </li>
                        <li class="list-group-item border-0 p-2 ps-0 pt-0">
                            <strong>Status :</strong>
                            <span>
                                @if ($detailRetur->status == 'approved')
                                    <span class="badge text-light bg-success">Disetujui</span>
                                @elseif ($detailRetur->status == 'pending')
                                    <span class="badge text-light bg-warning">Pending</span>
                                @elseif ($detailRetur->status == 'rejected')
                                    <span class="badge text-light bg-danger">Ditolak</span>
                                @endif
                            </span>
                        </li>
                    </ul>

                    <div class="row justify-content-between mt-3">
                        <div class="col-md-3">
                            <div class="fw-bold">Supplier :</div>
                            <div>{{ $detailRetur->purchase->supplier->nama_supplier }}</div>
                            <div>{{ $detailRetur->purchase->supplier->no_hp }}</div>
                            <div>{{ $detailRetur->purchase->supplier->alamat }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="fw-bold">Usaha :</div>
                            <div>{{ $detailRetur->business->nama_usaha }}</div>
                            <div>{{ $detailRetur->business->alamat }}</div>
                        </div>
                    </div>
                    <table class="table table-bordered mt-2">
                        <thead>
                            <tr>
                                <th width="5%">No.</th>
                                <th width="25%">Nama Produk</th>
                                <th width="15%">Harga Satuan</th>
                                <th width="10%">Jumlah</th>
                                <th width="15%">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($detailRetur->purchasesReturnDetails as $purchasesReturnDetails)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>{{ $purchasesReturnDetails->product->nama_produk }}</td>
                                    <td class="text-end">{{ number_format($purchasesReturnDetails->harga_satuan, 2, ',', '.') }}</td>
                                    <td class="text-center">{{ $purchasesReturnDetails->jumlah }}</td>
                                    <td class="text-end">{{ number_format($purchasesReturnDetails->sub_total, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-3 fw-bold">Alasan Retur :</div>
                    <div class="px-3 py-2 border rounded">
                        {{ $detailRetur->alasan_return != '' ? $detailRetur->alasan_return : '-' }}</div>
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
