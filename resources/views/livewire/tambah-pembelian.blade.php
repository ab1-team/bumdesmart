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
                <div class="input-group">
                    <div class="flex-fill">
                        <select class="form-select" id="searchProduct">
                            <option value=""></option>
                        </select>
                    </div>
                    <button class="btn btn-icon btn-primary" title="Scan Barcode" @click="openScanner()">
                        <span class="material-symbols-outlined">qr_code_scanner</span>
                    </button>
                </div>
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
                                        <input type="number" :step="product.allow_decimal ? 'any' : '1'"
                                            class="form-control" x-model="product.jumlah_beli"
                                            x-on:input="updateRow(product.id)" x-on:focus="$el.select()">
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
                                        <label class="form-selectgroup-item flex-grow-1">
                                            <input type="radio" name="metodeBayar" value="qris"
                                                class="form-selectgroup-input" x-model="metodeBayar">
                                            <span class="form-selectgroup-label">
                                                <div class="d-flex gap-2 align-items-center">
                                                    <span class="material-symbols-outlined">
                                                        qr_code_2
                                                    </span>
                                                    <span>QRIS</span>
                                                </div>
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <div x-show="['transfer', 'qris'].includes(metodeBayar) && parseFormatted(bayar) > 0" x-transition
                                    class="mb-3">
                                    <label class="form-label">Pilih Bank</label>
                                    <div wire:ignore>
                                        <select id="bankAccountSelectPurchase" class="form-select" x-model="noRekening" placeholder="Pilih Rekening Bank...">
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
                                    wire:loading.attr="disabled" :disabled="isLoading">
                                    <span wire:loading.remove x-show="!isLoading">SIMPAN</span>
                                    <span wire:loading x-show="true" class="spinner-border spinner-border-sm" role="status"></span>
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

        <!-- Scanner Modal -->
        <div class="modal modal-blur fade" id="scannerModal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static" wire:ignore.self>
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content shadow-lg overflow-hidden">
                    <div class="modal-status-top bg-primary"></div>
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <span class="material-symbols-outlined me-2 text-primary">qr_code_scanner</span>
                            Scan Barcode / QR
                        </h5>
                        <button type="button" class="btn-close" @click="closeScanner()"></button>
                    </div>
                    <div class="modal-body p-0 position-relative border-top border-bottom">
                        <!-- Scanner Viewport -->
                        <div id="reader" style="width: 100%; min-height: 350px; background: #1d273b;"></div>
                        
                        <!-- Custom Overlay -->
                        <div class="scanner-overlay">
                            <div class="scanner-laser"></div>
                            <div class="scanner-frame"></div>
                        </div>

                        <!-- Last Scanned Info Overlay (Premium Look) -->
                        <div x-show="lastScannedName" x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 transform translate-y-4"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             class="position-absolute bottom-0 start-0 end-0 p-3 text-center"
                             style="background: linear-gradient(to top, rgba(0,0,0,0.8), transparent); z-index: 10; color: white;">
                            <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                                <span class="material-symbols-outlined text-success">check_circle</span>
                                <span class="fw-bold">Produk Ditemukan!</span>
                            </div>
                            <div x-text="lastScannedName" class="small opacity-75"></div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light d-flex justify-content-between py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="toggleCamera()">
                            <span class="material-symbols-outlined me-2">cached</span> Ganti Kamera
                        </button>
                        <button type="button" class="btn btn-primary px-4 shadow-sm" @click="closeScanner()">
                            <span class="material-symbols-outlined me-2">done_all</span> Selesai
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <style>
            /* Custom Scanner Styles */
            .scanner-overlay {
                position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                pointer-events: none;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 5;
            }
            .scanner-frame {
                width: 250px;
                height: 250px;
                border: 2px solid rgba(255, 255, 255, 0.3);
                border-radius: 20px;
                box-shadow: 0 0 0 1000px rgba(0, 0, 0, 0.5);
                position: relative;
            }
            .scanner-laser {
                position: absolute;
                width: 230px;
                height: 2px;
                background: #2fb344;
                box-shadow: 0 0 15px #2fb344;
                animation: scan 2s linear infinite;
                z-index: 6;
            }
            @keyframes scan {
                0% { top: 25%; }
                50% { top: 75%; }
                100% { top: 25%; }
            }
            .scanner-success-flash {
                animation: success-flash 0.5s ease-out;
            }
            @keyframes success-flash {
                0% { background: rgba(47, 179, 68, 0); }
                50% { background: rgba(47, 179, 68, 0.3); }
                100% { background: rgba(47, 179, 68, 0); }
            }
        </style>
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

                // Scanner State
                html5QrCode: null,
                lastScannedCode: null,
                lastScannedTime: 0,
                lastScannedName: '',
                currentCameraId: null,
                cameras: [],

                // Payment State
                jenisPembayaran: 'cash',
                metodeBayar: 'tunai',
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

                // Scanner Methods
                async openScanner() {
                    $('#scannerModal').modal('show');

                    this.lastScannedCode = null;
                    this.lastScannedName = '';

                    setTimeout(async () => {
                        try {
                            this.html5QrCode = new Html5Qrcode("reader");
                            const devices = await Html5Qrcode.getCameras();
                            if (devices && devices.length > 0) {
                                this.cameras = devices;
                                let backCamera = devices.find(d => d.label.toLowerCase().includes('back'));
                                this.currentCameraId = backCamera ? backCamera.id : devices[0].id;
                                this.startScanning();
                            } else {
                                Toast.fire({ icon: 'error', title: 'Kamera tidak ditemukan' });
                            }
                        } catch (err) {
                            console.error(err);
                            Toast.fire({ icon: 'error', title: 'Gagal mengakses kamera' });
                        }
                    }, 500);
                },

                async startScanning() {
                    const config = { fps: 10, qrbox: { width: 250, height: 250 } };
                    await this.html5QrCode.start(
                        this.currentCameraId, 
                        config, 
                        (decodedText) => this.onScanSuccess(decodedText)
                    );
                },

                async toggleCamera() {
                    if (this.cameras.length < 2) return;
                    await this.html5QrCode.stop();
                    let currentIndex = this.cameras.findIndex(c => c.id === this.currentCameraId);
                    let nextIndex = (currentIndex + 1) % this.cameras.length;
                    this.currentCameraId = this.cameras[nextIndex].id;
                    this.startScanning();
                },

                onScanSuccess(decodedText) {
                    const now = Date.now();
                    if (decodedText === this.lastScannedCode && (now - this.lastScannedTime) < 2000) {
                        return;
                    }

                    this.lastScannedCode = decodedText;
                    this.lastScannedTime = now;

                    const reader = document.getElementById('reader');
                    reader.classList.add('scanner-success-flash');
                    setTimeout(() => reader.classList.remove('scanner-success-flash'), 500);

                    const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3');
                    audio.play().catch(() => {});

                    this.$wire.scanProduct(decodedText).then(res => {
                        if (res.success) {
                            this.addProduct(res.product);
                            this.lastScannedName = res.product.nama_produk;
                            setTimeout(() => { if(this.lastScannedCode === decodedText) this.lastScannedName = '' }, 3000);
                        } else {
                            Toast.fire({ icon: 'warning', title: res.message });
                        }
                    });
                },

                async closeScanner() {
                    if (this.html5QrCode) {
                        try {
                            await this.html5QrCode.stop();
                        } catch (e) {}
                        this.html5QrCode = null;
                    }
                    $('#scannerModal').modal('hide');
                },
                totalProducts: {
                    subtotal: '0',
                    diskon: 0,
                    cashback: 0,
                    jumlah_beli: 0
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

                    this.$watch('metodeBayar', (value) => {
                        if (value === 'transfer' && @js($defaultTransferAccount)) {
                            this.noRekening = @js($defaultTransferAccount);
                        } else if (value === 'qris' && @js($defaultQrisAccount)) {
                            this.noRekening = @js($defaultQrisAccount);
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
                    this.globalDiskon = data.globalDiskon || {
                        jenis: 'nominal',
                        jumlah: 0
                    };
                    this.globalCashback = data.globalCashback || {
                        jenis: 'nominal',
                        jumlah: 0
                    };

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
                    if (num === null || num === undefined || num === '') return '';
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

                initExpDatePicker(el, product) {
                    if (el._litepicker) el._litepicker.destroy();
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
                            harga_beli: this.formatRupiah(product.harga_beli),
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
                            subtotal: this.formatRupiah(product.harga_beli)
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

            // Bank Account Select
            if (document.getElementById('bankAccountSelectPurchase')) {
                new TomSelect('#bankAccountSelectPurchase', {
                    create: false,
                    onChange: function(value) {
                        let el = document.querySelector('[x-data]');
                        if (el) Alpine.$data(el).noRekening = value;
                    }
                });
            }

            // Product Search
            if (document.getElementById('searchProduct')) {
                new TomSelect('#searchProduct', {
                    closeAfterSelect: false,
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
                                    <div class="text-muted small">
                                        ${escape(data.category)} | ${escape(data.brand)}
                                    </div>
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
                        @this.call('loadSearchProducts', query, 0).then(res => {
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
