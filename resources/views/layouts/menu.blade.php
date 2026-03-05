@php
    $isMaster = auth()->user()->is_master;

    if ($isMaster) {
        $listMenu = [
            [
                'title' => 'Dashboard',
                'url' => '/master/dashboard',
                'icon' => 'home',
            ],
            [
                'title' => 'Owner',
                'url' => '/master/owner',
                'icon' => 'person',
            ],
            [
                'title' => 'Business',
                'url' => '/master/business',
                'icon' => 'business',
            ],
        ];
    } else {
        $listMenu = [
            [
                'title' => 'Dashboard',
                'url' => '/dashboard',
                'icon' => 'home',
            ],
            [
                'title' => 'Master Data',
                'url' => '/master-data',
                'icon' => 'database',
                'child' => [
                    [
                        'title' => 'Role',
                        'url' => '/master-data/role',
                    ],
                    [
                        'title' => 'User',
                        'url' => '/master-data/user',
                    ],
                    [
                        'title' => 'Member',
                        'url' => '/master-data/member',
                    ],
                    [
                        'title' => 'Pelanggan',
                        'url' => '/master-data/pelanggan',
                    ],
                    [
                        'title' => 'Supplier',
                        'url' => '/master-data/supplier',
                    ],
                ],
            ],
            [
                'title' => 'Master Produk',
                'url' => '/master-produk',
                'icon' => 'box',
                'child' => [
                    [
                        'title' => 'Satuan',
                        'url' => '/master-produk/satuan',
                    ],
                    [
                        'title' => 'Kategori',
                        'url' => '/master-produk/kategori',
                    ],
                    [
                        'title' => 'Merek',
                        'url' => '/master-produk/merek',
                    ],
                    [
                        'title' => 'Rak',
                        'url' => '/master-produk/rak',
                    ],
                    [
                        'title' => 'Produk',
                        'url' => '/master-produk/produk',
                    ],
                ],
            ],
            [
                'title' => 'Pembelian',
                'url' => '/pembelian',
                'icon' => 'add_shopping_cart',
                'child' => [
                    [
                        'title' => 'Tambah Pembelian',
                        'url' => '/pembelian/tambah',
                    ],
                    [
                        'title' => 'Daftar Pembelian',
                        'url' => '/pembelian/daftar',
                    ],
                    [
                        'title' => 'Daftar Retur',
                        'url' => '/pembelian/daftar-retur',
                    ],
                ],
            ],
            [
                'title' => 'Penjualan',
                'url' => '/penjualan',
                'icon' => 'point_of_sale',
                'child' => [
                    [
                        'title' => 'Tambah Penjualan',
                        'url' => '/penjualan/tambah',
                    ],
                    [
                        'title' => 'Daftar Penjualan',
                        'url' => '/penjualan/daftar',
                    ],
                    [
                        'title' => 'Daftar Return',
                        'url' => '/penjualan/daftar-retur',
                    ],
                    [
                        'title' => 'POS',
                        'url' => '/penjualan/pos',
                    ],
                ],
            ],
            [
                'title' => 'Inventory',
                'url' => '/stock',
                'icon' => 'inventory',
                'child' => [
                    [
                        'title' => 'Stok Opname',
                        'url' => '/stock/opname',
                        'child' => [
                            [
                                'title' => 'Tambah Opname',
                                'url' => '/stock/opname/tambah',
                            ],
                            [
                                'title' => 'Daftar Opname',
                                'url' => '/stock/opname/daftar',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Stok Adjustment',
                        'url' => '/stock/adjustment',
                        'child' => [
                            [
                                'title' => 'Tambah Adjustment',
                                'url' => '/stock/adjustment/tambah',
                            ],
                            [
                                'title' => 'Daftar Adjustment',
                                'url' => '/stock/adjustment/daftar',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Keuangan',
                'url' => '/keuangan',
                'icon' => 'analytics',
                'child' => [
                    [
                        'title' => 'Jurnal Umum',
                        'url' => '/keuangan/jurnal-umum',
                    ],
                    [
                        'title' => 'Pelaporan',
                        'url' => '/keuangan/pelaporan',
                    ],
                ],
            ],
            [
                'title' => 'Pengaturan',
                'url' => '/master-pengaturan',
                'icon' => 'settings',
            ],
        ];
    } // end else (business user menu)

    $path = '/' . request()->path();
@endphp

<div class="navbar-expand-md">
    <div class="collapse navbar-collapse" id="navbar-menu">
        <div class="navbar">
            <div class="container-xl">
                <div class="row flex-column flex-md-row flex-fill align-items-center">
                    <div class="col">
                        <!-- BEGIN NAVBAR MENU -->
                        <nav aria-label="Primary">
                            <!-- BEGIN NAVBAR MENU -->
                            <ul class="navbar-nav">
                                @foreach ($listMenu as $menu)
                                    @if (isset($menu['child']))
                                        @php
                                            $active = false;
                                            if (str_contains($path, $menu['url'])) {
                                                $active = true;
                                            }
                                        @endphp

                                        <li class="nav-item dropdown {{ $active ? 'active' : '' }}">
                                            <a class="nav-link dropdown-toggle" href="#navbar-form"
                                                data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button"
                                                aria-haspopup="true" aria-expanded="false">
                                                <span class="nav-icon d-md-none d-lg-inline-block">
                                                    <span class="material-symbols-outlined">
                                                        {{ $menu['icon'] }}
                                                    </span>
                                                </span>
                                                <span class="nav-link-title"> {{ $menu['title'] }} </span>
                                            </a>
                                            <div class="dropdown-menu">
                                                @foreach ($menu['child'] as $child)
                                                    @if (isset($child['child']))
                                                        @php
                                                            $subMenuActive = false;
                                                            if (str_contains($path, $child['url'])) {
                                                                $subMenuActive = true;
                                                            }
                                                        @endphp
                                                        {{-- Nested Dropdown --}}
                                                        <div class="dropdown-submenu">
                                                            <a class="dropdown-item {{ $subMenuActive ? 'active' : '' }} dropdown-toggle"
                                                                href="#">
                                                                {{ $child['title'] }}
                                                            </a>
                                                            <div class="dropdown-menu">
                                                                @foreach ($child['child'] as $subChild)
                                                                    <a class="dropdown-item {{ $path == $subChild['url'] ? 'active' : '' }}"
                                                                        href="{{ $subChild['url'] }}">
                                                                        {{ $subChild['title'] }}
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @else
                                                        {{-- Regular Dropdown Item --}}
                                                        <a class="dropdown-item {{ $path == $child['url'] ? 'active' : '' }}"
                                                            href="{{ $child['url'] }}">
                                                            {{ $child['title'] }}
                                                        </a>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </li>
                                    @else
                                        <li class="nav-item {{ $path == $menu['url'] ? 'active' : '' }}">
                                            <a class="nav-link" href="{{ $menu['url'] }}">
                                                <span class="nav-icon d-md-none d-lg-inline-block">
                                                    <span class="material-symbols-outlined">
                                                        {{ $menu['icon'] }}
                                                    </span>
                                                </span>
                                                <span class="nav-link-title"> {{ $menu['title'] }} </span>
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                            <!-- END NAVBAR MENU -->
                        </nav>
                        <!-- END NAVBAR MENU -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
