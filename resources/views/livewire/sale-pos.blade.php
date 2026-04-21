<div class="row main-row" x-data="posSystem()" @sale-stored.window="cart = []">
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
    <div class="col-6 col-md-7 col-lg-8 d-flex flex-column h-100">
        <div class="mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="input-icon mb-3">
                        <input type="text" value="" class="form-control" placeholder="Cari Produk..."
                            wire:model.live.debounce.500ms="searchProduct" />
                        <span class="input-icon-addon">
                            <span class="material-symbols-outlined">
                                search
                            </span>
                        </span>
                    </div>

                    <div class="w-100 overflow-x-auto category-scroll p-1">
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
        <div class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden">
            <div class="row">
                @foreach ($products as $product)
                    <div class="col-6 col-md-4 col-lg-3 mb-3">
                        <div class="card text-bg-dark border-0 shadow-sm product-card"
                            @click="addToCart({
                                id: {{ $product->id }},
                                name: '{{ addslashes($product->nama_produk) }}',
                                price: {{ $product->harga_jual }},
                                stock: {{ $product->stok_aktual }},
                                image: '{{ asset('storage/' . $product->gambar) }}'
                            })">
                            <img src="{{ asset('storage/' . $product->gambar) }}" class="card-img"
                                alt="{{ $product->nama_produk }}">
                            <div class="card-img-overlay d-flex flex-column justify-content-end">
                                <h5 class="card-title mb-1">{{ $product->nama_produk }}</h5>
                                <div class="card-text">Rp {{ number_format($product->harga_jual, 0, ',', '.') }}</div>
                                <span class="badge">{{ $product->stok_aktual }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{ $products->links('components.pos-pagination') }}
    </div>

    <div class="col-6 col-md-5 col-lg-4 overflow-hidden" wire:ignore>
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
                        <div class="list-group-item py-3 px-0">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <a href="#">
                                        <span class="avatar avatar-1"
                                            :style="`background-image: url(${item.image})`"></span>
                                    </a>
                                </div>
                                <div class="col">
                                    <div role="button" @click="openModal(item)"
                                        class="fw-bold text-primary d-block text-truncate" x-text="item.name"></div>
                                    <div class="d-block text-truncate">
                                        <!-- Price Display Logic -->
                                        <template x-if="calculateItemDiscount(item) > 0">
                                            <div class="lh-1">
                                                <small
                                                    class="text-secondary opacity-75 text-decoration-line-through me-1"
                                                    style="font-size: 0.7em;" x-text="formatRupiah(item.price)"></small>
                                                <span style="font-size: 0.8em;"
                                                    x-text="formatRupiah(item.price - (calculateItemDiscount(item) / item.qty))"></span>
                                            </div>
                                        </template>
                                        <template x-if="calculateItemDiscount(item) <= 0">
                                            <span class="text-secondary" x-text="formatRupiah(item.price)"></span>
                                        </template>
                                    </div>
                                </div>
                                <div class="col-4 align-self-sm-start text-end">
                                    <span class="fw-bold"
                                        x-text="formatRupiah((item.price * item.qty) - calculateItemDiscount(item))"></span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <div class="d-flex align-items-center gap-2 bg-primary rounded p-1">
                                        <button class="qty-btn" @click="updateQty(item.id, -1)">
                                            <span class="material-symbols-outlined">remove</span>
                                        </button>
                                        <span class="qty-display" x-text="item.qty"></span>
                                        <button class="qty-btn" @click="updateQty(item.id, 1)">
                                            <span class="material-symbols-outlined">add</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center">
                                    <a href="#" class="link-danger link-offset-2 link-underline-opacity-0"
                                        @click.prevent="removeFromCart(item.id)">
                                        <span class="material-symbols-outlined">delete</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="card-footer mt-0">
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-info d-flex flex-column align-items-center flex-fill"
                        @click="openGlobalDiscountModal()">
                        <span class="material-symbols-outlined fs-1">
                            percent_discount
                        </span>
                    </a>
                    <a class="btn btn-outline-info d-flex flex-column align-items-center flex-fill"
                        @click="openGlobalCashbackModal()">
                        <span class="material-symbols-outlined fs-1">
                            currency_exchange
                        </span>
                    </a>
                    <a class="btn btn-outline-warning d-flex flex-column align-items-center flex-fill"
                        @click="pauseSale()">
                        <span class="material-symbols-outlined fs-1">
                            inactive_order
                        </span>
                    </a>
                    <a class="btn btn-outline-secondary d-flex flex-column align-items-center flex-fill position-relative"
                        x-show="heldSales.length > 0" @click="openHeldSalesModal()">
                        <span class="material-symbols-outlined fs-1">
                            restore_page
                        </span>
                        <span class="badge bg-info text-light badge-notification"
                            style="position: absolute; top: 0; right: 0; width: 1.5rem; height: 1.5rem; display: flex; align-items: center; justify-content: center;"
                            x-text="heldSales.length"></span>
                    </a>
                    <a class="btn btn-outline-danger d-flex flex-column align-items-center flex-fill"
                        @click="clearCart()">
                        <span class="material-symbols-outlined fs-1">
                            delete_sweep
                        </span>
                    </a>
                </div>

                @if ($cashDrawer)
                    <div class="mt-3 p-3 bg-blue-lt rounded border border-blue-subtle">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="btn btn-success btn-sm">Kasir Terbuka</span>
                            </div>
                            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal"
                                data-bs-target="#closeCashierModal">
                                Tutup Kasir
                            </button>
                        </div>
                        <div class="space-y-1">
                            <div class="d-flex align-items-center text-secondary small">
                                <span class="material-symbols-outlined fs-5 me-2">person</span>
                                <span>{{ auth()->user()->nama_lengkap }}</span>
                            </div>
                            <div class="d-flex align-items-center text-secondary small">
                                <span class="material-symbols-outlined fs-5 me-2">schedule</span>
                                <span>{{ \Carbon\Carbon::parse($cashDrawer->tanggal_buka)->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                @endif

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
            </div>
        </div>
    </div>

    @include('livewire.sale-pos-component.modal-produk')
    @include('livewire.sale-pos-component.modal-diskon')
    @include('livewire.sale-pos-component.modal-cashback')
    @include('livewire.sale-pos-component.modal-pembayaran')

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
            overflow: hidden;
            margin: 0 !important;
            display: flex;
            flex-wrap: nowrap;
            position: relative;
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
    </style>
@endsection

@section('script')
    <script>
        let customerTomSelect;

        document.addEventListener('alpine:init', () => {
            Alpine.data('posSystem', () => ({
                cart: [],
                selectedItem: null,
                selectedCustomer: null,
                customerSearch: '',
                customerResults: [],
                showCustomerResults: false,
                checkOut: {
                    bayar: '',
                    kembalian: 0,
                    payment_method: 'tunai',
                    note: ''
                },
                globalDiskon: {
                    jenis: 'nominal',
                    jumlah: 0
                },
                globalCashback: {
                    jenis: 'nominal',
                    jumlah: 0
                },
                heldSales: [],
                init() {
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
                        if (existingItem.qty < product.stock) {
                            existingItem.qty++;
                        } else {
                            Toast.fire({
                                icon: 'error',
                                title: 'Stok tidak mencukupi'
                            });
                        }
                    } else {
                        if (product.stock > 0) {
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
                    }
                },

                updateQty(id, delta) {
                    const item = this.cart.find(i => i.id === id);
                    if (!item) return;

                    const newQty = parseFloat(item.qty) + delta;
                    if (newQty <= 0) {
                        this.removeFromCart(id);
                    } else if (newQty <= item.stock) {
                        item.qty = newQty;
                    } else {
                        Toast.fire({
                            icon: 'error',
                            title: 'Stok tidak mencukupi'
                        });
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
                    let base = this.grossTotal - this.totalDiscount;

                    if (this.globalDiskon.jenis === 'nominal') {
                        return parseFloat(this.globalDiskon.jumlah) || 0;
                    } else {
                        return (base * (parseFloat(this.globalDiskon.jumlah) || 0)) / 100;
                    }
                },

                calculateGlobalCashbackValue() {
                    let base = this.grossTotal - this.totalDiscount;

                    if (this.globalCashback.jenis === 'nominal') {
                        return parseFloat(this.globalCashback.jumlah) || 0;
                    } else {
                        return (base * (parseFloat(this.globalCashback.jumlah) || 0)) / 100;
                    }
                },

                get grossTotal() {
                    return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
                },

                get cartTotal() {
                    return this.grossTotal - this.totalDiscount;
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
                    let val = this.grossTotal - this.totalDiscount - this
                        .calculateGlobalDiscountValue();
                    return val > 0 ? val : 0;
                },

                formatRupiah(num) {
                    return new Intl.NumberFormat('en-US').format(num || 0);
                },

                parseFormatted(val) {
                    if (typeof val === 'number') return val;
                    return parseFloat(String(val).replace(/,/g, '')) || 0;
                },

                processPayment() {
                    if (this.cart.length === 0) return;

                    if (!this.checkOut.bayar) {
                        this.checkOut.bayar = this.subtotal;
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
                    let pay = parseFloat(this.checkOut.bayar) || 0;
                    return pay - this.subtotal;
                },

                submitSale() {
                    if ((this.checkOut.bayar === '' || parseFloat(this.checkOut.bayar) < 0) && this
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
                        bayar: this.checkOut.bayar,
                        kembalian: this.calculateChange(),
                        payment_method: this.checkOut.payment_method,
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
                        note: ''
                    };
                }
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
                
                // Refresh if it's cashier related to ensure full state sync
                if (event.id === 'openCashierModal' || event.id === 'closeCashierModal') {
                    window.location.reload();
                }
            });

            Livewire.on('add-to-cart', (event) => {
                let el = document.querySelector('[x-data]');
                if (el) {
                    let raw = event.product;

                    console.log(raw);
                    let productData = {
                        id: raw.id,
                        name: raw.nama_produk,
                        price: raw.harga_jual,
                        stock: raw.stok_aktual,
                        image: '/storage/' + raw.gambar,
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
        });

        document.addEventListener('DOMContentLoaded', function() {
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
                        if (el) {
                            let data = this.options[value];
                            Alpine.$data(el).selectedCustomer = data || null;
                        }
                    }
                });
            }

            const scrollContainer = document.querySelector('.overflow-y-auto');
            if (scrollContainer) {
                let isDown = false;
                let startY;
                let scrollTop;

                scrollContainer.addEventListener('mousedown', (e) => {
                    isDown = true;
                    scrollContainer.style.userSelect = 'none';
                    startY = e.pageY - scrollContainer.offsetTop;
                    scrollTop = scrollContainer.scrollTop;
                });

                scrollContainer.addEventListener('mouseleave', () => {
                    isDown = false;
                });

                scrollContainer.addEventListener('mouseup', () => {
                    isDown = false;
                });

                scrollContainer.addEventListener('mousemove', (e) => {
                    if (!isDown) return;
                    e.preventDefault();
                    const y = e.pageY - scrollContainer.offsetTop;
                    const walk = (y - startY) * 2;
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
