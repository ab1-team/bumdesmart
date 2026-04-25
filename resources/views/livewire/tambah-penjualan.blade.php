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
                            <th width="15%">Qty</th>
                            <th width="12%">Diskon</th>
                            <th width="12%">Cashback</th>
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
                                    <input type="text" class="form-control" x-model="product.harga_jual" readonly
                                        x-mask:dynamic="$money($input, ',', '.', 0)">
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="number" :step="product.allow_decimal ? 'any' : '1'"
                                            class="form-control" x-model="product.jumlah_jual"
                                            x-on:input="updateRow(product.id)" x-on:focus="$el.select()">
                                    </div>
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
                                    <div class="text-end fw-bold py-2" x-text="product.subtotal"></div>
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
                                                    x-mask:dynamic="$money($input, ',', '.', 0)"
                                                    x-model="globalDiskon.jumlah">
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
                                                    x-mask:dynamic="$money($input, ',', '.', 0)"
                                                    x-model="globalCashback.jumlah">
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
                                        x-mask:dynamic="$money($input, ',', '.', 0)" x-model="bayar"
                                        x-on:keyup="calculateKembalian">
                                </div>

                                <div class="mb-3" x-show="jenisPembayaran === 'cash' || parseFormatted(bayar) > 0"
                                    x-transition>
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

                                <div x-show="metodeBayar === 'transfer' && (jenisPembayaran === 'cash' || parseFormatted(bayar) > 0)"
                                    x-transition class="mb-3">
                                    <label class="form-label">Pilih Bank</label>
                                    <div wire:ignore>
                                        <select id="bankAccountSelectSales" class="form-select" x-model="noRekening" placeholder="Pilih Rekening Bank...">
                                            <option value=""></option>
                                            @foreach($bankAccounts as $bank)
                                                <option value="{{ $bank->no_rek_bank }}">{{ $bank->nama }} ({{ $bank->no_rek_bank }})</option>
                                            @endforeach
                                        </select>
                                    </div>
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

                // Summary Data (Reactive instead of complex getters)
                summary: {
                    itemCount: 0,
                    subtotal: '0',
                    orderDiscount: '0',
                    orderTax: '0',
                    grandTotal: '0',
                    orderCashback: '0'
                },
                totalProducts: {
                    subtotal: '0',
                    diskon: 0,
                    jumlah_jual: 0
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
                    // Watchers - Only specific fields that affect totals
                    this.$watch('products', () => this.updateTotals(), {
                        deep: true
                    });
                    this.$watch('globalDiskon', () => this.updateTotals(), {
                        deep: true
                    });
                    this.$watch('globalCashback', () => this.updateTotals(), {
                        deep: true
                    });
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

                    this.jenisPajak = data.jenisPajak || 'tidak ada';
                    this.globalDiskon = data.globalDiskon || {
                        jenis: 'nominal',
                        jumlah: 0
                    };
                    this.globalCashback = data.globalCashback || {
                        jenis: 'nominal',
                        jumlah: 0
                    };

                    this.jenisPembayaran = data.jenisPembayaran;
                    this.metodeBayar = data.metodeBayar || 'tunai';
                    this.noRekening = data.noRekening || '';
                    this.bayar = this.formatRupiah(data.bayar);
                    this.status = data.status;

                    // Sync Payment Select
                    setTimeout(() => {
                        this.syncTomSelect('jenisPembayaran', this.jenisPembayaran);
                        this.syncTomSelect('jenisPajak', this.jenisPajak);
                    }, 500);

                    this.$nextTick(() => {
                        this.updateTotals();
                        this.calculateKembalian();
                    });
                },

                syncTomSelect(id, val) {
                    let el = document.getElementById(id);
                    if (el && el.tomselect) {
                        el.tomselect.setValue(val, true);
                    }
                },

                // --- Helpers ---
                formatDecimal(num) {
                    if (num === null || num === undefined || num === '') return '';
                    let val = (typeof num === 'string') ? this.parseFormatted(num) : num;
                    return new Intl.NumberFormat('id-ID', {
                        maximumFractionDigits: 2,
                        minimumFractionDigits: 0
                    }).format(val);
                },

                formatRupiah(num) {
                    return this.formatDecimal(num);
                },

                parseFormatted(val) {
                    if (typeof val === 'number') return val;
                    if (!val) return 0;
                    let str = String(val).trim();
                    // Indonesia: . (titik) ribuan, , (koma) desimal. 
                    if (str.includes(',')) {
                        let clean = str.replace(/\./g, '').replace(/,/g, '.');
                        return parseFloat(clean) || 0;
                    }
                    if (str.includes('.')) {
                        let parts = str.split('.');
                        if (parts[parts.length - 1].length === 3 || parts.length > 2) {
                            return parseFloat(str.replace(/\./g, '')) || 0;
                        }
                        return parseFloat(str) || 0;
                    }
                    return parseFloat(str) || 0;
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
                        totalQty += parseFloat(p.jumlah_jual) || 0;
                        sumDiskon += this.parseFormatted((p.diskon && p.diskon.nominal) ? p
                            .diskon.nominal : 0);
                        sumProductCashback += this.parseFormatted((p.cashback && p.cashback
                            .nominal) ? p.cashback.nominal : 0);
                    });

                    // Global Discount
                    let gDiskonVal = this.parseFormatted(this.globalDiskon ? this.globalDiskon.jumlah :
                        0);
                    let gDiskonAmt = (this.globalDiskon && this.globalDiskon.jenis === 'nominal') ?
                        gDiskonVal : (totalSub * gDiskonVal / 100);

                    // Tax
                    let taxable = Math.max(0, totalSub - gDiskonAmt);
                    let taxAmt = (this.jenisPajak === 'PPN') ? taxable * 0.11 : 0;
                    let grand = taxable + taxAmt;

                    // Global Cashback
                    let gCashbackVal = this.parseFormatted(this.globalCashback ? this.globalCashback
                        .jumlah : 0);
                    let gCashbackAmt = (this.globalCashback && this.globalCashback.jenis ===
                        'nominal') ? gCashbackVal : (totalSub * gCashbackVal / 100);

                    // Update Data Properties
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
                        let maxStock = this.products[id].stok_tersedia;
                        if (this.products[id].jumlah_jual >= maxStock) {
                            alert(`Stok tidak mencukupi! Maksimal ${maxStock} unit`);
                            return;
                        }
                        this.products[id].jumlah_jual++;
                    } else {
                        if (!product || (product.stok_tersedia !== undefined && product.stok_tersedia <=
                                0)) {
                            alert('Produk tidak tersedia atau stok habis');
                            return;
                        }

                        this.products[id] = {
                            id: product.id,
                            nama_produk: product.nama_produk,
                            gambar: product.gambar,
                            sku: product.sku,
                            harga_jual: this.formatRupiah(product.harga_jual),
                            jumlah_jual: 1,
                            unit: product.unit,
                            allow_decimal: product.allow_decimal,
                            stok_tersedia: product.stok_tersedia || 999999,
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
                            batch_info: product.batch_info || ''
                        };
                    }
                    this.updateRow(id);
                },

                removeProduct(id) {
                    delete this.products[id];
                    this.updateTotals();
                },

                updateRow(id) {
                    if (!this.products[id]) return;

                    let p = this.products[id];
                    let harga = this.parseFormatted(p.harga_jual);
                    let qty = parseFloat(p.jumlah_jual) || 0;

                    // Cek jika produk tidak boleh desimal tapi diinput desimal
                    if (!p.allow_decimal && qty % 1 !== 0) {
                        qty = Math.floor(qty);
                        p.jumlah_jual = qty;
                    }

                    if (qty > p.stok_tersedia) {
                        alert(`Stok tidak mencukupi! Maksimal ${p.stok_tersedia} unit`);
                        p.jumlah_jual = p.stok_tersedia;
                        qty = p.stok_tersedia;
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

                    if (pay < grand) {
                        this.jenisPembayaran = 'credit';
                        this.status = 'partial';
                        this.syncTomSelect('jenisPembayaran', 'credit');
                    } else {
                        if (this.jenisPembayaran === 'credit') {
                            this.jenisPembayaran = 'cash';
                            this.syncTomSelect('jenisPembayaran', 'cash');
                        }
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
                    this.updateTotals();
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
                    this.updateTotals();

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

            // Jenis Pembayaran TomSelect Sync
            let payEl = document.getElementById('jenisPembayaran');
            if (payEl && !payEl.tomselect) {
                new TomSelect(payEl, {
                    onChange: function(value) {
                        let el = document.querySelector('[x-data]');
                        if (el) Alpine.$data(el).jenisPembayaran = value;
                    }
                });
            }

            // Jenis Pajak TomSelect Sync
            let taxEl = document.getElementById('jenisPajak');
            if (taxEl && !taxEl.tomselect) {
                new TomSelect(taxEl, {
                    onChange: function(value) {
                        let el = document.querySelector('[x-data]');
                        if (el) Alpine.$data(el).jenisPajak = value;
                    }
                });
            }

            // Bank Account Select
            if (document.getElementById('bankAccountSelectSales')) {
                new TomSelect('#bankAccountSelectSales', {
                    create: false,
                    onChange: function(value) {
                        let el = document.querySelector('[x-data]');
                        if (el) Alpine.$data(el).noRekening = value;
                    }
                });
            }

            // Product Search (with Barcode support via type detection)
            if (document.getElementById('searchProduct')) {
                let tsProduct = new TomSelect('#searchProduct', {
                    closeAfterSelect: false,
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
                                    <div class="text-muted small">
                                        ${escape(data.category)} | ${escape(data.brand)}
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span>${escape(data.sku)}</span>
                                        <div class="text-end">
                                            <div class="text-success fw-bold">Rp ${Number(data.harga_jual).toLocaleString('id-ID')} / ${escape(data.unit)}</div>
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
                            // Autoselect if exactly 1 match (barcode support)
                            if (res.data.length === 1) {
                                let item = res.data[0];
                                if (query === item.sku || query === item.barcode) {
                                    this.addItem(item.id);
                                    this.blur();
                                }
                            }
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
