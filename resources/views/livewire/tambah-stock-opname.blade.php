<div>
    <div class="col-12">
        <div class="card" x-data="stockOpname()">
            <div class="card-header">
                <h3 class="card-title">Form Tambah Stock Opname</h3>
            </div>
            <div class="card-body">
                <!-- Header Form -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Nomor Opname</label>
                                <input type="text" class="form-control" x-model="nomorOpname"
                                    placeholder="Auto Generate">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tanggal Opname</label>
                                <input type="text" class="form-control litepicker" id="tanggal_opname"
                                    x-model="tanggalOpname">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select tom-select" id="statusOpname" x-model="status">
                                    <option value="draft">Draft</option>
                                    <option value="approved">Approved (Finalize)</option>
                                </select>
                                <small class="text-muted" x-show="status === 'approved'">
                                    Stok akan langsung diupdate di sistem.
                                </small>
                                <small class="text-muted" x-show="status === 'draft'">
                                    Stok disimpan sebagai draft.
                                </small>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control" x-model="catatan" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-muted-lt h-100">
                            <div class="card-body">
                                <h4 class="card-title">Ringkasan</h4>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total Item:</span>
                                    <span class="fw-bold" x-text="items.length">0</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Item Selisih:</span>
                                    <span class="fw-bold text-warning"
                                        x-text="items.filter(i => i.selisih !== 0).length">0</span>
                                </div>
                                <!-- Validasi untuk Tombol Simpan -->
                                <button type="button" class="btn btn-primary w-100 mt-3" @click="save"
                                    :disabled="isSaving || items.length === 0">
                                    <span x-show="isSaving" class="spinner-border spinner-border-sm me-2"></span>
                                    <span x-text="status === 'approved' ? 'Approve & Finalize' : 'Simpan Draft'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Filters & Load Data -->
                <div class="row align-items-end mb-3">
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label">Filter Kategori</label>
                        <select class="form-select tom-select" id="kategori" wire:model.live="categoryId">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->nama_kategori }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label">Filter Rak</label>
                        <select class="form-select tom-select" id="rak" wire:model.live="shelfId">
                            <option value="">Semua Rak</option>
                            @foreach ($shelves as $shelf)
                                <option value="{{ $shelf->id }}">{{ $shelf->nama_rak }} -
                                    {{ $shelf->lokasi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Pilih Kategori atau Rak untuk memuat produk.
                            Data yang dimuat akan ditambahkan ke tabel di bawah untuk dihitung.
                        </div>
                    </div>
                </div>

                <!-- Table Input Logic -->
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-striped">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="30%">Produk</th>
                                <th width="15%" class="text-center">Stok Sistem</th>
                                <th width="15%" class="text-center">Stok Fisik</th>
                                <th width="15%" class="text-center">Selisih</th>
                                <th width="20%">Alasan/Catatan</th>
                                <th width="5%"></th>
                            </tr>
                        </thead>
                        <tbody wire:ignore>
                            <template x-for="(item, index) in items" :key="item.id">
                                <tr
                                    :class="{
                                        'table-warning': item.selisih !== 0,
                                        'table-success': item.counted &&
                                            item.selisih === 0
                                    }">
                                    <td x-text="index + 1"></td>
                                    <td>
                                        <div class="font-weight-medium" x-text="item.nama_produk"></div>
                                        <div class="text-muted text-xs" x-text="item.kode_produk"></div>
                                    </td>
                                    <td class="text-center" x-text="item.sistem"></td>
                                    <td>
                                        <input type="number" step="any" class="form-control text-center"
                                            x-model.number="item.fisik"
                                            @input="calculateDiff(index); item.counted = true"
                                            @focus="$event.target.select()">
                                    </td>
                                    <td class="text-center">
                                        <span x-text="item.selisih > 0 ? '+' + item.selisih : item.selisih"
                                            :class="item.selisih < 0 ? 'text-danger fw-bold' : (item.selisih > 0 ?
                                                'text-success fw-bold' : 'text-muted')">
                                        </span>
                                        <div x-show="item.selisih !== 0" class="text-xs mt-1">
                                            <span x-text="item.selisih < 0 ? 'Shortage' : 'Excess'" class="badge"
                                                :class="item.selisih < 0 ? 'bg-danger-lt' : 'bg-success-lt'">
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" x-model="item.alasan"
                                            placeholder="Ket. selisih...">
                                    </td>
                                    <td>
                                        <button class="btn btn-icon btn-ghost-danger btn-sm"
                                            @click="removeItem(index)">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="icon icon-tabler icon-tabler-x" width="24" height="24"
                                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M18 6l-12 12" />
                                                <path d="M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="items.length === 0">
                                <td colspan="7" class="text-center py-4 text-muted">
                                    Belum ada produk yang dimuat. Silakan pilih filter Kategori atau Rak.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

@section('script')
    <script>
        (function() {
            function registerStockOpname() {
                // Avoid double registration
                if (Alpine.data && !Alpine.data['stockOpname']) {
                    Alpine.data('stockOpname', () => ({
                        nomorOpname: @entangle('nomorOpname'),
                        tanggalOpname: @entangle('tanggalOpname'),
                        status: @entangle('status'),
                        catatan: @entangle('catatan'),
                        search: '',
                        items: [],
                        isSaving: false,

                        init() {
                            if (!this.tanggalOpname) {
                                this.tanggalOpname = new Date().toISOString().split('T')[0];
                            }

                            // Sync products from Livewire to Alpine
                            this.$watch('$wire.products', (value) => {
                                if (value && value.length > 0) {
                                    this.mergeItems(value);
                                }
                            });
                        },

                        loadProducts() {
                            if (this.search.length < 2) return;
                            this.$wire.loadProducts(1, this.search);
                        },

                        mergeItems(newItems) {
                            // Use a Map for O(1) lookup
                            const existingIds = new Set(this.items.map(i => i.id));
                            newItems.forEach(item => {
                                if (!existingIds.has(item.id)) {
                                    this.items.push(item);
                                }
                            });
                        },

                        removeItem(index) {
                            this.items.splice(index, 1);
                        },

                        calculateDiff(index) {
                            const item = this.items[index];
                            // Safety check
                            const fisik = item.fisik === '' ? 0 : parseFloat(item.fisik);
                            const sistem = parseFloat(item.sistem);

                            item.selisih = fisik - sistem;

                            if (item.selisih > 0) item.jenis_selisih = 'plus';
                            else if (item.selisih < 0) item.jenis_selisih = 'minus';
                            else item.jenis_selisih = 'match';
                        },

                        save() {
                            if (this.status === 'approved') {
                                if (!confirm(
                                        'Apakah anda yakin ingin FINALISASI Stock Opname ini? Stok akan diupdate.'
                                    )) {
                                    return;
                                }
                            }

                            this.isSaving = true;

                            const payload = {
                                no_opname: this.nomorOpname,
                                tanggal: this.tanggalOpname,
                                status: this.status,
                                catatan: this.catatan,
                                items: this.items.map(item => ({
                                    product_id: item.id,
                                    stok_sistem: item.sistem,
                                    stok_fisik: item.fisik,
                                    selisih: item.selisih,
                                    jenis_selisih: item.jenis_selisih,
                                    alasan: item.alasan,
                                    harga_satuan: item.harga_beli
                                }))
                            };

                            this.$wire.saveOpname(payload)
                                .then(() => {
                                    this.isSaving = false;
                                })
                                .catch((e) => {
                                    this.isSaving = false;
                                    console.error(e);
                                });
                        }
                    }));
                }
            }

            if (typeof Alpine !== 'undefined') {
                registerStockOpname();
            } else {
                document.addEventListener('alpine:init', registerStockOpname);
            }
        })();
    </script>
@endsection
