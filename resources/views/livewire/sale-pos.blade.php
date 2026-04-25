<div class="row main-row" x-data="posSystem()" @sale-stored.window="cart = []" @keydown.window="handleShortcuts($event)">
    @if (!$cashDrawer)
        <div class="position-absolute d-flex flex-column align-items-center justify-content-center"
            style="z-index: 100; background: rgba(255,255,255,0.7); backdrop-filter: blur(5px); top: 0; left: 0; right: 0; bottom: 0;">
            <div class="card shadow-lg" style="width: 400px;">
                <div class="card-body text-center p-5">
                    <span class="material-symbols-outlined text-primary mb-3" style="font-size: 64px;">
                        lock_open
                    </span>
                    <h2 class="mb-3">Kasir Belum Dibuka</h2>
                    <p class="text-secondary mb-4">Silahkan buka kasir untuk memulai transaksi.</p>
                    <button class="btn btn-primary btn-lg w-100" data-bs-toggle="modal"
                        data-bs-target="#openCashierModal">
                        Buka Kasir Sekarang
                    </button>
                </div>
            </div>
        </div>
    @endif
    <div class="col-12 col-md-5 col-lg-4 d-flex flex-column h-100 mb-3 mb-md-0">
        <div class="mb-3">
            <div class="card">
                <div class="card-body">
                    <!-- Customer Selection -->
                    <div class="mb-3">
                        <div class="input-group" wire:ignore>
                            <a href="/dashboard" class="btn btn-icon btn-outline-primary" title="Kembali ke Dashboard">
                                <span class="material-symbols-outlined">home</span>
                            </a>
                            <div class="flex-fill">
                                <select class="form-select" id="customerSearch" placeholder="Pilih Pelanggan...">
                                    <option value=""></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Product Search -->
                    <div class="mb-3" wire:ignore>
                        <div class="input-group">
                            <div class="flex-fill">
                                <select id="productSearchSelect" class="form-select"
                                    placeholder="Cari Produk atau Scan Barcode...">
                                    <option value=""></option>
                                </select>
                            </div>
                            <button class="btn btn-icon btn-primary" title="Scan Barcode" @click="openScanner()">
                                <span class="material-symbols-outlined">qr_code_scanner</span>
                            </button>
                        </div>
                    </div>

                    <div class="w-100 overflow-x-auto category-scroll p-1 d-none d-md-block">
                        <div class="form-selectgroup d-flex flex-nowrap">
                            <label class="form-selectgroup-item flex-shrink-0">
                                <input type="radio" name="category" class="form-selectgroup-input" checked
                                    value="" wire:model.live="selectedCategory" />
                                <span class="form-selectgroup-label whitespace-nowrap">
                                    <span class="material-symbols-outlined">
                                        apps
                                    </span>
                                    Semua Kategori
                                </span>
                            </label>

                            @foreach ($categories as $category)
                                <label class="form-selectgroup-item flex-shrink-0">
                                    <input type="radio" name="category" value="{{ $category->id }}"
                                        class="form-selectgroup-input" wire:model.live="selectedCategory" />
                                    <span class="form-selectgroup-label whitespace-nowrap">
                                        <span class="material-symbols-outlined">
                                            {{ $category->icon }}
                                        </span>
                                        {{ $category->nama_kategori }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden p-1 d-none d-md-block">
            <div class="row g-2">
                @foreach ($products as $product)
                    <div class="col-6 mb-2">
                        <div class="card text-bg-dark border-0 shadow-sm product-card" style="height: 100px;"
                            @click="addToCart({
                                id: {{ $product->id }},
                                name: '{{ addslashes($product->nama_produk) }}',
                                price: {{ $product->harga_jual }},
                                stock: {{ $product->stok_aktual }},
                                unit: {{ $product->unit }},
                                image: '{{ $product->gambar && $product->gambar !== 'products/no-image.png' ? asset('storage/' . $product->gambar) : 'https://placehold.co/400x400?text=No+Image' }}'
                            })">
                            <div
                                class="h-100 bg-muted-lt d-flex align-items-center justify-content-center overflow-hidden position-relative">
                                @if (
                                    $product->gambar &&
                                        $product->gambar !== 'products/no-image.png' &&
                                        file_exists(public_path('storage/' . $product->gambar)))
                                    <img src="{{ asset('storage/' . $product->gambar) }}" class="card-img h-100"
                                        style="object-fit: cover; opacity: 0.6;" alt="{{ $product->nama_produk }}">
                                @else
                                    <img src="https://placehold.co/400x400?text=No+Image" class="card-img h-100"
                                        style="object-fit: cover; opacity: 0.3;" alt="{{ $product->nama_produk }}">
                                @endif
                            </div>
                            <div class="card-img-overlay d-flex flex-column justify-content-end p-2">
                                <h5 class="card-title mb-0 text-truncate" style="font-size: 0.7rem;">
                                    {{ $product->nama_produk }}</h5>
                                <div class="card-text fw-bold text-primary" style="font-size: 0.75rem;">Rp
                                    {{ number_format($product->harga_jual, 0, ',', '.') }}</div>
                                <span class="badge bg-dark-lt position-absolute top-0 end-0 m-1"
                                    style="font-size: 0.6rem;">{{ number_format($product->stok_aktual, $product->stok_aktual == intval($product->stok_aktual) ? 0 : 1, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="d-none d-md-block">
            {{ $products->links('components.pos-pagination') }}
        </div>
    </div>

    <div class="col-12 col-md-7 col-lg-8 overflow-hidden d-flex flex-column" wire:ignore>
        <div class="card h-100">

            <div class="card-body p-0 px-3 overflow-x-hidden overflow-y-auto">
                <div class="list-group list-group-flush">

                    <template x-if="cart.length === 0">
                        <div class="text-center p-5 text-secondary">
                            <span class="material-symbols-outlined fs-1 mb-2">shopping_cart_off</span>
                            <p>Keranjang kosong</p>
                        </div>
                    </template>

                    <template x-for="(item, index) in cart" :key="item.id">
                        <div class="list-group-item py-2 px-0 border-0 border-bottom">
                            <div class="row g-2 align-items-center">
                                <!-- Image -->
                                <div class="col-auto">
                                    <span class="avatar avatar-sm rounded shadow-sm"
                                        :style="`background-image: url(${item.image})`"></span>
                                </div>

                                <!-- Name & Unit Price -->
                                <div class="col min-w-0">
                                    <div role="button" @click="openModal(item)"
                                        class="fw-bold text-dark d-block text-truncate fs-5 lh-1 mb-1"
                                        x-text="item.name"></div>
                                    <div class="text-secondary small">
                                        <template x-if="calculateItemDiscount(item) > 0">
                                            <span>
                                                <span class="text-decoration-line-through opacity-50 me-1"
                                                    x-text="formatRupiah(item.price)"></span>
                                                <span class="text-primary"
                                                    x-text="formatRupiah(item.price - (calculateItemDiscount(item) / item.qty))"></span>
                                            </span>
                                        </template>
                                        <template x-if="calculateItemDiscount(item) <= 0">
                                            <span x-text="formatRupiah(item.price)"></span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Quantity Controls -->
                                <div class="col-auto">
                                    <div class="d-flex align-items-center bg-light rounded-pill p-1 border shadow-sm" style="width: 120px;">
                                        <button class="btn btn-sm btn-icon btn-ghost-secondary border-0 rounded-circle"
                                            style="width: 24px; height: 24px;" @click="updateQty(item.id, -1)">
                                            <span class="material-symbols-outlined"
                                                style="font-size: 14px;">remove</span>
                                        </button>
                                        <input type="text" class="form-control form-control-sm border-0 bg-transparent text-center fw-bold p-0"
                                            style="box-shadow: none; font-size: 0.8rem;"
                                            :value="formatDecimal(item.qty)"
                                            @blur="updateQtyFromInput(item.id, $event.target.value)"
                                            @keydown.enter="$event.target.blur()">
                                        <button class="btn btn-sm btn-icon btn-ghost-secondary border-0 rounded-circle"
                                            style="width: 24px; height: 24px;" @click="updateQty(item.id, 1)">
                                            <span class="material-symbols-outlined"
                                                style="font-size: 14px;">add</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Total Nominal -->
                                <div class="col-auto text-end fw-bold text-dark fs-4" style="min-width: 80px;"
                                    x-text="formatRupiah((parseFloat(item.price) * parseFloat(item.qty)) - calculateItemDiscount(item))">
                                </div>

                                <!-- Delete Button -->
                                <div class="col-auto">
                                    <button class="btn btn-sm btn-icon btn-ghost-danger border-0"
                                        @click="removeFromCart(item.id)">
                                        <span class="material-symbols-outlined" style="font-size: 18px;">delete</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="card-footer mt-0 p-2">
                <div class="d-flex flex-wrap gap-1 justify-content-center">
                    <button class="btn btn-outline-info flex-fill p-1" style="min-width: 45px;" title="Diskon Global"
                        @click="openGlobalDiscountModal()">
                        <span class="material-symbols-outlined fs-3">percent_discount</span>
                    </button>
                    <button class="btn btn-outline-info flex-fill p-1" style="min-width: 45px;" title="Cashback Global"
                        @click="openGlobalCashbackModal()">
                        <span class="material-symbols-outlined fs-3">currency_exchange</span>
                    </button>
                    <button class="btn btn-outline-warning flex-fill p-1" style="min-width: 45px;" title="Tunda Transaksi"
                        @click="pauseSale()">
                        <span class="material-symbols-outlined fs-3">inactive_order</span>
                    </button>
                    <button class="btn btn-outline-secondary flex-fill p-1 position-relative" style="min-width: 45px;"
                        title="Transaksi Tertunda" x-show="heldSales.length > 0" @click="openHeldSalesModal()">
                        <span class="material-symbols-outlined fs-3">restore_page</span>
                        <span class="badge bg-info text-light badge-notification"
                            style="position: absolute; top: -5px; right: -5px; min-width: 1rem; height: 1rem; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 0.6rem;"
                            x-text="heldSales.length"></span>
                    </button>
                    <button class="btn btn-outline-danger flex-fill p-1" style="min-width: 45px;" title="Reset Keranjang"
                        @click="clearCart()">
                        <span class="material-symbols-outlined fs-3">delete_sweep</span>
                    </button>
                    @if ($cashDrawer)
                        <button class="btn btn-outline-dark flex-fill p-1" style="min-width: 45px;" title="Tutup Kasir" data-bs-toggle="modal"
                            data-bs-target="#closeCashierModal">
                            <span class="material-symbols-outlined fs-3">logout</span>
                        </button>
                    @endif
                </div>

                <div class="w-100 border-top border-bottom mt-2 py-2">
                    <div class="d-flex justify-content-between pb-1 text-secondary">
                        <span>Subtotal</span>
                        <span x-text="formatRupiah(cartTotal)"></span>
                    </div>

                    <div class="d-flex justify-content-between pb-1 text-secondary">
                        <span>Diskon (Global)</span>
                        <span x-text="formatRupiah(calculateGlobalDiscountValue())"></span>
                    </div>
                    <div class="d-flex justify-content-between pb-1 text-secondary">
                        <span>Cashback (Global)</span>
                        <span x-text="formatRupiah(calculateGlobalCashbackValue())"></span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold fs-3">
                        <span>Total</span>
                        <span x-text="formatRupiah(subtotal)"></span>
                    </div>
                </div>

                <div class="d-grid mt-2">
                    <button type="button" class="btn btn-primary" @click="processPayment"
                        :disabled="cart.length === 0">Bayar</button>
                </div>

                <div class="mt-3 pt-2 border-top">
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <span class="badge bg-light text-dark border p-1" style="font-size: 0.65rem;"><kbd>F1</kbd> Cari Produk</span>
                        <span class="badge bg-light text-dark border p-1" style="font-size: 0.65rem;"><kbd>F2</kbd> Pelanggan</span>
                        <span class="badge bg-light text-dark border p-1" style="font-size: 0.65rem;"><kbd>F3</kbd> Tunda</span>
                        <span class="badge bg-light text-dark border p-1" style="font-size: 0.65rem;"><kbd>F4</kbd> Tertunda</span>
                        <span class="badge bg-light text-dark border p-1" style="font-size: 0.65rem;"><kbd>F8</kbd> Bayar</span>
                        <span class="badge bg-light text-dark border p-1" style="font-size: 0.65rem;"><kbd>F9</kbd> Reset</span>
                        <span class="badge bg-light text-dark border p-1" style="font-size: 0.65rem;"><kbd>F10</kbd> Tutup Kasir</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('livewire.sale-pos-component.modal-produk')
    @include('livewire.sale-pos-component.modal-diskon')
    @include('livewire.sale-pos-component.modal-cashback')
    @include('livewire.sale-pos-component.modal-pembayaran')

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

    <!-- Open Cashier Modal -->
    <div wire:ignore.self class="modal modal-blur fade" id="openCashierModal" data-bs-backdrop="static"
        data-bs-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <div class="modal-content">
                <form wire:submit.prevent="openCashier">
                    <div class="modal-header">
                        <h5 class="modal-title">Buka Kasir</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Saldo Awal</label>
                            <div class="input-group" x-data="{
                                displayValue: '',
                                format(val) {
                                    if (!val) return '';
                                    return new Intl.NumberFormat('id-ID').format(val);
                                },
                                updateRaw(val) {
                                    let raw = val.replace(/\./g, '').replace(/[^0-9]/g, '');
                                    this.displayValue = this.format(raw);
                                    @this.set('openingBalance', raw);
                                }
                            }" x-init="displayValue = format($wire.openingBalance)">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control" x-model="displayValue"
                                    @input="updateRaw($event.target.value)" placeholder="0" required>
                            </div>
                            @error('openingBalance')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100">Buka Kasir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Close Cashier Modal -->
    <div wire:ignore.self class="modal modal-blur fade" id="closeCashierModal" tabindex="-1" role="dialog"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form wire:submit.prevent="closeCashier">
                    <div class="modal-header">
                        <h5 class="modal-title">Tutup Kasir</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Saldo Akhir di Tangan (Tunai)</label>
                            <div class="input-group" x-data="{
                                displayValue: '',
                                format(val) {
                                    if (!val) return '';
                                    return new Intl.NumberFormat('id-ID').format(val);
                                },
                                updateRaw(val) {
                                    let raw = val.replace(/\./g, '').replace(/[^0-9]/g, '');
                                    this.displayValue = this.format(raw);
                                    @this.set('closingBalanceManual', raw);
                                }
                            }" x-init="displayValue = format($wire.closingBalanceManual)">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control" x-model="displayValue"
                                    @input="updateRaw($event.target.value)" placeholder="0" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" wire:model="cashDrawerNote" rows="3" placeholder="Opsional..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary me-auto"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Tutup Kasir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Held Sales Modal -->
    <div class="modal modal-blur fade" id="heldSalesModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transaksi Tertunda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group list-group-flush">
                        <template x-if="heldSales.length === 0">
                            <div class="text-center p-3 text-secondary">
                                Tidak ada transaksi yang tertunda
                            </div>
                        </template>
                        <template x-for="(sale, index) in heldSales" :key="index">
                            <div class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="avatar bg-yellow-lt" x-text="index + 1"></span>
                                    </div>
                                    <div class="col text-truncate">
                                        <div class="text-reset d-block"
                                            x-text="sale.customer ? sale.customer.nama_pelanggan : 'Umum/Walk-in'">
                                        </div>
                                        <div class="d-block text-secondary text-truncate mt-n1">
                                            <span x-text="sale.date"></span> &bull;
                                            <span x-text="sale.cart.length + ' Item'"></span> &bull;
                                            <span x-text="formatRupiah(sale.total)"></span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-primary btn-sm" @click="restoreHeldSale(index)">
                                            <span class="material-symbols-outlined fs-5">restore</span>
                                            Ambil
                                        </button>
                                        <button class="btn btn-danger btn-sm ms-2" @click="removeHeldSale(index)">
                                            <span class="material-symbols-outlined fs-5">delete</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

@section('link')
    <style>
        html,
        body {
            height: 100%;
        }

        .form-selectgroup-label.whitespace-nowrap {
            display: flex !important;
            align-items: center;
            gap: 0.5rem;
        }

        .page {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        @media (max-width: 767.98px) {
            .page {
                height: auto;
                min-height: 100vh;
            }
            .main-row {
                height: auto;
                overflow: visible;
            }
            .h-100 {
                height: auto !important;
            }
            .overflow-y-auto {
                max-height: 500px;
            }
        }

        .page-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .page-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .page-body>.container-xl {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .main-row {
            flex: 1;
            overflow: auto;
            margin: 0 !important;
            display: flex;
            flex-wrap: wrap;
            position: relative;
        }

        @media (min-width: 768px) {
            .main-row {
                flex-wrap: nowrap;
                overflow: hidden;
            }
        }

        .overflow-y-auto::-webkit-scrollbar {
            display: none;
        }

        .overflow-y-auto {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .category-scroll::-webkit-scrollbar {
            display: none;
        }

        .category-scroll {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .product-card {
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .product-card .card-img {
            transition: transform 0.3s ease;
        }

        .product-card:hover .card-img {
            transform: scale(1.05);
        }

        .card-img-overlay {
            position: absolute;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.8));
        }

        .card-img-overlay::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0), rgba(0, 0, 0, 1));
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            pointer-events: none;
        }

        .product-card:hover .card-img-overlay::before {
            opacity: 1;
        }

        .card-img-overlay h5 {
            color: white !important;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
            z-index: 1;
            transition: transform 0.3s ease;
        }

        .card-img-overlay .card-text {
            z-index: 1;
            color: var(--tblr-primary) !important;
            font-weight: bold;
            transition: transform 0.3s ease;
        }

        .product-card:hover .card-img-overlay h5,
        .product-card:hover .card-img-overlay .card-text {
            transform: translateY(-5px);
        }

        .card-img-overlay .badge {
            position: absolute !important;
            top: 12px;
            right: 12px;
            background-color: rgba(0, 0, 0, 0.4);
            color: white;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }

        .qty-btn {
            width: 18px;
            height: 18px;
            padding: 0;
            border: none;
            border-radius: 0.25rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.15s;
        }

        .qty-btn:hover {
            background: var(--bs-primary);
        }

        .qty-btn .material-symbols-outlined {
            font-size: 12px;
        }

        .qty-display {
            color: white;
            font-weight: bold;
            font-size: 0.875rem;
            min-width: 14px;
            text-align: center;
        }

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
        .scanner-frame::before, .scanner-frame::after, 
        .scanner-frame div::before, .scanner-frame div::after {
            content: "";
            position: absolute;
            width: 30px;
            height: 30px;
            border-color: #2fb344;
            border-style: solid;
        }
        /* Corners */
        .scanner-frame {
            border: 2px solid rgba(255,255,255,0.2);
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
@endsection

@section('script')
    <script>
        let customerTomSelect;

        document.addEventListener('alpine:init', () => {
            Alpine.data('posSystem', () => ({
                cart: [],
                selectedItem: null,
                selectedCustomer: @js($defaultCustomer),
                customerSearch: '',
                customerResults: [],
                showCustomerResults: false,
                checkOut: {
                    bayar: '',
                    kembalian: 0,
                    payment_method: 'tunai',
                    no_rekening: '',
                    note: ''
                },
                globalDiskon: {
                    jenis: 'nominal',
                    jumlah: 0
                },
                globalCashback: {
                    type: 'fixed',
                    value: 0
                },
                // Scanner State
                html5QrCode: null,
                lastScannedCode: null,
                lastScannedTime: 0,
                lastScannedName: '',
                currentCameraId: null,
                cameras: [],
                heldSales: [],
                init() {
                    // Check if shift/session changed
                    let currentDrawerId = @js($cashDrawer ? $cashDrawer->id : null);
                    let currentUserId = @js(auth()->id());
                    let currentSessionId = @js(session()->getId());
                    
                    let storedDrawerId = localStorage.getItem('pos_drawer_id');
                    let storedUserId = localStorage.getItem('pos_user_id');
                    let storedSessionId = localStorage.getItem('pos_session_id');

                    if (storedUserId != currentUserId || storedDrawerId != currentDrawerId || storedSessionId != currentSessionId) {
                        localStorage.removeItem('pos_cart');
                        // pos_held_sales sengaja tidak dihapus sesuai permintaan user
                        localStorage.setItem('pos_drawer_id', currentDrawerId);
                        localStorage.setItem('pos_user_id', currentUserId);
                        localStorage.setItem('pos_session_id', currentSessionId);
                    }

                    let loadedCart = JSON.parse(localStorage.getItem('pos_cart')) || [];
                    this.cart = loadedCart.map(item => ({
                        ...item,
                        original_price: item.original_price || item
                            .price,
                        diskon: item.diskon || {
                            jenis: 'nominal',
                            jumlah: 0,
                            nominal: 0
                        },
                        cashback: item.cashback || {
                            jenis: 'nominal',
                            jumlah: 0,
                            nominal: 0
                        }
                    }));
                    this.$watch('cart', (val) => localStorage.setItem('pos_cart', JSON.stringify(val)));
                    this.$watch('selectedCustomer', (customer) => {
                        this.updateCartPrices();

                        if (customer && customer.customer_group && customer.customer_group
                            .diskon_persen) {
                            this.globalDiskon = {
                                jenis: 'persen',
                                jumlah: parseFloat(customer.customer_group.diskon_persen)
                            };
                        } else {
                            this.globalDiskon = {
                                jenis: 'nominal',
                                jumlah: 0
                            };
                        }
                    });

                    let storedHeldSales = localStorage.getItem('pos_held_sales');
                    if (storedHeldSales) {
                        this.heldSales = JSON.parse(storedHeldSales);
                    }
                    this.$watch('heldSales', (val) => localStorage.setItem('pos_held_sales', JSON
                        .stringify(val)));

                    this.resetCheckout();

                    this.$watch('checkOut.payment_method', (value) => {
                        if (value === 'transfer' && @js($defaultTransferAccount)) {
                            this.checkOut.no_rekening = @js($defaultTransferAccount);
                        } else if (value === 'qris' && @js($defaultQrisAccount)) {
                            this.checkOut.no_rekening = @js($defaultQrisAccount);
                        }
                    });
                },

                getSpecialPrice(productId) {
                    if (!this.selectedCustomer || !this.selectedCustomer.customer_group || !this
                        .selectedCustomer
                        .customer_group.product_prices) return null;

                    let today = new Date().toISOString().split('T')[0];

                    let special = this.selectedCustomer.customer_group.product_prices.find(p => {
                        if (p.product_id !== productId) return false;
                        if (p.tanggal_mulai && p.tanggal_mulai > today) return false;
                        if (p.tanggal_akhir && p.tanggal_akhir < today) return false;
                        return true;
                    });

                    return special ? parseFloat(special.harga_spesial) : null;
                },

                updateCartPrices() {
                    this.cart = this.cart.map(item => {
                        let original = item.original_price !== undefined ? parseFloat(item
                                .original_price) :
                            parseFloat(item.price);
                        let special = this.getSpecialPrice(item.id);

                        return {
                            ...item,
                            original_price: original,
                            price: special !== null ? special : original
                        };
                    });
                },

                addToCart(product) {
                    const existingItem = this.cart.find(item => item.id === product.id);
                    if (existingItem) {
                        existingItem.qty++;
                    } else {
                        let originalPrice = parseFloat(product.price);
                        let specialPrice = this.getSpecialPrice(product.id);
                        let finalPrice = specialPrice !== null ? specialPrice : originalPrice;

                        this.cart.push({
                            ...product,
                            qty: 1,
                            original_price: originalPrice,
                            price: finalPrice,
                            diskon: {
                                jenis: 'nominal',
                                jumlah: 0,
                                nominal: 0
                            },
                            cashback: {
                                jenis: 'nominal',
                                jumlah: 0,
                                nominal: 0
                            }
                        });
                    }
                },

                updateQty(id, delta) {
                    const item = this.cart.find(i => i.id === id);
                    if (!item) return;

                    let newQty = parseFloat(item.qty) + delta;

                    // Cek ijin desimal dari satuan
                    let allowDecimal = false;
                    if (item.unit) {
                        let d = item.unit.desimal;
                        if (d == 1 || d == '1' || d === true || (typeof d === 'string' && d.toLowerCase() === 'ya')) {
                            allowDecimal = true;
                        }
                    }

                    if (!allowDecimal && newQty % 1 !== 0) {
                        newQty = Math.floor(newQty);
                    }

                    if (newQty <= 0) {
                        this.removeFromCart(id);
                    } else {
                        item.qty = newQty;
                    }
                },

                updateQtyFromInput(id, value) {
                    const item = this.cart.find(i => i.id === id);
                    if (!item) return;

                    let newQty = this.parseNumber(value);

                    // Cek ijin desimal dari satuan
                    let allowDecimal = false;
                    if (item.unit) {
                        let d = item.unit.desimal;
                        if (d == 1 || d == '1' || d === true || (typeof d === 'string' && d.toLowerCase() === 'ya')) {
                            allowDecimal = true;
                        }
                    }

                    if (!allowDecimal && newQty % 1 !== 0) {
                        newQty = Math.floor(newQty);
                    }

                    if (newQty <= 0) {
                        this.removeFromCart(id);
                    } else {
                        item.qty = newQty;
                    }
                },


                removeFromCart(id) {
                    Swal.fire({
                        title: 'Hapus dari keranjang?',
                        text: 'Anda yakin ingin menghapus produk ini dari keranjang?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.cart = this.cart.filter(item => item.id !== id);
                            if (this.selectedItem && this.selectedItem.id === id) {
                                this.selectedItem = null;
                                $('#productModal').modal('hide');
                            }
                        }
                    });
                },

                clearCart() {
                    Swal.fire({
                        title: 'Kosongkan keranjang?',
                        text: 'Anda yakin ingin menghapus semua produk dari keranjang?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.cart = [];
                            this.selectedItem = null;
                            this.resetGlobalDiscounts();
                        }
                    });
                },

                openModal(item) {
                    this.selectedItem = this.cart.find(i => i.id === item.id);
                    $('#productModal').modal('show');
                },

                openGlobalDiscountModal() {
                    $('#globalDiscountModal').modal('show');
                },

                openGlobalCashbackModal() {
                    $('#globalCashbackModal').modal('show');
                },

                openHeldSalesModal() {
                    $('#heldSalesModal').modal('show');
                },

                pauseSale() {
                    if (this.cart.length === 0) {
                        Toast.fire({
                            icon: 'warning',
                            title: 'Keranjang kosong'
                        });
                        return;
                    }

                    Swal.fire({
                        title: 'Tunda Transaksi?',
                        text: 'Transaksi akan disimpan sementara dan keranjang akan dikosongkan.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Tunda',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const saleData = {
                                cart: this.cart,
                                customer: this.selectedCustomer,
                                globalDiskon: this.globalDiskon,
                                globalCashback: this.globalCashback,
                                date: new Date().toLocaleString('id-ID'),
                                total: this.subtotal
                            };

                            this.heldSales.push(saleData);

                            this.cart = [];
                            this.selectedItem = null;
                            this.selectedCustomer = null;
                            this.resetGlobalDiscounts();

                            // Clear TomSelect if exists
                            let tom = document.getElementById('customerSearch').tomselect;
                            if (tom) tom.clear();

                            Toast.fire({
                                icon: 'success',
                                title: 'Transaksi ditunda'
                            });
                        }
                    });
                },

                restoreHeldSale(index) {
                    if (this.cart.length > 0) {
                        Swal.fire({
                            title: 'Keranjang tidak kosong',
                            text: 'Pulihkan transaksi akan menimpa keranjang saat ini. Lanjutkan?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Timpa',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                this.doRestore(index);
                            }
                        });
                    } else {
                        this.doRestore(index);
                    }
                },

                doRestore(index) {
                    const sale = this.heldSales[index];

                    this.cart = sale.cart;
                    this.selectedCustomer = sale.customer;
                    this.globalDiskon = sale.globalDiskon || {
                        jenis: 'nominal',
                        jumlah: 0
                    };
                    this.globalCashback = sale.globalCashback || {
                        jenis: 'nominal',
                        jumlah: 0
                    };

                    if (this.selectedCustomer) {
                        if (customerTomSelect) {
                            try {
                                customerTomSelect.clear(true);
                                const customerId = String(this.selectedCustomer.id);

                                if (!customerTomSelect.options[customerId]) {
                                    const customerData = {
                                        id: customerId,
                                        nama_pelanggan: this.selectedCustomer.nama_pelanggan,
                                        alamat: this.selectedCustomer.alamat,
                                        business_id: this.selectedCustomer.business_id,
                                        created_at: this.selectedCustomer.created_at,
                                        customer_group: this.selectedCustomer.customer_group,
                                        customer_group_id: this.selectedCustomer.customer_group_id,
                                        kode_pelanggan: this.selectedCustomer.kode_pelanggan,
                                        limit_hutang: this.selectedCustomer.limit_hutang,
                                        no_hp: this.selectedCustomer.no_hp,
                                        password: this.selectedCustomer.password,
                                        updated_at: this.selectedCustomer.updated_at,
                                        username: this.selectedCustomer.username,
                                        kode_pelanggan: this.selectedCustomer.kode_pelanggan,
                                        limit_hutang: this.selectedCustomer.limit_hutang,
                                        no_hp: this.selectedCustomer.no_hp,
                                        password: this.selectedCustomer.password,
                                        updated_at: this.selectedCustomer.updated_at,
                                        username: this.selectedCustomer.username
                                    };
                                    customerTomSelect.addOption(customerData);
                                }

                                customerTomSelect.setValue(customerId, true);
                            } catch (e) {
                                console.error('TomSelect update failed:', e);
                                console.error('Error message:', e.message);
                                console.error('Customer data:', this.selectedCustomer);
                            }
                        }
                    }

                    this.heldSales.splice(index, 1);
                    $('#heldSalesModal').modal('hide');

                    Toast.fire({
                        icon: 'success',
                        title: 'Transaksi dipulihkan'
                    });
                },

                removeHeldSale(index) {
                    Swal.fire({
                        title: 'Hapus Transaksi Tertunda?',
                        text: 'Data transaksi ini akan dihapus permanen.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Ya, Hapus',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.heldSales.splice(index, 1);
                        }
                    });
                },

                resetGlobalDiscounts() {
                    this.globalDiskon = {
                        jenis: 'nominal',
                        jumlah: 0
                    };
                    this.globalCashback = {
                        jenis: 'nominal',
                        jumlah: 0
                    };
                },

                calculateItemDiscount(item) {
                    if (!item || !item.diskon) return 0;
                    let price = parseFloat(item.price);
                    let qty = parseFloat(item.qty);
                    let discountVal = parseFloat(item.diskon.jumlah) || 0;

                    if (item.diskon.jenis === 'nominal') {
                        return discountVal;
                    } else {
                        return (price * qty * discountVal) / 100;
                    }
                },

                calculateItemCashback(item) {
                    if (!item || !item.cashback) return 0;
                    let price = parseFloat(item.price);
                    let qty = parseFloat(item.qty);
                    let cashbackVal = parseFloat(item.cashback.jumlah) || 0;

                    if (item.cashback.jenis === 'nominal') {
                        return cashbackVal;
                    } else {
                        return (price * qty * cashbackVal) / 100;
                    }
                },

                calculateGlobalDiscountValue() {
                    let base = parseFloat(this.grossTotal) - parseFloat(this.totalDiscount);

                    if (this.globalDiskon.jenis === 'nominal') {
                        return parseFloat(this.globalDiskon.jumlah) || 0;
                    } else {
                        return (base * (parseFloat(this.globalDiskon.jumlah) || 0)) / 100;
                    }
                },

                calculateGlobalCashbackValue() {
                    let base = parseFloat(this.grossTotal) - parseFloat(this.totalDiscount);

                    if (this.globalCashback.jenis === 'nominal') {
                        return parseFloat(this.globalCashback.jumlah) || 0;
                    } else {
                        return (base * (parseFloat(this.globalCashback.jumlah) || 0)) / 100;
                    }
                },

                get grossTotal() {
                    return this.cart.reduce((sum, item) => sum + (parseFloat(item.price) * parseFloat(item.qty)), 0);
                },

                get cartTotal() {
                    return parseFloat(this.grossTotal) - parseFloat(this.totalDiscount);
                },

                get totalDiscount() {
                    return this.cart.reduce((sum, item) => {
                        return sum + this.calculateItemDiscount(item);
                    }, 0);
                },

                get totalCashback() {
                    return this.cart.reduce((sum, item) => {
                        return sum + this.calculateItemCashback(item);
                    }, 0);
                },

                get subtotal() {
                    let val = parseFloat(this.grossTotal) - parseFloat(this.totalDiscount) - parseFloat(this
                        .calculateGlobalDiscountValue());
                    return val > 0 ? val : 0;
                },

                formatDecimal(num) {
                    if (num === null || num === undefined || num === '') return '';
                    let val = (typeof num === 'string') ? this.parseNumber(num) : num;
                    return new Intl.NumberFormat('id-ID', {
                        maximumFractionDigits: 3,
                        minimumFractionDigits: 0
                    }).format(val);
                },

                formatRupiah(num) {
                    if (num === null || num === undefined || num === '') return '';
                    let val = (typeof num === 'string') ? this.parseNumber(num) : num;
                    return new Intl.NumberFormat('id-ID', {
                        maximumFractionDigits: 2,
                        minimumFractionDigits: 0
                    }).format(val);
                },

                parseNumber(val) {
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

                processPayment() {
                    if (this.cart.length === 0) return;

                    if (!this.checkOut.bayar) {
                        this.checkOut.bayar = this.formatRupiah(this.subtotal);
                    }
                    $('#checkoutModal').modal('show');
                    setTimeout(() => {
                        const input = document.getElementById('paymentInput');
                        if (input) input.select();
                    }, 500);
                },

                getSuggestedAmounts() {
                    let total = this.subtotal;
                    let suggestions = new Set();

                    [50000, 100000, 200000, 500000, 1000000].forEach(base => {
                        let sugg = Math.ceil(total / base) * base;
                        if (sugg === total) sugg += base;
                        suggestions.add(sugg);
                    });

                    return Array.from(suggestions).sort((a, b) => a - b).filter(a => a > total);
                },

                calculateChange() {
                    let pay = this.parseNumber(this.checkOut.bayar) || 0;
                    return pay - this.subtotal;
                },

                submitSale() {
                    if ((this.checkOut.bayar === '' || this.parseNumber(this.checkOut.bayar) < 0) && this
                        .checkOut.payment_method !== 'credit') {
                        Toast.fire({
                            icon: 'error',
                            title: 'Jumlah bayar tidak valid'
                        });
                        return;
                    }

                    if (!this.selectedCustomer) {
                        Toast.fire({
                            icon: 'error',
                            title: 'Pelanggan tidak valid'
                        });
                        return;
                    }

                    const payload = {
                        products: this.cart,
                        customer_id: this.selectedCustomer ? this.selectedCustomer.id : null,
                        grandTotal: this.subtotal,
                        bayar: this.parseNumber(this.checkOut.bayar),
                        kembalian: this.calculateChange(),
                        payment_method: this.checkOut.payment_method,
                        no_rekening: this.checkOut.no_rekening,
                        note: this.checkOut.note,
                        globalDiskon: this.globalDiskon,
                        globalCashback: this.globalCashback
                    };

                    this.$wire.saveSale(payload);
                    $('#checkoutModal').modal('hide');
                },

                resetCheckout() {
                    this.checkOut = {
                        bayar: '',
                        kembalian: 0,
                        payment_method: 'tunai',
                        no_rekening: '',
                        note: ''
                    };
                },

                handleShortcuts(e) {
                    const shortcuts = ['F1', 'F2', 'F3', 'F4', 'F8', 'F9', 'F10'];
                    if (!shortcuts.includes(e.key)) return;

                    e.preventDefault();

                    switch(e.key) {
                        case 'F1':
                            if (productSearchTomSelect) {
                                productSearchTomSelect.focus();
                                productSearchTomSelect.open();
                            }
                            break;
                        case 'F2':
                            if (customerTomSelect) {
                                customerTomSelect.focus();
                                customerTomSelect.open();
                            }
                            break;
                        case 'F3':
                            this.pauseSale();
                            break;
                        case 'F4':
                            this.openHeldSalesModal();
                            break;
                        case 'F8':
                            this.processPayment();
                            break;
                        case 'F9':
                            this.clearCart();
                            break;
                        case 'F10':
                            const closeBtn = document.querySelector('[title="Tutup Kasir"]');
                            if (closeBtn) closeBtn.click();
                            break;
                    }
                },

                // Scanner Methods
                async openScanner() {
                    $('#scannerModal').modal('show');

                    this.lastScannedCode = null;
                    this.lastScannedName = '';
                    window.posLastScanned = { code: null, time: 0 };

                    setTimeout(async () => {
                        try {
                            this.html5QrCode = new Html5Qrcode("reader");
                            const devices = await Html5Qrcode.getCameras();
                            if (devices && devices.length > 0) {
                                this.cameras = devices;
                                // Prefer back camera
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
                    this.isScanningEnabled = false;
                    const config = { fps: 10, qrbox: { width: 250, height: 250 } };
                    await this.html5QrCode.start(
                        this.currentCameraId, 
                        config, 
                        (decodedText) => this.onScanSuccess(decodedText)
                    );
                    // Wait 1 second before allowing scans to process (camera warm up / focus)
                    setTimeout(() => { this.isScanningEnabled = true; }, 1000);
                },

                async toggleCamera() {
                    if (this.cameras.length < 2) return;
                    await this.html5QrCode.stop();
                    let currentIndex = this.cameras.findIndex(c => c.id === this.currentCameraId);
                    let nextIndex = (currentIndex + 1) % this.cameras.length;
                    this.currentCameraId = this.cameras[nextIndex].id;
                    this.startScanning();
                },

                isScanningEnabled: false,
                onScanSuccess(decodedText) {
                    if (!this.isScanningEnabled) return;

                    const now = Date.now();
                    
                    // Use window object to persist state across Livewire re-renders
                    if (!window.posLastScanned) {
                        window.posLastScanned = { code: null, time: 0 };
                    }

                    // 1. Same-item loop protection (5 seconds)
                    if (decodedText === window.posLastScanned.code && (now - window.posLastScanned.time) < 5000) {
                        return;
                    }

                    // 2. General cooldown (1.5 seconds)
                    if ((now - window.posLastScanned.time) < 1500) {
                        return;
                    }

                    this.isScanningEnabled = false; 
                    window.posLastScanned.code = decodedText;
                    window.posLastScanned.time = now;
                    
                    // Also update Alpine state for UI consistency
                    this.lastScannedCode = decodedText;
                    this.lastScannedTime = now;

                    // Visual feedback
                    const reader = document.getElementById('reader');
                    reader.classList.add('scanner-success-flash');
                    setTimeout(() => reader.classList.remove('scanner-success-flash'), 500);

                    // Audio feedback
                    const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3');
                    audio.play().catch(() => {});

                    // Haptic feedback (vibration) for mobile
                    if (navigator.vibrate) {
                        navigator.vibrate(200);
                    }

                    // Process Scan
                    this.$wire.scanProduct(decodedText).then(res => {
                        if (res.success) {
                            this.addToCart(res.product);
                            this.lastScannedName = res.product.name;
                            
                            // Explicitly clear main TomSelect search box if it exists
                            if (typeof productSearchTomSelect !== 'undefined') {
                                productSearchTomSelect.clear(true);
                                productSearchTomSelect.setTextboxValue('');
                            }

                            setTimeout(() => { if(window.posLastScanned && window.posLastScanned.code === decodedText) this.lastScannedName = '' }, 3000);
                        } else {
                            Toast.fire({ icon: 'warning', title: res.message });
                        }
                        
                        // Re-enable scanning after the general cooldown
                        setTimeout(() => { this.isScanningEnabled = true; }, 1500);
                    }).catch(() => {
                        this.isScanningEnabled = true;
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
            }));
        });

        window.addEventListener('open-receipt', (event) => {
            window.open(event.detail.url, '_blank');
        });

        document.addEventListener('livewire:initialized', () => {
            Livewire.on('sale-stored', () => {
                window.dispatchEvent(new CustomEvent('sale-stored'));
            });

            Livewire.on('close-modal', (event) => {
                $(`#${event.id}`).modal('hide');
                if (event.id === 'openCashierModal' || event.id === 'closeCashierModal') {
                    window.location.reload();
                }
            });

            Livewire.on('add-to-cart', (event) => {
                let el = document.querySelector('[x-data]');
                if (el) {
                    let raw = event.product;
                    let productData = {
                        id: raw.id,
                        name: raw.nama_produk,
                        price: raw.harga_jual,
                        stock: raw.stok_aktual,
                        unit: raw.unit,
                        image: (raw.gambar && raw.gambar !== 'products/no-image.png') ? ('/storage/' +
                            raw.gambar) : 'https://placehold.co/400x400?text=No+Image',
                    };

                    Alpine.$data(el).addToCart(productData);

                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                    });
                    Toast.fire({
                        icon: 'success',
                        title: 'Produk ditambahkan: ' + raw.nama_produk
                    });
                }
            });

            // Initialize TomSelects after Livewire/Alpine are ready
            if (document.getElementById('customerSearch')) {
                customerTomSelect = new TomSelect('#customerSearch', {
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
                        if (el && value) {
                            let data = this.options[value];
                            if (data) {
                                Alpine.$data(el).selectedCustomer = JSON.parse(JSON.stringify(data));
                            }
                        } else if (el) {
                            Alpine.$data(el).selectedCustomer = null;
                        }
                    }
                });

                @if ($defaultCustomer)
                    customerTomSelect.addOption(@js($defaultCustomer));
                    customerTomSelect.setValue(@js($defaultCustomer->id));
                @endif
            }

            if (document.getElementById('productSearchSelect')) {
                productSearchTomSelect = new TomSelect('#productSearchSelect', {
                    valueField: 'id',
                    labelField: 'nama_produk',
                    searchField: ['nama_produk', 'sku'],
                    closeAfterSelect: false,
                    load: function(query, callback) {
                        if (query.length < 2) return callback();
                        @this.call('loadProducts', query, 0).then(res => {
                            callback(res.data);
                            // Autoselect if exactly 1 match (helpful for barcode scanners)
                            if (res.data.length === 1) {
                                let item = res.data[0];
                                // Check if query exactly matches SKU or Barcode
                                if (query === item.sku || query === item.barcode) {
                                    this.addItem(item.id);
                                    this.blur();
                                }
                            }
                        }).catch(() => callback());
                    },
                    render: {
                        option: function(data, escape) {
                            let formattedStok = new Intl.NumberFormat('id-ID', {
                                maximumFractionDigits: 2
                            }).format(data.stok_aktual);

                            return `<div class="d-flex flex-column py-1">
                                <div class="fw-bold text-dark">${escape(data.nama_produk)}</div>
                                <div class="text-muted small">
                                    ${escape(data.category ? data.category.nama_kategori : '-')} | ${escape(data.brand ? data.brand.nama_merek : '-')}
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-secondary">${escape(data.sku || '-')}</small>
                                    <span class="badge bg-primary-lt">Rp ${new Intl.NumberFormat('id-ID').format(data.harga_jual)}</span>
                                </div>
                                <small class="text-muted mt-1">Stok: ${formattedStok} ${data.unit ? data.unit.nama_satuan : ''}</small>
                            </div>`;
                        },
                        item: function(data, escape) {
                            return `<div>${escape(data.nama_produk)}</div>`;
                        }
                    },
                    onChange: function(value) {
                        if (!value) return;
                        let el = document.querySelector('[x-data]');
                        if (el) {
                            let raw = this.options[value];
                            let productData = {
                                id: raw.id,
                                name: raw.nama_produk,
                                price: parseFloat(raw.harga_jual),
                                stock: parseFloat(raw.stok_aktual),
                                unit: raw.unit,
                                image: (raw.gambar && raw.gambar !== 'products/no-image.png') ? (
                                        '/storage/' + raw.gambar) :
                                    'https://placehold.co/400x400?text=No+Image',
                            };
                            Alpine.$data(el).addToCart(productData);
                            setTimeout(() => {
                                this.clear(true);
                                this.setTextboxValue('');
                                this.clearOptions();
                                // this.close();
                            }, 50);
                        }
                    }
                });
            }

            if (document.getElementById('bankAccountSelectPos')) {
                new TomSelect('#bankAccountSelectPos', {
                    create: false,
                    sortField: {
                        field: "text",
                        direction: "asc"
                    },
                    onChange: function(value) {
                        let el = document.querySelector('[x-data]');
                        if (el) {
                            Alpine.$data(el).checkOut.no_rekening = value;
                        }
                    }
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const scrollContainer = document.querySelector('.overflow-y-auto');
            if (scrollContainer) {
                let isDown = false;
                let startY, scrollTop;
                scrollContainer.addEventListener('mousedown', (e) => {
                    isDown = true;
                    scrollContainer.style.userSelect = 'none';
                    startY = e.pageY - scrollContainer.offsetTop;
                    scrollTop = scrollContainer.scrollTop;
                });
                scrollContainer.addEventListener('mouseleave', () => isDown = false);
                scrollContainer.addEventListener('mouseup', () => isDown = false);
                scrollContainer.addEventListener('mousemove', (e) => {
                    if (!isDown) return;
                    e.preventDefault();
                    const y = e.pageY - scrollContainer.offsetTop;
                    const walk = (y - startY) * 2;
                    scrollContainer.scrollTop = scrollTop - walk;
                });
            }

            const categoryScroll = document.querySelector('.category-scroll');
            if (categoryScroll) {
                categoryScroll.addEventListener('wheel', (e) => {
                    if (e.deltaY !== 0) {
                        e.preventDefault();
                        categoryScroll.scrollLeft += e.deltaY;
                    }
                });
            }
        });
    </script>
@endsection
