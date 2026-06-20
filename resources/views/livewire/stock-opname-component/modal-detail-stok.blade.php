<div class="modal fade" id="detailProdukModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    {{ $titleModal ?? 'Detail Produk' }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if ($stockdetail instanceof \App\Models\StockMovement)
                    <div class="card mb-4 border-primary">
                        <div class="card-body">
                            <h5 class="card-title text-primary">Informasi Perubahan Stok</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td class="fw-bold" width="30%">Tanggal Perubahan</td>
                                    <td width="1%">:</td>
                                    <td>
                                        {{ \Carbon\Carbon::parse($stockdetail->tanggal_perubahan_stok)->format('d-m-Y H:i') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Jenis Perubahan</td>
                                    <td>:</td>
                                    <td>
                                        <span class="badge text-light bg-primary">
                                            {{ $stockdetail->jenis_perubahan }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Jumlah Perubahan</td>
                                    <td>:</td>
                                    <td>
                                        {{ $stockdetail->jumlah_perubahan }}
                                        {{ $product?->unit?->nama_satuan }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Catatan</td>
                                    <td>:</td>
                                    <td>{{ $stockdetail->catatan ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                @endif

                @if ($product instanceof \App\Models\Product)
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <img
                                        src="{{ $product->gambar ? asset('storage/' . $product->gambar) : asset('images/no-image.png') }}"
                                        class="img-fluid rounded"
                                        alt="Gambar Produk">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8 mb-3">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h4>{{ $product->nama_produk }}</h4>

                                    <table class="table table-sm">
                                        <tr>
                                            <td class="fw-bold">SKU</td>
                                            <td>:</td>
                                            <td>{{ $product->sku }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Kategori</td>
                                            <td>:</td>
                                            <td>{{ $product->category?->nama_kategori ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Merek</td>
                                            <td>:</td>
                                            <td>{{ $product->brand?->nama_brand ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Rak</td>
                                            <td>:</td>
                                            <td>
                                                {{ $product->shelf?->nama_rak ?? '-' }}
                                                ({{ $product->shelf?->lokasi ?? '-' }})
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Harga Beli</td>
                                            <td>:</td>
                                            <td>Rp {{ number_format($product->harga_beli, 2, ',','.') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Stok Saat Ini</td>
                                            <td>:</td>
                                            <td>
                                                {{ $product->stok_aktual }}
                                                {{ $product->unit?->nama_satuan }}
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary ms-auto" data-bs-dismiss="modal">
                    Tutup
                </button>
            </div>

        </div>
    </div>
</div>
