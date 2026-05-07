<div wire:ignore x-data="returPembelianHandler()" x-init="initData(@js($purchase))" @reset-form.window="resetForm">
    <div class="card mb-3">
        <div class="card-body">
            <ul class="list-group ">
                <li class="list-group-item border-0 p-2 ps-0 pt-0">
                    <strong>Tanggal :</strong>
                    <span>
                        {{ $purchase->tanggal_pembelian }}
                    </span>
                </li>
                <li class="list-group-item border-0 p-2 ps-0 pt-0">
                    <strong>No. Pembelian :</strong>
                    <span>{{ $purchase->no_pembelian }}</span>
                </li>
                <li class="list-group-item border-0 p-2 ps-0 pt-0">
                    <strong>Status :</strong>
                    <span>
                        @if ($purchase->status == 'completed')
                            <span class="badge text-light bg-success">Selesai</span>
                        @elseif ($purchase->status == 'partial')
                            <span class="badge text-light bg-warning">Sebagian</span>
                        @elseif ($purchase->status == 'pending')
                            <span class="badge text-light bg-danger">Pending</span>
                        @endif
                    </span>
                </li>
            </ul>

            <div class="row justify-content-between mt-3">
                <div class="col-md-3">
                    <div class="fw-bold">Supplier :</div>
                    <div>{{ $purchase->supplier->nama_supplier }}</div>
                    <div>{{ $purchase->supplier->no_hp }}</div>
                    <div>{{ $purchase->supplier->alamat }}</div>
                </div>
                <div class="col-md-3">
                    <div class="fw-bold">Usaha :</div>
                    <div>{{ $purchase->business->nama_usaha }}</div>
                    <div>{{ $purchase->business->alamat }}</div>
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
                        <template x-for="(purchaseDetail, index) in Object.values(purchase.purchase_details)"
                            :key="index">
                            <tr>
                                <td x-text="index + 1"></td>
                                <td>
                                    <div x-text="purchaseDetail.product.nama_produk"></div>
                                    <div x-text="purchaseDetail.product.sku"></div>
                                </td>
                                <td>
                                    <div x-text="formatRupiah(purchaseDetail.harga_satuan)"></div>
                                </td>
                                <td>
                                    <div x-text="purchaseDetail.jumlah"></div>
                                </td>
                                <td>
                                    <span x-text="formatRupiah(purchaseDetail.jumlah_diskon)"></span>
                                    <span x-show="purchaseDetail.jenis_diskon === 'persen'">%</span>
                                </td>
                                <td>
                                    <span x-text="formatRupiah(purchaseDetail.jumlah_cashback)"></span>
                                    <span x-show="purchaseDetail.jenis_cashback === 'persen'">%</span>
                                </td>
                                <td>
                                    <input type="number" class="form-control"
                                        x-model="purchaseDetail.purchases_return_detail.jumlah"
                                        x-on:change="updateSubtotal(purchaseDetail)" x-on:focus="$el.select()"
                                        :max="purchaseDetail.product_batch.jumlah_saat_ini">
                                </td>
                                <td>
                                    <span
                                        x-text="formatRupiah(purchaseDetail.harga_satuan * purchaseDetail.purchases_return_detail.jumlah)"></span>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="Object.keys(purchase.purchase_details).length === 0">
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
            Alpine.data('returPembelianHandler', () => ({
                purchase: {},
                alasanRetur: '',
                isLoading: false,
                initData(data) {
                    this.purchase = data;

                    this.purchase.purchase_details = this.purchase.purchase_details.map((
                        purchaseDetail) => {
                        if (!purchaseDetail.purchases_return_detail) {
                            purchaseDetail.purchases_return_detail = {
                                jumlah: 0,
                            };
                        }

                        return {
                            ...purchaseDetail,
                        };
                    });
                },

                saveAll() {
                    this.isLoading = true;

                    let totalRetur = 0;
                    const returPembelian = [];
                    this.purchase.purchase_details.filter((purchaseDetail) => {
                        if (purchaseDetail.purchases_return_detail.jumlah > 0) {
                            returPembelian.push({
                                purchase_detail_id: purchaseDetail.id,
                                product_id: purchaseDetail.product_id,
                                product_batch_id: purchaseDetail.product_batch.id,
                                harga_satuan: purchaseDetail.harga_satuan,
                                jumlah: purchaseDetail.purchases_return_detail.jumlah,
                                subtotal_retur: purchaseDetail.purchases_return_detail
                                    .jumlah *
                                    purchaseDetail.harga_satuan,
                            });

                            totalRetur += purchaseDetail.purchases_return_detail
                                .jumlah * purchaseDetail.harga_satuan;
                        }
                    });

                    if (returPembelian.length === 0) {
                        Toast.fire({
                            icon: 'error',
                            title: 'Tidak ada produk yang dipilih',
                        });

                        this.isLoading = false;
                        return;
                    }

                    const data = {
                        purchase_id: this.purchase.id,
                        total_retur: totalRetur,
                        alasan_retur: this.alasanRetur,
                        retur_pembelian: returPembelian,
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
                    this.purchase = {};
                    this.isLoading = false;
                    this.purchaseDetails = [];
                    this.totalRetur = 0;

                },
                updateSubtotal(purchaseDetail) {
                    const max = purchaseDetail.product_batch.jumlah_saat_ini;
                    if (purchaseDetail.purchases_return_detail.jumlah > max) {
                        purchaseDetail.purchases_return_detail.jumlah = max;

                        Swal.fire({
                            icon: 'warning',
                            title: 'Peringatan',
                            text: "Jumlah retur melebihi stok saat ini (" + max + ")"
                        })
                    }
                },
                formatRupiah(num) {
                    if (num === null || num === undefined || num === '') return '0';
                    let val = (typeof num === 'string') ? this.parseFormatted(num) : num;
                    return new Intl.NumberFormat('id-ID', {
                        maximumFractionDigits: 2,
                        minimumFractionDigits: 2
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
