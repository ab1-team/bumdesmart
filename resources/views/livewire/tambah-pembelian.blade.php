<div wire:ignore x-data="pembelianHandler()" x-init="initData(@js($existingData))" @reset-form.window="resetForm">
    <div class="card">
        <div class="card-body">
            <!-- Header Form -->
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Nomor Pembelian</label>
                    <input type="text" class="form-control" x-model="nomorPembelian" placeholder="Nomor Pembelian" />
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Tanggal Pembelian</label>
                    <input type="text" class="form-control litepicker" id="tanggalPembelian"
                        x-model="tanggalPembelian" placeholder="Tanggal Pembelian" />
                </div>
                <!-- Supplier Select -->
                <div class="col-md-4 mb-3" wire:ignore>
                    <label class="form-label">Supplier</label>
                    <select class="form-select" id="supplier" x-model="supplier">
                        <option value=""></option>
                    </select>
                </div>
            </div>

            <hr>

            <!-- Product Search -->
            <div class="mb-3" wire:ignore>
                <label class="form-label">Cari Produk</label>
                <select class="form-select" id="searchProduct">
                    <option value=""></option>
                </select>
            </div>

            <!-- Product Table -->
            <div class="table-responsive">
                <table class="table table-vcenter table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="20%">Nama Produk</th>
                            <th width="12%">Harga Satuan</th>
                            <th width="8%">Qty</th>
                            <th width="10%">Exp. Date</th>
                            <th width="12%">Diskon</th>
                            <th width="12%">Cashback</th>
                            <th width="12%">Subtotal</th>
                            <th width="5%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(product, index) in Object.values(products)" :key="index">
                            <tr>
                                <td x-text="index + 1"></td>
                                <td>
                                    <div x-text="product.nama_produk" class="fw-bold"></div>
                                    <div x-text="product.sku" class="text-secondary small"></div>
                                </td>
                                <td>
                                    <input type="text" class="form-control" x-model="product.harga_beli"
                                        x-on:input="updateRow(product.id)" x-mask:dynamic="$money($input)"
                                        x-on:focus="$el.select()">
                                </td>
                                <td>
                                    <input type="number" step="any" class="form-control" x-model="product.jumlah_beli"
                                        x-on:input="updateRow(product.id)" x-on:focus="$el.select()">
                                </td>
                                <td>
                                    <input type="text" class="form-control" x-model="product.tanggal_kadaluarsa"
                                        placeholder="Exp. Date" x-init="initExpDatePicker($el, product)" :id="'expDate-' + product.id">
                                </td>
                                <td>
                                    <!-- Trigger Modal Diskon -->
                                    <div class="input-group cursor-pointer" x-on:click="openDiscountModal(product.id)">
                                        <input type="text" class="form-control bg-white cursor-pointer" readonly
                                            x-bind:value="product.diskon.nominal">
                                    </div>
                                </td>
                                <td>
                                    <!-- Trigger Modal Cashback -->
                                    <div class="input-group cursor-pointer" x-on:click="openCashbackModal(product.id)">
                                        <input type="text" class="form-control bg-white cursor-pointer" readonly
                                            x-bind:value="product.cashback.nominal">
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control bg-light" readonly
                                        x-bind:value="product.subtotal">
                                </td>
                                <td>
                                    <a href="#" class="text-danger"
                                        x-on:click.prevent="removeProduct(product.id)">
                                        <span class="material-symbols-outlined">delete</span>
                                    </a>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="Object.keys(products).length === 0">
                            <td colspan="9" class="text-center text-muted py-4">
                                <i>Belum ada produk yang dipilih</i>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold bg-light">
                            <td colspan="4" class="text-end">TOTAL</td>
                            <td></td>
                            <td x-text="formatRupiah(totalProducts.diskon)"></td>
                            <td x-text="formatRupiah(totalProducts.cashback)"></td>
                            <td x-text="totalProducts.subtotal"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <hr>

            <!-- Bottom Section (Calculations & Payment) -->
            <div class="row">
                <div class="col-md-8 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Subtotal</label>
                                        <input type="text" class="form-control fw-bold" readonly
                                            x-model="totalProducts.subtotal" />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Jenis Pajak (11%)</label>
                                        <select class="form-select tom-select" id="jenisPajak" x-model="jenisPajak">
                                            <option value="tidak ada">Tidak Ada</option>
                                            <option value="PPN">PPN</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label small">Diskon Tambahan (Global)</label>
                                        <div class="row g-2 align-items-center mb-2">
                                            <div class="col-auto">
                                                <div class="form-selectgroup">
                                                    <label class="form-selectgroup-item">
                                                        <input type="radio" value="nominal"
                                                            class="form-selectgroup-input"
                                                            x-model="globalDiskon.jenis">
                                                        <span class="form-selectgroup-label px-2">Rp</span>
                                                    </label>
                                                    <label class="form-selectgroup-item">
                                                        <input type="radio" value="persen"
                                                            class="form-selectgroup-input"
                                                            x-model="globalDiskon.jenis">
                                                        <span class="form-selectgroup-label px-2">%</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <input type="text" class="form-control"
                                                    x-mask:dynamic="$money($input)" x-model="globalDiskon.jumlah">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small">Cashback Tambahan (Global)</label>
                                        <div class="row g-2 align-items-center mb-2">
                                            <div class="col-auto">
                                                <div class="form-selectgroup">
                                                    <label class="form-selectgroup-item">
                                                        <input type="radio" value="nominal"
                                                            class="form-selectgroup-input"
                                                            x-model="globalCashback.jenis">
                                                        <span class="form-selectgroup-label px-2">Rp</span>
                                                    </label>
                                                    <label class="form-selectgroup-item">
                                                        <input type="radio" value="persen"
                                                            class="form-selectgroup-input"
                                                            x-model="globalCashback.jenis">
                                                        <span class="form-selectgroup-label px-2">%</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <input type="text" class="form-control"
                                                    x-mask:dynamic="$money($input)" x-model="globalCashback.jumlah">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Catatan</label>
                                        <textarea class="form-control" rows="3" x-model="catatan" placeholder="Catatan transaksi..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grand Totals & Actions -->
                <div class="col-md-4">
                    <div class="card bg-primary-lt h-100">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Subtotal</span>
                                    <span class="fw-bold" x-text="summary.subtotal"></span>
                                </div>
                                <div class="d-flex justify-content-between mb-1 text-danger">
                                    <span>Diskon (-)</span>
                                    <span class="fw-bold" x-text="summary.orderDiscount"></span>
                                </div>
                                <div class="d-flex justify-content-between mb-1 text-secondary">
                                    <span>Pajak (+)</span>
                                    <span class="fw-bold" x-text="summary.orderTax"></span>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fs-2">TOTAL</span>
                                    <span class="fs-2 fw-bold" x-text="summary.grandTotal"></span>
                                </div>
                                <div class="text-end small text-muted mt-1">
                                    Cashback: <span x-text="summary.orderCashback"></span>
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="mb-3">
                                    <label class="form-label">Status Pembayaran</label>
                                    <select class="form-select tom-select" id="jenisPembayaran"
                                        x-model="jenisPembayaran">
                                        <option value="cash">Cash/Lunas</option>
                                        <option value="credit">Tempo/Hutang</option>
                                        <option value="preorder">Pre-Order</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Nominal Bayar</label>
                                    <input type="text" class="form-control fs-3" placeholder="Bayar"
                                        x-mask:dynamic="$money($input)" x-model="bayar"
                                        x-on:keyup="calculateKembalian">
                                </div>

                                <div class="mb-3" x-show="parseFormatted(bayar) > 0" x-transition>
                                    <label class="form-label">Metode Pembayaran</label>
                                    <div class="form-selectgroup w-100">
                                        <label class="form-selectgroup-item flex-grow-1">
                                            <input type="radio" name="metodeBayar" value="tunai"
                                                class="form-selectgroup-input" x-model="metodeBayar">
                                            <span class="form-selectgroup-label">
                                                <div class="d-flex gap-2 align-items-center">
                                                    <span class="material-symbols-outlined">
                                                        payments
                                                    </span>
                                                    <span>Tunai</span>
                                                </div>
                                            </span>
                                        </label>
                                        <label class="form-selectgroup-item flex-grow-1">
                                            <input type="radio" name="metodeBayar" value="transfer"
                                                class="form-selectgroup-input" x-model="metodeBayar">
                                            <span class="form-selectgroup-label">
                                                <div class="d-flex gap-2 align-items-center">
                                                    <span class="material-symbols-outlined">
                                                        payment_card
                                                    </span>
                                                    <span>Transfer</span>
                                                </div>
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <div x-show="metodeBayar === 'transfer' && parseFormatted(bayar) > 0" x-transition
                                    class="mb-3">
                                    <input type="text" class="form-control" x-model="noRekening"
                                        placeholder="Nomor Rekening">
                                </div>

                                <div class="d-flex justify-content-between small">
                                    <span>Kembali:</span>
                                    <span class="fw-bold" x-text="kembalian"></span>
                                </div>

                                <button class="btn btn-primary w-100 btn-lg mt-3" x-on:click="saveAll"
                                    :disabled="isLoading">
                                    <span x-show="!isLoading">SIMPAN</span>
                                    <span x-show="isLoading" class="spinner-border spinner-border-sm"
                                        role="status"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- Includes for Modals -->
        @include('livewire.tambah-pembelian-component.modal-diskon')
        @include('livewire.tambah-pembelian-component.modal-cashback')
    </div>
</div>

@section('script')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('pembelianHandler', () => ({
                // Basic Fields
                nomorPembelian: '',
                tanggalPembelian: new Date().toISOString().slice(0, 10),
                supplier: '',
                catatan: '',

                // The Cart
                products: {},

                // Global State
                jenisPajak: 'tidak ada',
                globalDiskon: {
                    jenis: 'nominal',
                    jumlah: 0
                },
                globalCashback: {
                    jenis: 'nominal',
                    jumlah: 0
                },

                // Payment State
                jenisPembayaran: 'cash',
                metodeBayar: 'tunai',
                noRekening: '',
                bayar: 0,
                kembalian: 0,

                // Computed Summaries (for display)
                totalProducts: {
                    subtotal: 0,
                    diskon: 0,
                    cashback: 0,
                    jumlah_beli: 0
                },
                summary: {
                    itemCount: 0,
                    subtotal: 0,
                    orderDiscount: 0,
                    orderTax: 0,
                    grandTotal: 0,
                    orderCashback: 0
                },

                isLoading: false,

                // Modal Handlers
                activeModalId: null,
                modalProduct: {
                    diskon: {
                        jenis: 'nominal',
                        jumlah: 0,
                        nominal: 0
                    },
                    cashback: {
                        jenis: 'nominal',
                        jumlah: 0,
                        nominal: 0
                    },
                    nama_produk: '',
                    gambar: ''
                },

                init() {
                    // Auto-calculate when things change
                    this.$watch('products', () => this.calculateTotal(), {
                        deep: true
                    });
                    this.$watch('globalDiskon', () => this.calculateTotal(), {
                        deep: true
                    });
                    this.$watch('globalCashback', () => this.calculateTotal(), {
                        deep: true
                    });
                    this.$watch('jenisPajak', () => this.calculateTotal());
                    this.$watch('bayar', () => this.calculateKembalian());
                    this.$watch('jenisPembayaran', (val) => {
                        if ('{{ $purchaseId }}') return;

                        let grand = this.parseFormatted(this.summary.grandTotal);
                        let pay = this.parseFormatted(this.bayar);

                        // Only auto-fill if switching to CASH and currently underpaid
                        if (val === 'cash' && pay < grand) {
                            this.bayar = this.formatRupiah(grand);
                            this.calculateKembalian();
                        }
                    });
                },

                initData(data) {
                    if (!data) return;
                    this.nomorPembelian = data.nomorPembelian;
                    this.tanggalPembelian = data.tanggalPembelian;
                    this.catatan = data.catatan || '';
                    this.supplier = data.supplier;

                    // Pre-populate Supplier TomSelect if possible
                    let supplierSelect = document.getElementById('supplier');
                    if (supplierSelect && data.supplier && data.supplier_name) {
                        if (supplierSelect.tomselect) {
                            supplierSelect.tomselect.addOption({
                                id: data.supplier,
                                nama_supplier: data.supplier_name
                            });
                            supplierSelect.tomselect.setValue(data.supplier);
                        } else {
                            // If TomSelect not yet ready, add fallback option or wait
                            let opt = document.createElement('option');
                            opt.value = data.supplier;
                            opt.text = data.supplier_name;
                            supplierSelect.add(opt);
                        }
                    }

                    // Populate Products
                    if (data.products && Object.keys(data.products).length > 0) {
                        // Deep copy to ensure reactivity
                        this.products = JSON.parse(JSON.stringify(data.products));
                    }

                    // Global Configs
                    this.jenisPajak = data.jenisPajak;
                    this.globalDiskon = data.globalDiskon;
                    this.globalCashback = data.globalCashback;

                    // Payment
                    this.jenisPembayaran = data.jenisPembayaran;
                    this.bayar = this.formatRupiah(data.bayar);
                    this.status = data.status;

                    // Sync Payment Type
                    setTimeout(() => {
                        this.syncTomSelect('jenisPembayaran', data.jenisPembayaran);
                        this.syncTomSelect('jenisPajak', data.jenisPajak);
                    }, 500);

                    // Recalculate
                    this.$nextTick(() => {
                        this.calculateTotal();
                        // Force kembalian calc after total update
                        this.calculateKembalian();
                    });
                },

                // --- Helpers ---
                formatRupiah(num) {
                    return new Intl.NumberFormat('en-US').format(num || 0);
                },

                parseMoney(str) {
                    if (!str) return 0;
                    // Remove commas, keep dots and numbers
                    return parseFloat(String(str).replace(/,/g, '')) || 0;
                },

                parseFormatted(val) {
                    if (typeof val === 'number') return val;
                    // Remove commas for standard float parsing
                    return parseFloat(String(val).replace(/,/g, '')) || 0;
                },

                initExpDatePicker(el, product) {
                    // Destroy existing instance if any
                    if (el._litepicker) {
                        el._litepicker.destroy();
                    }

                    const picker = new Litepicker({
                        element: el,
                        format: 'YYYY-MM-DD',
                        singleMode: true,
                        autoApply: true,
                        setup: (picker) => {
                            picker.on('selected', (date) => {
                                product.tanggal_kadaluarsa = date.format(
                                    'YYYY-MM-DD');
                            });
                        }
                    });

                    el._litepicker = picker;
                },

                // --- Cart Logic ---
                addProduct(product) {
                    let id = product.id;
                    if (this.products[id]) {
                        this.products[id].jumlah_beli++;
                    } else {
                        this.products[id] = {
                            id: product.id,
                            nama_produk: product.nama_produk,
                            gambar: product.gambar,
                            sku: product.sku,
                            harga_beli: this.formatRupiah(parseInt(product
                                .harga_beli)), // Store as string for input support
                            jumlah_beli: 1,
                            tanggal_kadaluarsa: '',
                            diskon: {
                                jenis: 'nominal',
                                jumlah: 0,
                                nominal: 0
                            },
                            cashback: {
                                jenis: 'nominal',
                                jumlah: 0,
                                nominal: 0
                            },
                            subtotal: this.formatRupiah(parseInt(product.harga_beli))
                        };
                    }
                    this.updateRow(id);
                },

                removeProduct(id) {
                    Swal.fire({
                        title: 'Hapus Produk',
                        text: 'Yakin ingin menghapus produk ini?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            delete this.products[id];
                        }
                    });
                },

                updateRow(id) {
                    if (!this.products[id]) return;

                    let p = this.products[id];
                    let harga = this.parseFormatted(p.harga_beli);
                    let qty = parseFloat(p.jumlah_beli) || 0;
                    let diskon = this.parseFormatted(p.diskon.nominal);

                    let sub = (harga * qty) - diskon;
                    if (sub < 0) sub = 0;

                    this.products[id].subtotal = this.formatRupiah(sub);
                },

                calculateTotal() {
                    let totalSub = 0;
                    let totalQty = 0;
                    let sumDiskon = 0;
                    let sumCashback = 0;

                    // Loop through cart
                    Object.values(this.products).forEach(p => {
                        let sub = this.parseFormatted(p.subtotal);
                        let qty = parseFloat(p.jumlah_beli) || 0;
                        let d = this.parseFormatted(p.diskon.nominal);
                        let c = this.parseFormatted(p.cashback.nominal);

                        totalSub += sub;
                        totalQty += qty;
                        sumDiskon += d;
                        sumCashback += c;
                    });

                    // Global Discount
                    let gDiskonVal = this.parseFormatted(this.globalDiskon.jumlah);
                    let gDiskonAmt = 0;
                    if (this.globalDiskon.jenis === 'nominal') {
                        gDiskonAmt = gDiskonVal;
                    } else {
                        gDiskonAmt = (totalSub * gDiskonVal) / 100;
                    }

                    // Global Cashback
                    let gCashbackVal = this.parseFormatted(this.globalCashback.jumlah);
                    let gCashbackAmt = 0;
                    if (this.globalCashback.jenis === 'nominal') {
                        gCashbackAmt = gCashbackVal;
                    } else {
                        gCashbackAmt = (totalSub * gCashbackVal) / 100;
                    }

                    // Tax
                    let taxable = totalSub - gDiskonAmt;
                    if (taxable < 0) taxable = 0;

                    let taxAmt = 0;
                    if (this.jenisPajak === 'PPN') {
                        taxAmt = taxable * 0.11;
                    }

                    let grand = taxable + taxAmt;

                    // Update Display State
                    this.totalProducts = {
                        subtotal: this.formatRupiah(totalSub),
                        diskon: sumDiskon,
                        cashback: sumCashback,
                        jumlah_beli: totalQty
                    };

                    this.summary = {
                        itemCount: totalQty,
                        subtotal: this.formatRupiah(totalSub),
                        orderDiscount: this.formatRupiah(gDiskonAmt),
                        orderTax: this.formatRupiah(taxAmt),
                        grandTotal: this.formatRupiah(grand),
                        orderCashback: this.formatRupiah(gCashbackAmt)
                    };

                    this.calculateKembalian();
                },

                calculateKembalian() {
                    let pay = this.parseFormatted(this.bayar);
                    let grand = this.parseFormatted(this.summary.grandTotal);

                    if (pay >= grand) {
                        this.kembalian = this.formatRupiah(pay - grand);
                    } else {
                        this.kembalian = "0";
                    }
                    this.updatePaymentStatus();
                },

                updatePaymentStatus() {
                    let pay = this.parseFormatted(this.bayar);
                    let grand = this.parseFormatted(this.summary.grandTotal);

                    if (pay < grand) {
                        // Partial Payment
                        if (this.jenisPembayaran !== 'preorder') {
                            this.jenisPembayaran = 'credit';
                            this.syncTomSelect('jenisPembayaran', 'credit');
                        }
                        this.status = 'partial';
                    } else {
                        // Full Payment
                        if (this.jenisPembayaran === 'credit') {
                            this.jenisPembayaran = 'cash';
                            this.syncTomSelect('jenisPembayaran', 'cash');
                        }

                        if (this.jenisPembayaran === 'preorder') {
                            this.status = 'paid';
                        } else {
                            this.status = 'completed';
                        }
                    }
                },

                syncTomSelect(id, value) {
                    let el = document.getElementById(id);
                    if (el && el.tomselect) {
                        el.tomselect.setValue(value, true);
                    }
                },

                // --- Modals ---
                openDiscountModal(id) {
                    this.activeModalId = id;
                    this.modalProduct = JSON.parse(JSON.stringify(this.products[id]));
                    // Ensure plain numbers or formatted?
                    // x-mask expects formatted? Let's treat it as existing value.
                    // If it was already formatted in product state, it's fine.
                    // Assuming `jumlah` tracks the input value.
                    $('#discountModal').modal('show');
                },

                openCashbackModal(id) {
                    this.activeModalId = id;
                    this.modalProduct = JSON.parse(JSON.stringify(this.products[id]));
                    $('#cashbackModal').modal('show');
                },

                saveDiscount() {
                    if (!this.activeModalId) return;
                    let id = this.activeModalId;

                    let p = this.products[id];
                    let m = this.modalProduct;

                    let harga = this.parseFormatted(p.harga_beli);
                    let qty = parseFloat(p.jumlah_beli) || 0;


                    // FIX: Use parseFormatted
                    let val = this.parseFormatted(m.diskon.jumlah);

                    let nominal = 0;
                    if (m.diskon.jenis === 'nominal') {
                        nominal = val;
                    } else {
                        nominal = (harga * qty * val) / 100;
                    }

                    // Save back to cart
                    this.products[id].diskon = {
                        jenis: m.diskon.jenis,
                        jumlah: m.diskon.jumlah, // keep the formatted input value
                        nominal: this.formatRupiah(nominal)
                    };

                    this.updateRow(id);
                    this.$watch('products', () => {});
                    $('#discountModal').modal('hide');
                },

                saveCashback() {
                    if (!this.activeModalId) return;
                    let id = this.activeModalId;
                    let p = this.products[id];
                    let m = this.modalProduct;

                    let harga = this.parseFormatted(p.harga_beli);
                    let qty = parseFloat(p.jumlah_beli) || 0;

                    // FIX: Use parseFormatted
                    let val = this.parseFormatted(m.cashback.jumlah);

                    let nominal = 0;
                    if (m.cashback.jenis === 'nominal') {
                        nominal = val;
                    } else {
                        nominal = (harga * qty * val) / 100;
                    }

                    this.products[id].cashback = {
                        jenis: m.cashback.jenis,
                        jumlah: m.cashback.jumlah,
                        nominal: this.formatRupiah(nominal)
                    };

                    this.updateRow(id);
                    $('#cashbackModal').modal('hide');
                },

                resetForm() {
                    this.nomorPembelian = '';
                    this.tanggalPembelian = new Date().toISOString().slice(0, 10);
                    this.supplier = '';
                    this.catatan = '';
                    this.products = {};
                    this.jenisPajak = 'tidak ada';
                    this.globalDiskon = {
                        jenis: 'nominal',
                        jumlah: 0
                    };
                    this.globalCashback = {
                        jenis: 'nominal',
                        jumlah: 0
                    };
                    this.jenisPembayaran = 'cash';
                    this.metodeBayar = 'tunai';
                    this.noRekening = '';
                    this.bayar = 0;
                    this.kembalian = 0;

                    this.calculateTotal();

                    let supplierSelect = document.getElementById('supplier');
                    if (supplierSelect && supplierSelect.tomselect) supplierSelect.tomselect.clear();

                    this.syncTomSelect('jenisPembayaran', 'cash');
                },

                // --- Server Sync ---
                saveAll() {
                    this.isLoading = true;

                    let cleanProducts = [];
                    Object.values(this.products).forEach(p => {
                        cleanProducts.push({
                            id: p.id,
                            jumlah_beli: p.jumlah_beli,
                            harga_beli: this.parseFormatted(p.harga_beli),
                            tanggal_kadaluarsa: p.tanggal_kadaluarsa || null,
                            diskon: {
                                jenis: p.diskon.jenis,
                                jumlah: this.parseFormatted(p.diskon.jumlah),
                                nominal: this.parseFormatted(p.diskon.nominal)
                            },
                            cashback: {
                                jenis: p.cashback.jenis,
                                jumlah: this.parseFormatted(p.cashback.jumlah),
                                nominal: this.parseFormatted(p.cashback.nominal)
                            },
                            subtotal: this.parseFormatted(p.subtotal)
                        });
                    });

                    let payload = {
                        nomorPembelian: this.nomorPembelian,
                        tanggalPembelian: this.tanggalPembelian,
                        supplier: this.supplier,
                        catatan: this.catatan,
                        products: cleanProducts,

                        subtotal: this.parseFormatted(this.totalProducts.subtotal),
                        jenisPajak: this.jenisPajak,
                        globalDiskon: {
                            jenis: this.globalDiskon.jenis,
                            jumlah: this.parseFormatted(this.globalDiskon.jumlah)
                        },
                        globalCashback: {
                            jenis: this.globalCashback.jenis,
                            jumlah: this.parseFormatted(this.globalCashback.jumlah)
                        },

                        jenisPembayaran: this.jenisPembayaran,
                        status: this.status,
                        // If transfer, send noRekening
                        noRekening: (this.metodeBayar === 'transfer') ? this.noRekening : null,
                        // Could also send metodeBayar if needed by backend, adding it to notes or a new field
                        // For now, assuming backend infers or doesn't firmly require it column-wise
                        bayar: this.parseFormatted(this.bayar),
                        kembalian: this.parseFormatted(this.kembalian),

                        grandTotal: this.parseFormatted(this.summary.grandTotal),
                        metodeBayar: this.metodeBayar,
                    };

                    @this.call('saveAll', payload)
                        .then(() => {
                            this.isLoading = false;
                        })
                        .catch(err => {
                            this.isLoading = false;
                            console.error(err);
                            alert('Gagal menyimpan transaksi');
                        });
                }
            }));
        });

        // TomSelect Initialization
        document.addEventListener('DOMContentLoaded', () => {
            // Supplier
            if (document.getElementById('supplier')) {
                new TomSelect('#supplier', {
                    valueField: 'id',
                    labelField: 'nama_supplier',
                    searchField: 'nama_supplier',
                    load: function(query, callback) {
                        if (query.length < 2) return callback();
                        @this.call('loadSuppliers', query, 0).then(res => callback(res.data)).catch(
                            () => callback());
                    },
                    onChange: function(value) {
                        let el = document.querySelector('[x-data]');
                        if (el) Alpine.$data(el).supplier = value;
                    }
                });
            }

            // Product Search
            if (document.getElementById('searchProduct')) {
                new TomSelect('#searchProduct', {
                    valueField: 'id',
                    labelField: 'nama_produk',
                    searchField: ['nama_produk', 'sku'],
                    render: {
                        option: function(data, escape) {
                            return `<div class="d-flex align-items-center py-2 border-bottom">
                                    <div class="col-auto">
                                        <span class="avatar avatar-1" style="background-image: url(${'/storage/' + data.gambar})"></span>
                                    </div>
                                  <div class="flex-grow-1 ps-2">
                                    <div class="fw-bold">${escape(data.nama_produk)}</div>
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span>${escape(data.sku)}</span>
                                        <span class="text-success">Rp ${new Intl.NumberFormat('en-US').format(data.harga_beli)}</span>
                                    </div>
                                  </div>
                                </div>`;
                        }
                    },
                    load: function(query, callback) {
                        if (query.length < 2) return callback();
                        @this.call('loadSearchProducts', query, 0).then(res => callback(res.data))
                            .catch(() => callback());
                    },
                    onChange: function(value) {
                        if (!value) return;
                        let instance = this;
                        let selected = instance.options[value];

                        this.clear();
                        this.clearOptions();

                        let el = document.querySelector('[x-data]');
                        if (el) {
                            Alpine.$data(el).addProduct(selected);
                            instance.clear();
                        }
                    }
                });
            }
        });
    </script>
@endsection
