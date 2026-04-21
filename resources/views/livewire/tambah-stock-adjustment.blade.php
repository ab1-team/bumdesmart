@section('link')
    <style>
        .ts-control {
            border: 1px solid #dce1e7;
            padding: 8px 12px;
            border-radius: 4px;
        }

        .ts-wrapper.multi .ts-control>div {
            background: #f0f2f6;
            color: #1d273b;
            border: 1px solid #dce1e7;
        }
    </style>
@endsection

<div>
    <div class="card" wire:ignore x-data="stockAdjustment()" x-init="init()">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Nomor Penyesuaian</label>
                    <input type="text" class="form-control" x-model="nomorAdjustment" readonly
                        placeholder="Auto Generated">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tanggal Penyesuaian</label>
                    <input type="text" id="tanggalAdjustment" class="form-control litepicker">
                </div>

                <div class="col-md-3" wire:ignore>
                    <label class="form-label">Jenis Penyesuaian</label>
                    <select id="jenisAdjustment" class="form-select tom-select">
                        <option value="">-- pilih --</option>
                        <option value="damaged">Barang Rusak</option>
                        <option value="expired">Kadaluarsa</option>
                        <option value="lost">Hilang/Dicuri</option>
                        <option value="found">Temuan Barang</option>
                        <option value="correction">Koreksi</option>
                        <option value="sample">Sample</option>
                    </select>
                </div>
            </div>

            <hr>

            <div class="row mb-3">
                <div class="col-12" wire:ignore>
                    <label class="form-label">Cari & Tambah Produk</label>
                    <select id="cariProduk" class="form-select"
                        placeholder="Ketik nama produk atau scan barcode..."></select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Produk</th>
                            <th style="width: 15%">Harga Satuan</th>
                            <th style="width: 10%">Stok Sistem</th>
                            <th style="width: 10%">Stok Fisik</th>
                            <th style="width: 10%">Selisih</th>
                            <th>Alasan</th>
                            <th style="width: 5%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="item.id">
                            <tr>
                                <td x-text="index + 1"></td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold" x-text="item.nama_produk"></span>
                                        <small class="text-muted" x-text="item.sku"></small>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control" :value="formatRupiah(item.harga_satuan)"
                                        readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" :value="formatDecimal(item.stok_sistem)" readonly>
                                </td>
                                <td>
                                    <input type="number" step="any" class="form-control" min="0"
                                        x-model="item.stok_fisik" @input="hitung(item)">
                                </td>
                                <td>
                                    <span :class="{ 'text-success': item.selisih > 0, 'text-danger': item.selisih < 0 }"
                                        class="fw-bold" x-text="formatDecimal(item.selisih)"></span>
                                    <small class="d-block text-muted" x-show="item.selisih > 0">(Surplus)</small>
                                    <small class="d-block text-muted" x-show="item.selisih < 0">(Minus)</small>
                                </td>
                                <td>
                                    <input type="text" class="form-control" x-model="item.alasan"
                                        placeholder="Alasan...">
                                </td>
                                <td>
                                    <a href="#" class="text-danger" @click.prevent="removeItem(index)">
                                        <span class="material-symbols-outlined">delete</span>
                                    </a>
                                </td>
                            </tr>
                        </template>
                        <template x-if="items.length === 0">
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    Belum ada produk yang dipilih. Silakan cari produk di atas.
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <hr>

            <div class="row mt-3">
                <div class="col-12 mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea class="form-control" rows="3" x-model="catatan"></textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-success" @click="simpan('draft')">
                        <i class="fas fa-save me-1"></i> Simpan Draft
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@section('script')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('stockAdjustment', () => ({
                nomorAdjustment: '',
                tanggalAdjustment: '',
                jenisAdjustment: '',
                catatan: '',
                items: [], // Selected items to adjust

                tsJenis: null,
                tsProduk: null,

                init() {
                    this.initTanggal()
                    this.initJenis()
                    this.initCariProduk()
                },

                initTanggal() {
                    if (this._tanggalInit) return
                    this._tanggalInit = true

                    new Litepicker({
                        element: document.getElementById('tanggalAdjustment'),
                        format: 'YYYY-MM-DD',
                        autoApply: true,
                        singleMode: true,
                        setup: p => p.on('selected', d => {
                            this.tanggalAdjustment = d.format('YYYY-MM-DD')
                        })
                    })
                },

                initJenis() {
                    const el = document.getElementById('jenisAdjustment')
                    if (!el || el.tomselect) {
                        this.tsJenis = el?.tomselect
                        return
                    }

                    this.tsJenis = new TomSelect(el, {
                        persist: true,
                        onChange: v => this.jenisAdjustment = v
                    })
                },

                initCariProduk() {
                    const el = document.getElementById('cariProduk')
                    if (!el) return

                    if (el.tomselect) {
                        this.tsProduk = el.tomselect
                        return
                    }

                    this.tsProduk = new TomSelect(el, {
                        valueField: 'id',
                        labelField: 'nama_produk',
                        searchField: ['nama_produk', 'sku'],
                        placeholder: 'Ketik nama barang...',
                        load: (query, callback) => {
                            if (!query.length) return callback()
                            @this.call('loadProducts', 1, query).then(res => {
                                callback(res.data)
                            }).catch(() => {
                                callback()
                            })
                        },
                        render: {
                            option: function(item, escape) {
                                return `<div class="d-flex align-items-center py-2 px-2 border-bottom">
                                    ${item.gambar ? `<span class="avatar me-2" style="background-image: url(${escape(item.gambar)})"></span>` : ''}
                                    <div class="flex-fill">
                                        <div class="fw-bold">${escape(item.nama_produk)}</div>
                                        <div class="text-muted small">SKU: ${escape(item.sku)} | Stok: ${Number(item.stok_aktual).toLocaleString('id-ID', { maximumFractionDigits: 2, minimumFractionDigits: 0 })} ${escape(item.unit)}</div>
                                    </div>
                                </div>`
                            },
                            item: function(item, escape) {
                                return `<div>${escape(item.nama_produk)}</div>`
                            }
                        },
                        onChange: (value) => {
                            if (value) {
                                const item = this.tsProduk.options[value]
                                this.addItem(item)
                                this.tsProduk.clear() // Reset search box
                            }
                        }
                    })
                },

                addItem(product) {
                    // Check if already exists
                    const exists = this.items.find(i => i.id == product.id)
                    if (exists) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'warning',
                            title: 'Produk sudah ada di daftar',
                            showConfirmButton: false,
                            timer: 1500
                        })
                        return
                    }

                    this.items.push({
                        id: product.id,
                        nama_produk: product.nama_produk,
                        sku: product.sku,
                        stok_sistem: Number(product.stok_aktual),
                        stok_fisik: Number(product.stok_aktual), // Default match
                        selisih: 0,
                        harga_satuan: Number(product.harga_beli ?? 0),
                        alasan: ''
                    })
                },

                removeItem(index) {
                    this.items.splice(index, 1)
                },

                hitung(item) {
                    item.selisih = item.stok_fisik - item.stok_sistem
                },

                formatDecimal(val) {
                    if (val === null || val === undefined) return '';
                    return Number(val).toLocaleString('id-ID', {
                        maximumFractionDigits: 2,
                        minimumFractionDigits: 0
                    });
                },

                formatRupiah(val) {
                    return this.formatDecimal(val);
                },

                simpan(status) {
                    if (this.items.length === 0) {
                        Swal.fire('Error', 'Belum ada produk yang dipilih', 'error')
                        return
                    }

                    // Check if any adjustments made
                    const hasAdjustment = this.items.some(i => i.selisih !== 0)
                    if (!hasAdjustment) {
                        Swal.fire('Info',
                            'Tidak ada perubahan selisih stok pada item yang dipilih. Data akan tetap disimpan.',
                            'info')
                    }

                    Swal.fire({
                        title: 'Simpan Draft?',
                        text: 'Data adjustment akan disimpan sebagai draft.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Simpan',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            @this.call('saveAdjustment', {
                                no_penyesuaian: this.nomorAdjustment,
                                tanggal_penyesuaian: this.tanggalAdjustment,
                                jenis_penyesuaian: this.jenisAdjustment,
                                status: status,
                                catatan: this.catatan,
                                items: this.items
                            })
                        }
                    })
                }

            }))
        })
    </script>
@endsection
