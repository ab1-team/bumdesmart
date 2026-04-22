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
                            <th width="25%">Nama Produk</th>
                            <th width="12%">Harga Satuan</th>
                            <th width="12%">Qty</th>
                            <th width="10%">Exp. Date</th>
                            <th width="10%">Diskon</th>
                            <th width="11%">Cashback</th>
                            <th width="11%">Subtotal</th>
                            <th width="4%"></th>
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
                                        x-on:input="updateRow(product.id)" x-mask:dynamic="$money($input, ',', '.', 0)"
                                        x-on:focus="$el.select()">
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="number" :step="product.allow_decimal ? 'any' : '1'" class="form-control" x-model="product.jumlah_beli"
                                            x-on:input="updateRow(product.id)" x-on:focus="$el.select()">
                                        <span class="input-group-text small" x-text="product.unit"></span>
                                    </div>
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
                            <td colspan="3" class="text-end">TOTAL</td>
                            <td x-text="totalProducts.jumlah_beli"></td>
                            <td></td>
                            <td x-text="formatRupiah(totalProducts.diskon)"></td>
                            <td x-text="formatRupiah(totalProducts.cashback || 0)"></td>
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
                                            x-bind:value="totalProducts.subtotal" />
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
                                                    x-mask:dynamic="$money($input, ',', '.', 0)" x-model="globalDiskon.jumlah">
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
                                                    x-mask:dynamic="$money($input, ',', '.', 0)" x-model="globalCashback.jumlah">
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
                                        x-mask:dynamic="$money($input, ',', '.', 0)" x-model="bayar"
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
                status: 'completed',

                // Summary Data (Reactive instead of complex getters)
                summary: { itemCount: 0, subtotal: '0', orderDiscount: '0', orderTax: '0', grandTotal: '0', orderCashback: '0' },
                totalProducts: { subtotal: '0', diskon: 0, cashback: 0, jumlah_beli: 0 },

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
                    this.$watch('products', () => this.updateTotals(), { deep: true });
                    this.$watch('globalDiskon', () => this.updateTotals(), { deep: true });
                    this.$watch('globalCashback', () => this.updateTotals(), { deep: true });
                    this.$watch('jenisPajak', () => this.updateTotals());
                    this.$watch('bayar', () => this.calculateKembalian());
                    
                    this.$watch('jenisPembayaran', (val) => {
                        if (val === 'cash') {
                            let pay = this.parseFormatted(this.bayar);
                            let grand = this.parseFormatted(this.summary.grandTotal);
                            // Only auto-fill if current payment is less than total
                            if (pay < grand) {
                                this.bayar = this.summary.grandTotal;
                            }
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
                            let opt = document.createElement('option');
                            opt.value = data.supplier;
                            opt.text = data.supplier_name;
                            supplierSelect.add(opt);
                        }
                    }

                    // Populate Products
                    if (data.products && Object.keys(data.products).length > 0) {
                        this.products = JSON.parse(JSON.stringify(data.products));
                    }

                    // Configs
                    this.jenisPajak = data.jenisPajak || 'tidak ada';
                    this.globalDiskon = data.globalDiskon || { jenis: 'nominal', jumlah: 0 };
                    this.globalCashback = data.globalCashback || { jenis: 'nominal', jumlah: 0 };

                    // Payment
                    this.jenisPembayaran = data.jenisPembayaran;
                    this.bayar = this.formatRupiah(data.bayar);
                    this.status = data.status;

                    // Sync Payment Type
                    setTimeout(() => {
                        this.syncTomSelect('jenisPembayaran', this.jenisPembayaran);
                        this.syncTomSelect('jenisPajak', this.jenisPajak);
                    }, 500);

                    this.$nextTick(() => {
                        this.updateTotals();
                        this.calculateKembalian();
                    });
                },

                // --- Helpers ---
                formatRupiah(num) {
                    if (num === null || num === undefined) return '';
                    return Number(num).toLocaleString('id-ID', {
                        maximumFractionDigits: 2,
                        minimumFractionDigits: 0
                    });
                },

                parseFormatted(val) {
                    if (typeof val === 'number') return val;
                    if (!val) return 0;
                    let str = String(val).trim();
                    
                    // Format Indonesia: . (titik) adalah ribuan, , (koma) adalah desimal
                    // Kita hapus semua titik, lalu ganti koma dengan titik agar bisa di-parseFloat
                    let clean = str.replace(/\./g, '').replace(/,/g, '.');
                    return parseFloat(clean) || 0;
                },

                initExpDatePicker(el, product) {
                    if (el._litepicker) el._litepicker.destroy();
                    const picker = new Litepicker({
                        element: el,
                        format: 'YYYY-MM-DD',
                        singleMode: true,
                        autoApply: true,
                        setup: (picker) => {
                            picker.on('selected', (date) => {
                                product.tanggal_kadaluarsa = date.format('YYYY-MM-DD');
                            });
                        }
                    });
                    el._litepicker = picker;
                },

                // Update Totals (The brain of the calculation)
                updateTotals() {
                    let totalSub = 0;
                    let totalQty = 0;
                    let sumDiskon = 0;
                    let sumProductCashback = 0;

                    Object.values(this.products).forEach(p => {
                        let rowSub = this.parseFormatted(p.subtotal || 0);
                        totalSub += rowSub;
                        totalQty += parseFloat(p.jumlah_beli) || 0;
                        sumDiskon += this.parseFormatted((p.diskon && p.diskon.nominal) ? p.diskon.nominal : 0);
                        sumProductCashback += this.parseFormatted((p.cashback && p.cashback.nominal) ? p.cashback.nominal : 0);
                    });

                    // Global Discount
                    let gDiskonVal = this.parseFormatted(this.globalDiskon ? this.globalDiskon.jumlah : 0);
                    let gDiskonAmt = (this.globalDiskon && this.globalDiskon.jenis === 'nominal') ? gDiskonVal : (totalSub * gDiskonVal / 100);

                    // Tax
                    let taxable = Math.max(0, totalSub - gDiskonAmt);
                    let taxAmt = (this.jenisPajak === 'PPN') ? taxable * 0.11 : 0;
                    let grand = taxable + taxAmt;

                    // Global Cashback
                    let gCashbackVal = this.parseFormatted(this.globalCashback ? this.globalCashback.jumlah : 0);
                    let gCashbackAmt = (this.globalCashback && this.globalCashback.jenis === 'nominal') ? gCashbackVal : (totalSub * gCashbackVal / 100);

                    // Update Data Properties
                    this.totalProducts = {
                        subtotal: this.formatRupiah(totalSub),
                        diskon: sumDiskon,
                        cashback: sumProductCashback,
                        jumlah_beli: totalQty
                    };

                    this.summary = {
                        itemCount: totalQty,
                        subtotal: this.formatRupiah(totalSub),
                        orderDiscount: this.formatRupiah(gDiskonAmt),
                        orderTax: this.formatRupiah(taxAmt),
                        grandTotal: this.formatRupiah(grand),
                        orderCashback: this.formatRupiah(gCashbackAmt + sumProductCashback)
                    };

                    // Auto-fill payment if Cash is selected
                    if (this.jenisPembayaran === 'cash') {
                        this.bayar = this.summary.grandTotal;
                    }

                    this.calculateKembalian();
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
                            harga_beli: this.formatRupiah(parseInt(product.harga_beli)),
                            jumlah_beli: 1,
                            unit: product.unit,
                            allow_decimal: product.allow_decimal,
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
                        confirmButtonText: 'Ya, Hapus!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            delete this.products[id];
                            this.updateTotals();
                        }
                    });
                },

                updateRow(id) {
                    if (!this.products[id]) return;

                    let p = this.products[id];
                    let harga = this.parseFormatted(p.harga_beli);
                    let qty = parseFloat(p.jumlah_beli) || 0;
                    
                    // Cek jika produk tidak boleh desimal tapi diinput desimal
                    if (!p.allow_decimal && qty % 1 !== 0) {
                        qty = Math.floor(qty);
                        p.jumlah_beli = qty;
                    }

                    let diskon = this.parseFormatted(p.diskon.nominal);

                    let sub = (harga * qty) - diskon;
                    if (sub < 0) sub = 0;

                    this.products[id].subtotal = this.formatRupiah(sub);
                    this.updateTotals();
                },

                calculateKembalian() {
                    let pay = this.parseFormatted(this.bayar);
                    let grand = this.parseFormatted(this.summary.grandTotal);

                    this.kembalian = this.formatRupiah(pay - grand);
                    this.updatePaymentStatus();
                },

                updatePaymentStatus() {
                    let pay = this.parseFormatted(this.bayar);
                    let grand = this.parseFormatted(this.summary.grandTotal);

                    if (pay < grand) {
                        if (this.jenisPembayaran !== 'preorder') {
                            this.jenisPembayaran = 'credit';
                            this.syncTomSelect('jenisPembayaran', 'credit');
                        }
                        this.status = 'partial';
                    } else {
                        if (this.jenisPembayaran === 'credit') {
                            this.jenisPembayaran = 'cash';
                            this.syncTomSelect('jenisPembayaran', 'cash');
                        }
                        this.status = (this.jenisPembayaran === 'preorder') ? 'paid' : 'completed';
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

                    let val = this.parseFormatted(m.diskon.jumlah);
                    let nominal = (m.diskon.jenis === 'nominal') ? val : (harga * qty * val) / 100;

                    this.products[id].diskon = {
                        jenis: m.diskon.jenis,
                        jumlah: m.diskon.jumlah,
                        nominal: this.formatRupiah(nominal)
                    };

                    this.updateRow(id);
                    $('#discountModal').modal('hide');
                },

                saveCashback() {
                    if (!this.activeModalId) return;
                    let id = this.activeModalId;
                    let p = this.products[id];
                    let m = this.modalProduct;

                    let harga = this.parseFormatted(p.harga_beli);
                    let qty = parseFloat(p.jumlah_beli) || 0;

                    let val = this.parseFormatted(m.cashback.jumlah);
                    let nominal = (m.cashback.jenis === 'nominal') ? val : (harga * qty * val) / 100;

                    this.products[id].cashback = {
                        jenis: m.cashback.jenis,
                        jumlah: m.cashback.jumlah,
                        nominal: this.formatRupiah(nominal)
                    };

                    this.updateTotals();
                    $('#cashbackModal').modal('hide');
                },

                resetForm() {
                    this.nomorPembelian = '';
                    this.tanggalPembelian = new Date().toISOString().slice(0, 10);
                    this.supplier = '';
                    this.catatan = '';
                    this.products = {};
                    this.bayar = 0;
                    this.kembalian = 0;
                    this.updateTotals();

                    let supplierSelect = document.getElementById('supplier');
                    if (supplierSelect && supplierSelect.tomselect) supplierSelect.tomselect.clear();
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
                        noRekening: (this.metodeBayar === 'transfer') ? this.noRekening : null,
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

            // Sync Payment Type Select
            let payEl = document.getElementById('jenisPembayaran');
            if (payEl && !payEl.tomselect) {
                new TomSelect(payEl, {
                    onChange: function(value) {
                        let el = document.querySelector('[x-data]');
                        if (el) Alpine.$data(el).jenisPembayaran = value;
                    }
                });
            }

            // Sync Pajak Select
            let taxEl = document.getElementById('jenisPajak');
            if (taxEl && !taxEl.tomselect) {
                new TomSelect(taxEl, {
                    onChange: function(value) {
                        let el = document.querySelector('[x-data]');
                        if (el) Alpine.$data(el).jenisPajak = value;
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
                                        <span class="text-success fw-bold">Rp ${Number(data.harga_beli).toLocaleString('id-ID')} / ${escape(data.unit)}</span>
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
