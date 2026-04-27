<div class="modal fade" id="detailPenjualanModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detail Penjualan</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if (!empty($detailSale))
                    <ul class="list-group ">
                        <li class="list-group-item border-0 p-2 ps-0 pt-0">
                            <strong>Tanggal :</strong>
                            <span>
                                {{ date('Y-m-d', strtotime($detailSale->tanggal_transaksi)) }}
                            </span>
                        </li>
                        <li class="list-group-item border-0 p-2 ps-0 pt-0">
                            <strong>No. Invoice :</strong>
                            <span>{{ $detailSale->no_invoice }}</span>
                        </li>
                        <li class="list-group-item border-0 p-2 ps-0 pt-0">
                            <strong>Status :</strong>
                            <span>
                                @if ($detailSale->status == 'completed')
                                    <span class="badge text-light bg-success">Selesai</span>
                                @elseif ($detailSale->status == 'partial')
                                    <span class="badge text-light bg-warning">Sebagian</span>
                                @elseif ($detailSale->status == 'pending')
                                    <span class="badge text-light bg-danger">Pending</span>
                                @endif
                            </span>
                        </li>
                    </ul>

                    <div class="row justify-content-between mt-3">
                        <div class="col-md-3">
                            <div class="fw-bold">Customer :</div>
                            <div>{{ $detailSale->customer->nama_pelanggan }}</div>
                            <div>{{ $detailSale->customer->no_hp }}</div>
                            <div>{{ $detailSale->customer->alamat }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="fw-bold">Usaha :</div>
                            <div>{{ $detailSale->business->nama_usaha }}</div>
                            <div>{{ $detailSale->business->alamat }}</div>
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
                                @php
                                    $subtotal = 0;
                                @endphp
                                @foreach ($detailSale->saleDetails as $saleDetail)
                                    @php
                                        $subtotal += $saleDetail->subtotal;
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>{{ $saleDetail->product->nama_produk }}</td>
                                        <td class="text-end">{{ number_format($saleDetail->harga_satuan, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">{{ $saleDetail->jumlah }}</td>
                                        <td class="text-end">
                                            @if ($saleDetail->jenis_diskon == 'persen')
                                                {{ $saleDetail->jumlah_diskon }}%
                                            @else
                                                {{ number_format($saleDetail->jumlah_diskon, 0, ',', '.') }}
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if ($saleDetail->jenis_cashback == 'persen')
                                                {{ $saleDetail->jumlah_cashback }}%
                                            @else
                                                {{ number_format($saleDetail->jumlah_cashback, 0, ',', '.') }}
                                            @endif
                                        </td>
                                        <td class="text-end">{{ number_format($saleDetail->subtotal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-end fw-bold">Total</td>
                                    <td class="text-end fw-bold">{{ number_format($subtotal, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-end fw-bold">Diskon</td>
                                    <td class="text-end fw-bold">
                                        @if ($detailSale->jenis_diskon == 'persen')
                                            {{ $detailSale->jumlah_diskon }}%
                                        @else
                                            {{ number_format($detailSale->jumlah_diskon, 0, ',', '.') }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-end fw-bold">Cashback</td>
                                    <td class="text-end fw-bold">
                                        @if ($detailSale->jenis_cashback == 'persen')
                                            {{ $detailSale->jumlah_cashback }}%
                                        @else
                                            {{ number_format($detailSale->jumlah_cashback, 0, ',', '.') }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-end fw-bold">Total Keseluruhan</td>
                                    <td class="text-end fw-bold">{{ number_format($detailSale->total, 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-end fw-bold">Total Dibayar</td>
                                    <td class="text-end fw-bold">{{ number_format($detailSale->dibayar, 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-end fw-bold">Kembalian</td>
                                    <td class="text-end fw-bold">
                                        {{ number_format($detailSale->kembalian, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-3 fw-bold">Catatan :</div>
                    <div class="px-3 py-2 border rounded">
                        {{ $detailSale->keterangan != '' ? $detailSale->keterangan : '-' }}</div>

                @endif
            </div>
            <div class="modal-footer">
                @if (!empty($detailSale))
                    <a href="/penjualan/cetak-struk/{{ $detailSale->id }}" target="_blank" class="btn btn-info me-2">
                        <span class="material-symbols-outlined me-1">receipt</span> Cetak Struk
                    </a>
                    <a href="/penjualan/cetak-nota/{{ $detailSale->id }}" target="_blank" class="btn btn-dark me-2">
                        <span class="material-symbols-outlined me-1">description</span> Cetak Nota (A5)
                    </a>
                    <a href="/penjualan/cetak-surat-jalan/{{ $detailSale->id }}" target="_blank"
                        class="btn btn-primary me-2">
                        <span class="material-symbols-outlined me-1">local_shipping</span> Cetak Surat Jalan
                    </a>
                @endif
                <button type="button" class="btn ms-auto" data-bs-dismiss="modal">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
