<div class="modal fade" id="detailProdukModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detail Produk</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if ($detailProduk)
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-center">
                                        <img src="{{ asset('storage/' . $detailProduk->gambar) }}" alt="Gambar"
                                            class="img-fluid">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8 mb-3">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h4 class="card-title">{{ $detailProduk->nama_produk }}</h4>

                                    <table class="table">
                                        <tr>
                                            <td class="fw-bold" width="25%">Kode Produk</td>
                                            <td width="1%">:</td>
                                            <td>{{ $detailProduk->sku }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Kategori</td>
                                            <td>:</td>
                                            <td>{{ $detailProduk->category->nama_kategori }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Merek</td>
                                            <td>:</td>
                                            <td>{{ $detailProduk->brand->nama_brand }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Rak Penyimpanan</td>
                                            <td>:</td>
                                            <td>
                                                {{ $detailProduk->shelf->nama_rak ?? 'Tidak ada' }}
                                                ({{ $detailProduk->shelf->lokasi ?? 'Tidak ada' }})
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Harga Pembelian</td>
                                            <td>:</td>
                                            <td>Rp. {{ number_format($detailProduk->harga_beli, 2, ',','.') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Stok</td>
                                            <td>:</td>
                                            <td>{{ $detailProduk->stok_aktual }}
                                                {{ $detailProduk->unit->nama_satuan }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <table class="table">
                                        <tr>
                                            <td class="fw-bold" width="35%">Harga Jual Default</td>
                                            <td width="1%">:</td>
                                            <td>Rp. {{ number_format($detailProduk->harga_jual, 2, ',','.') }}</td>
                                        </tr>
                                        @foreach ($this->customerGroups as $customerGroup)
                                            @php
                                                $harga_jual =
                                                    $detailProduk->harga_jual -
                                                    ($detailProduk->harga_jual * $customerGroup->diskon_persen) / 100;
                                                foreach ($detailProduk->productPrices as $productPrice) {
                                                    if ($productPrice->customer_group_id == $customerGroup->id) {
                                                        $harga_jual = $productPrice->harga_spesial;
                                                    }
                                                }
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="fw-bold">
                                                        Harga Member {{ $customerGroup->nama_group }}
                                                    </div>
                                                    <div class="d-block text-secondary text-truncate mt-n1">
                                                        Default Diskon {{ $customerGroup->diskon_persen }}%
                                                    </div>
                                                </td>
                                                <td>:</td>
                                                <td>Rp. {{ number_format($harga_jual, 2, ',','.') }}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
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
