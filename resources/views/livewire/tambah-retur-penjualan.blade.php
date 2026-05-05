<div wire:ignore x-data="returPenjualanHandler()" x-init="initData(@js($sale))" @reset-form.window="resetForm">
    <div class="card mb-3">
        <div class="card-body">
            <ul class="list-group ">
                <li class="list-group-item border-0 p-2 ps-0 pt-0">
                    <strong>Tanggal :</strong>
                    <span>
                        {{ date('Y-m-d', strtotime($sale->tanggal_transaksi)) }}
                    </span>
                </li>
                <li class="list-group-item border-0 p-2 ps-0 pt-0">
                    <strong>No. Penjualan :</strong>
                    <span>{{ $sale->no_invoice }}</span>
                </li>
                <li class="list-group-item border-0 p-2 ps-0 pt-0">
                    <strong>Status :</strong>
                    <span>
                        @if ($sale->status == 'completed')
                            <span class="badge text-light bg-success">Selesai</span>
                        @elseif ($sale->status == 'partial')
                            <span class="badge text-light bg-warning">Sebagian</span>
                        @elseif ($sale->status == 'pending')
                            <span class="badge text-light bg-danger">Pending</span>
                        @endif
                    </span>
                </li>
            </ul>

            <div class="row justify-content-between mt-3">
                <div class="col-md-3">
                    <div class="fw-bold">Pelanggan :</div>
                    <div>{{ $sale->customer->nama_pelanggan }}</div>
                    <div>{{ $sale->customer->no_hp }}</div>
                    <div>{{ $sale->customer->alamat }}</div>
                </div>
                <div class="col-md-3">
                    <div class="fw-bold">Usaha :</div>
                    <div>{{ $sale->business->nama_usaha }}</div>
                    <div>{{ $sale->business->alamat }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="table-responsive mb-3">
                <table class="table table-vcenter table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="25%">Nama Produk</th>
                            <th width="15%">Harga Satuan</th>
                            <th width="10%">Jumlah Pembelian</th>
                            <th width="10%">Diskon</th>
                            <th width="10%">Cashback</th>
                            <th width="10%">Jumlah Retur</th>
                            <th width="15%">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(saleDetail, index) in Object.values(sale.sale_details)"
                            :key="index">
                            <tr>
                                <td x-text="index + 1"></td>
                                <td>
                                    <div x-text="saleDetail.product.nama_produk"></div>
                                    <div x-text="saleDetail.product.sku"></div>
                                </td>
                                <td>
                                    <div x-text="formatRupiah(saleDetail.harga_satuan)"></div>
                                </td>
                                <td>
                                    <div x-text="saleDetail.jumlah"></div>
                                </td>
                                <td>
                                    <span x-text="formatRupiah(saleDetail.jumlah_diskon)"></span>
                                    <span x-show="saleDetail.jenis_diskon === 'persen'">%</span>
                                </td>
                                <td>
                                    <span x-text="formatRupiah(saleDetail.jumlah_cashback)"></span>
                                    <span x-show="saleDetail.jenis_cashback === 'persen'">%</span>
                                </td>
                                <td>
                                    <input type="number" class="form-control"
                                        x-model="saleDetail.sales_return_detail.jumlah"
                                        x-on:change="updateSubtotal(saleDetail)" x-on:focus="$el.select()"
                                        :max="saleDetail.jumlah">
                                </td>
                                <td>
                                    <span
                                        x-text="formatRupiah(saleDetail.harga_satuan * saleDetail.sales_return_detail.jumlah)"></span>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="Object.keys(sale.sale_details).length === 0">
                            <td colspan="8" class="text-center text-muted py-4">
                                <i>Belum ada produk yang dipilih</i>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mb-3">
                <label class="form-label">Alasan Retur</label>
                <textarea class="form-control" rows="3" x-model="alasanRetur" placeholder="Alasan retur..."></textarea>
            </div>

            <div class="d-flex justify-content-end">
                <button class="btn btn-primary btn-lg mt-3" x-on:click="saveAll" :disabled="isLoading">
                    <span x-show="!isLoading">Simpan</span>
                    <span x-show="isLoading" class="spinner-border spinner-border-sm" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@section('script')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('returPenjualanHandler', () => ({
                sale: {},
                alasanRetur: '',
                isLoading: false,
                initData(data) {
                    this.sale = data;

                    this.sale.sale_details = this.sale.sale_details.map((
                        saleDetail) => {
                        if (!saleDetail.sales_return_detail) {
                            saleDetail.sales_return_detail = {
                                jumlah: 0,
                            };
                        }

                        return {
                            ...saleDetail,
                        };
                    });
                },

                saveAll() {
                    this.isLoading = true;

                    let totalRetur = 0;
                    const returnPenjualan = [];
                    this.sale.sale_details.filter((saleDetail) => {
                        if (saleDetail.sales_return_detail.jumlah > 0) {
                            returnPenjualan.push({
                                sale_detail_id: saleDetail.id,
                                product_id: saleDetail.product_id,
                                harga_satuan: saleDetail.harga_satuan,
                                jumlah: saleDetail.sales_return_detail.jumlah,
                                subtotal_retur: saleDetail.sales_return_detail
                                    .jumlah *
                                    saleDetail.harga_satuan,
                            });

                            totalRetur += saleDetail.sales_return_detail
                                .jumlah * saleDetail.harga_satuan;
                        }
                    });

                    if (returnPenjualan.length === 0) {
                        Toast.fire({
                            icon: 'error',
                            title: 'Tidak ada produk yang dipilih',
                        });

                        this.isLoading = false;
                        return;
                    }

                    const data = {
                        sale_id: this.sale.id,
                        total_retur: totalRetur,
                        alasan_retur: this.alasanRetur,
                        retur_penjualan: returnPenjualan,
                    };

                    @this.call('saveAll', data)
                        .then(() => {
                            this.isLoading = false;
                        })
                        .catch(err => {
                            this.isLoading = false;
                            console.error(err);
                            alert('Gagal menyimpan transaksi');
                        });
                },
                resetForm() {
                    this.alasanRetur = '';
                    this.sale = {};
                    this.isLoading = false;
                    this.saleDetails = [];
                    this.totalRetur = 0;
                },
                updateSubtotal(saleDetail) {
                    const max = saleDetail.jumlah;
                    if (saleDetail.sales_return_detail.jumlah > max) {
                        saleDetail.sales_return_detail.jumlah = max;

                        Swal.fire({
                            icon: 'warning',
                            title: 'Peringatan',
                            text: "Jumlah retur melebihi jumlah pembelian (" + max + ")"
                        })
                    }
                },
                formatRupiah(num) {
                    if (num === null || num === undefined || num === '') return '0';
                    let val = (typeof num === 'string') ? this.parseFormatted(num) : num;
                    return new Intl.NumberFormat('id-ID', {
                        maximumFractionDigits: 2,
                        minimumFractionDigits: 0
                    }).format(val);
                },

                parseFormatted(val) {
                    if (typeof val === 'number') return val;
                    if (!val) return 0;
                    let str = String(val).trim();
                    
                    // If the string has both dot and comma (e.g., 1.234,56)
                    if (str.includes('.') && str.includes(',')) {
                        return parseFloat(str.replace(/\./g, '').replace(/,/g, '.')) || 0;
                    }
                    
                    // If it only has a comma, it's definitely a decimal separator in ID format
                    if (str.includes(',')) {
                        return parseFloat(str.replace(/,/g, '.')) || 0;
                    }
                    
                    // If it only has a dot:
                    if (str.includes('.')) {
                        let parts = str.split('.');
                        // If it looks like a decimal (e.g., 2800.00 from DB), keep the dot
                        if (parts[parts.length - 1].length !== 3) {
                            return parseFloat(str) || 0;
                        }
                        // Otherwise, treat as thousands and remove
                        return parseFloat(str.replace(/\./g, '')) || 0;
                    }
                    
                    return parseFloat(str) || 0;
                },
            }))
        })
    </script>
@endsection
