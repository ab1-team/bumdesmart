<div wire:ignore x-data="penjualanHandler()" x-init="initData(@js($existingData))" @reset-form.window="resetForm">
    <div class="card">
        <div class="card-body">
            <!-- Header Form -->
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Nomor Penjualan</label>
                    <input type="text" class="form-control" x-model="nomorPenjualan" placeholder="Nomor Penjualan" />
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Tanggal Penjualan</label>
                    <input type="text" class="form-control litepicker" id="tanggalPenjualan"
                        x-model="tanggalPenjualan" placeholder="Tanggal Penjualan" />
                </div>
                <!-- Customer Select -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Customer</label>
                    <select class="form-select" id="customer" x-model="customer" wire:ignore>
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
                            <th width="30%">Nama Produk</th>
                            <th width="15%">Harga</th>
                            <th width="10%">Qty</th>
                            <th width="15%">Diskon</th>
                            <th width="15%">Cashback</th>
                            <th width="20%">Subtotal</th>
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
                                    <input type="text" class="form-control" x-model="product.harga_jual" readonly
                                        x-mask:dynamic="$money($input)">
                                </td>
                                <td>
                                    <input type="number" step="any" class="form-control" x-model="product.jumlah_jual"
                                        x-on:input="updateRow(product.id)" x-on:focus="$el.select()">
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
                            <td colspan="8" class="text-center text-muted py-4">
                                <i>Belum ada produk yang dipilih</i>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold bg-light">
                            <td colspan="3" class="text-end">TOTAL</td>
                            <td x-text="summary.itemCount"></td>
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
                                        <label class="form-label">Jenis Pajak</label>
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
                                        <option value="credit">Tempo/Piutang</option>
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
    </div>
    <!-- Includes for Modals -->
    @include('livewire.tambah-penjualan-component.modal-diskon')
    @include('livewire.tambah-penjualan-component.modal-cashback')
</div>
</div>

@section('script')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('penjualanHandler', () => ({
                // Basic Fields
                nomorPenjualan: '',
                tanggalPenjualan: new Date().toISOString().slice(0, 10),
                customer: '',
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

                // Sales usually focus on Payment
                jenisPembayaran: 'cash', // cash, credit
                metodeBayar: 'tunai', // tunai, transfer
                noRekening: '',
                bayar: 0,
                kembalian: 0,
                status: 'completed',

                // Summaries
                totalProducts: {
                    subtotal: 0,
                    diskon: 0,
                    jumlah_jual: 0
                },
                summary: {
                    itemCount: 0,
                    subtotal: 0,
                    orderDiscount: 0,
                    orderTax: 0,
                    grandTotal: 0
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
                    // Watchers
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
                },

                initData(data) {
                    if (!data) return;
                    this.nomorPenjualan = data.nomorPenjualan;
                    this.tanggalPenjualan = data.tanggalPenjualan;
                    this.customer = data.customer;
                    this.catatan = data.catatan || '';

                    // Pre-populate Customer TomSelect
                    let customerSelect = document.getElementById('customer');
                    if (customerSelect && data.customer && data.customer_name) {
                        if (customerSelect.tomselect) {
                            customerSelect.tomselect.addOption({
                                id: data.customer,
                                nama_pelanggan: data.customer_name
                            });
                            customerSelect.tomselect.setValue(data.customer);
                        } else {
                            let opt = document.createElement('option');
                            opt.value = data.customer;
                            opt.text = data.customer_name;
                            customerSelect.add(opt);
                        }
                    }

                    if (data.products && Object.keys(data.products).length > 0) {
                        this.products = JSON.parse(JSON.stringify(data.products));
                    }

                    this.jenisPajak = data.jenisPajak;
                    this.globalDiskon = data.globalDiskon;
                    this.globalCashback = data.globalCashback;

                    this.jenisPembayaran = data.jenisPembayaran;
                    this.metodeBayar = data.metodeBayar || 'tunai';
                    this.noRekening = data.noRekening || '';
                    this.bayar = this.formatRupiah(data.bayar);
                    this.status = data.status;

                    // Sync Payment Select
                    setTimeout(() => {
                        let paySelect = document.getElementById('jenisPembayaran');
                        if (paySelect && paySelect.tomselect) {
                            paySelect.tomselect.setValue(this.jenisPembayaran, true);
                        }
                        let taxSelect = document.getElementById('jenisPajak');
                        if (taxSelect && taxSelect.tomselect) {
                            taxSelect.tomselect.setValue(this.jenisPajak, true);
                        }
                    }, 500);

                    this.$nextTick(() => {
                        this.calculateTotal();
                        this.calculateKembalian();
                    });
                },

                // --- Helpers ---
                formatDecimal(num) {
                    if (num === null || num === undefined) return '';
                    return Number(num).toLocaleString('id-ID', {
                        maximumFractionDigits: 2,
                        minimumFractionDigits: 0
                    });
                },

                formatRupiah(num) {
                    return this.formatDecimal(num);
                },

                parseFormatted(val) {
                    if (typeof val === 'number') return val;
                    return parseFloat(String(val).replace(/,/g, '')) || 0;
                },

                // --- Cart Logic ---
                addProduct(product) {
                    let id = product.id;
                    if (this.products[id]) {
                        // Check if incrementing would exceed stock (use stored stock from cart)
                        let maxStock = this.products[id].stok_tersedia;
                        if (this.products[id].jumlah_jual >= maxStock) {
                            alert(`Stok tidak mencukupi! Maksimal ${maxStock} unit`);
                            return;
                        }
                        this.products[id].jumlah_jual++;
                    } else {
                        // New product - ensure stok_tersedia exists
                        if (!product.stok_tersedia || product.stok_tersedia <= 0) {
                            alert('Produk tidak tersedia atau stok habis');
                            return;
                        }

                        // Logic for pricing precedence will be handled in Backend search, 
                        // here we just receive the final 'selling_price' (harga_jual)
                        this.products[id] = {
                            id: product.id,
                            nama_produk: product.nama_produk,
                            gambar: product.gambar,
                            sku: product.sku,
                            harga_jual: this.formatRupiah(product.harga_jual),
                            jumlah_jual: 1,
                            stok_tersedia: product.stok_tersedia, // Store stock info
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
                            subtotal: this.formatRupiah(product.harga_jual),
                            batch_info: product.batch_info || '' // Optional info
                        };
                    }
                    this.updateRow(id);
                },

                removeProduct(id) {
                    delete this.products[id];
                    this.calculateTotal();
                },

                updateRow(id) {
                    if (!this.products[id]) return;

                    let p = this.products[id];
                    let harga = this.parseFormatted(p.harga_jual);
                    let qty = parseFloat(p.jumlah_jual) || 0;

                    // Validate qty against stock
                    if (qty > p.stok_tersedia) {
                        alert(`Stok tidak mencukupi! Maksimal ${p.stok_tersedia} unit`);
                        p.jumlah_jual = p.stok_tersedia;
                        qty = p.stok_tersedia;
                    }

                    if (qty < 1) {
                        p.jumlah_jual = 1;
                        qty = 1;
                    }

                    let diskon = this.parseFormatted(p.diskon.nominal);

                    let sub = (harga * qty) - diskon;
                    if (sub < 0) sub = 0;

                    this.products[id].subtotal = this.formatRupiah(sub);
                    this.calculateTotal(); // Ensure totals update immediately
                },

                calculateTotal() {
                    let totalSub = 0;
                    let totalQty = 0;
                    let sumDiskon = 0;
                    let sumProductCashback = 0;

                    Object.values(this.products).forEach(p => {
                        let sub = this.parseFormatted(p.subtotal);
                        let qty = parseFloat(p.jumlah_jual) || 0;
                        let d = this.parseFormatted(p.diskon.nominal);
                        let c = this.parseFormatted(p.cashback ? p.cashback.nominal : 0);

                        totalSub += sub;
                        totalQty += qty;
                        sumDiskon += d;
                        sumProductCashback += c;
                    });

                    // Global Discount
                    let gDiskonVal = this.parseFormatted(this.globalDiskon.jumlah);
                    let gDiskonAmt = 0;
                    if (this.globalDiskon.jenis === 'nominal') {
                        gDiskonAmt = gDiskonVal;
                    } else {
                        gDiskonAmt = (totalSub * gDiskonVal) / 100;
                    }

                    // Tax
                    let taxable = totalSub - gDiskonAmt;
                    if (taxable < 0) taxable = 0;

                    let taxAmt = 0;
                    if (this.jenisPajak === 'PPN') {
                        taxAmt = taxable * 0.11;
                    }

                    let grand = taxable + taxAmt;

                    this.totalProducts = {
                        subtotal: this.formatRupiah(totalSub),
                        diskon: sumDiskon,
                        jumlah_jual: totalQty
                    };

                    this.summary = {
                        itemCount: totalQty,
                        subtotal: this.formatRupiah(totalSub),
                        orderDiscount: this.formatRupiah(gDiskonAmt),
                        orderTax: this.formatRupiah(taxAmt),
                        grandTotal: this.formatRupiah(grand),
                        orderCashback: this.formatRupiah(this.calculateGlobalCashback(totalSub) +
                            sumProductCashback)
                    };

                    this.calculateKembalian();
                },

                calculateGlobalCashback(subtotal) {
                    let val = this.parseFormatted(this.globalCashback.jumlah);
                    if (this.globalCashback.jenis === 'nominal') return val;
                    return (subtotal * val) / 100;
                },

                calculateKembalian() {
                    let pay = this.parseFormatted(this.bayar);
                    let grand = this.parseFormatted(this.summary.grandTotal);

                    this.kembalian = this.formatRupiah(pay - grand);

                    // Auto-detect Credit status
                    if (pay < grand) {
                        this.jenisPembayaran = 'credit';
                        this.status = 'partial';
                    } else {
                        this.jenisPembayaran = 'cash';
                        this.status = 'completed';
                    }
                },

                // --- Modals ---
                openDiscountModal(id) {
                    this.activeModalId = id;
                    let p = JSON.parse(JSON.stringify(this.products[id]));
                    this.modalProduct = p;
                    $('#discountModal').modal('show');
                },

                openCashbackModal(id) {
                    this.activeModalId = id;
                    let p = JSON.parse(JSON.stringify(this.products[id]));
                    // Ensure cashback object exists
                    if (!p.cashback) {
                        p.cashback = {
                            jenis: 'nominal',
                            jumlah: 0,
                            nominal: 0
                        };
                    }
                    this.modalProduct = p;
                    $('#cashbackModal').modal('show');
                },

                saveDiscount() {
                    if (!this.activeModalId) return;
                    let id = this.activeModalId;
                    let m = this.modalProduct;
                    let p = this.products[id];

                    let harga = this.parseFormatted(p.harga_jual);
                    let qty = parseInt(p.jumlah_jual) || 0;

                    let valDiskon = this.parseFormatted(m.diskon.jumlah);
                    let nominalDiskon = 0;
                    if (m.diskon.jenis === 'nominal') {
                        nominalDiskon = valDiskon;
                    } else {
                        nominalDiskon = (harga * qty * valDiskon) / 100;
                    }

                    this.products[id].diskon = {
                        jenis: m.diskon.jenis,
                        jumlah: m.diskon.jumlah,
                        nominal: this.formatRupiah(nominalDiskon)
                    };

                    this.updateRow(id);
                    $('#discountModal').modal('hide');
                },

                saveCashback() {
                    if (!this.activeModalId) return;
                    let id = this.activeModalId;
                    let m = this.modalProduct;
                    let p = this.products[id];

                    let harga = this.parseFormatted(p.harga_jual);
                    let qty = parseInt(p.jumlah_jual) || 0;

                    let valCashback = this.parseFormatted(m.cashback.jumlah);
                    let nominalCashback = 0;
                    if (m.cashback.jenis === 'nominal') {
                        nominalCashback = valCashback;
                    } else {
                        // Percent cashback per item price
                        nominalCashback = (harga * qty * valCashback) / 100;
                    }

                    this.products[id].cashback = {
                        jenis: m.cashback.jenis,
                        jumlah: m.cashback.jumlah,
                        nominal: this.formatRupiah(nominalCashback)
                    };

                    // Cashback doesn't affect row subtotal, but triggers total recalc
                    this.calculateTotal();
                    $('#cashbackModal').modal('hide');
                },

                resetForm() {
                    this.nomorPenjualan = '';
                    this.tanggalPenjualan = new Date().toISOString().slice(0, 10);
                    this.customer = '';
                    this.catatan = '';
                    this.products = {};
                    this.bayar = 0;
                    this.kembalian = 0;
                    this.calculateTotal();

                    let customerSelect = document.getElementById('customer');
                    if (customerSelect && customerSelect.tomselect) customerSelect.tomselect.clear();
                },

                // --- Server Sync ---
                saveAll() {
                    this.isLoading = true;

                    let cleanProducts = [];
                    Object.values(this.products).forEach(p => {
                        cleanProducts.push({
                            id: p.id,
                            jumlah_jual: p.jumlah_jual,
                            harga_jual: this.parseFormatted(p.harga_jual),
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
                        nomorPenjualan: this.nomorPenjualan,
                        tanggalPenjualan: this.tanggalPenjualan,
                        customer: this.customer,
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
                        metodeBayar: this.metodeBayar,
                        noRekening: (this.metodeBayar === 'transfer') ? this.noRekening : null,
                        bayar: this.parseFormatted(this.bayar),
                        kembalian: this.parseFormatted(this.kembalian),
                        grandTotal: this.parseFormatted(this.summary.grandTotal),
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

        window.addEventListener('open-receipt', (event) => {
            window.open(event.detail.url, '_blank');
        });

        // TomSelect Initialization
        document.addEventListener('DOMContentLoaded', () => {
            // Customer Select
            if (document.getElementById('customer')) {
                new TomSelect('#customer', {
                    valueField: 'id',
                    labelField: 'nama_pelanggan',
                    searchField: 'nama_pelanggan',
                    load: function(query, callback) {
                        if (query.length < 2) return callback();
                        @this.call('loadCustomers', query, 0).then(res => callback(res.data)).catch(
                            () => callback());
                    },
                    onChange: function(value) {
                        let el = document.querySelector('[x-data]');
                        if (el) Alpine.$data(el).customer = value;
                    }
                });
            }

            // Product Search (with Barcode support via type detection)
            if (document.getElementById('searchProduct')) {
                let tsProduct = new TomSelect('#searchProduct', {
                    valueField: 'id',
                    labelField: 'nama_produk',
                    searchField: ['nama_produk', 'sku'], // Added barcode field if logical
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
                                        <div class="text-end">
                                            <div class="text-success fw-bold">Rp ${Number(data.harga_jual).toLocaleString('id-ID', { maximumFractionDigits: 2, minimumFractionDigits: 0 })}</div>
                                            <div class="text-info" style="font-size: 0.85em;">${escape(data.batch_info)}</div>
                                        </div>
                                    </div>
                                    ${data.promo_label ? `<div class="badge bg-green-lt mt-1">${escape(data.promo_label)}</div>` : ''}
                                  </div>
                                </div>`;
                        }
                    },
                    load: function(query, callback) {
                        // Pass current customer ID to get special prices
                        let customerId = document.getElementById('customer').value;

                        @this.call('loadSearchProducts', query, customerId).then(res => {
                            callback(res.data);
                        }).catch(() => callback());
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
