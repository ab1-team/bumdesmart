<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \App\Models\Menu::truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $menus = [
            [
                'title' => 'Dashboard',
                'url' => '/dashboard',
                'icon' => 'home',
                'order' => 1,
            ],
            [
                'title' => 'Master Data',
                'url' => '/master-data',
                'icon' => 'database',
                'order' => 2,
                'child' => [
                    ['title' => 'Role', 'url' => '/master-data/role', 'order' => 1],
                    ['title' => 'User', 'url' => '/master-data/user', 'order' => 2],
                    ['title' => 'Member', 'url' => '/master-data/member', 'order' => 3],
                    ['title' => 'Pelanggan', 'url' => '/master-data/pelanggan', 'order' => 4],
                    ['title' => 'Supplier', 'url' => '/master-data/supplier', 'order' => 5],
                ],
            ],
            [
                'title' => 'Master Produk',
                'url' => '/master-produk',
                'icon' => 'box',
                'order' => 3,
                'child' => [
                    ['title' => 'Satuan', 'url' => '/master-produk/satuan', 'order' => 1],
                    ['title' => 'Kategori', 'url' => '/master-produk/kategori', 'order' => 2],
                    ['title' => 'Merek', 'url' => '/master-produk/merek', 'order' => 3],
                    ['title' => 'Rak', 'url' => '/master-produk/rak', 'order' => 4],
                    ['title' => 'Produk', 'url' => '/master-produk/produk', 'order' => 5],
                ],
            ],
            [
                'title' => 'Pembelian',
                'url' => '/pembelian',
                'icon' => 'add_shopping_cart',
                'order' => 4,
                'child' => [
                    ['title' => 'Tambah Pembelian', 'url' => '/pembelian/tambah', 'order' => 1],
                    ['title' => 'Daftar Pembelian', 'url' => '/pembelian/daftar', 'order' => 2],
                    ['title' => 'Daftar Retur', 'url' => '/pembelian/daftar-retur', 'order' => 3],
                ],
            ],
            [
                'title' => 'Penjualan',
                'url' => '/penjualan',
                'icon' => 'point_of_sale',
                'order' => 5,
                'child' => [
                    ['title' => 'Tambah Penjualan', 'url' => '/penjualan/tambah', 'order' => 1],
                    ['title' => 'Daftar Penjualan', 'url' => '/penjualan/daftar', 'order' => 2],
                    ['title' => 'Daftar Return', 'url' => '/penjualan/daftar-retur', 'order' => 3],
                    ['title' => 'POS', 'url' => '/penjualan/pos', 'order' => 4],
                ],
            ],
            [
                'title' => 'Inventory',
                'url' => '/stock',
                'icon' => 'inventory',
                'order' => 6,
                'child' => [
                    [
                        'title' => 'Stok Opname',
                        'url' => '/stock/opname',
                        'order' => 1,
                        'child' => [
                            ['title' => 'Tambah Opname', 'url' => '/stock/opname/tambah', 'order' => 1],
                            ['title' => 'Daftar Opname', 'url' => '/stock/opname/daftar', 'order' => 2],
                        ],
                    ],
                    [
                        'title' => 'Stok Adjustment',
                        'url' => '/stock/adjustment',
                        'order' => 2,
                        'child' => [
                            ['title' => 'Tambah Adjustment', 'url' => '/stock/adjustment/tambah', 'order' => 1],
                            ['title' => 'Daftar Adjustment', 'url' => '/stock/adjustment/daftar', 'order' => 2],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Keuangan',
                'url' => '/keuangan',
                'icon' => 'analytics',
                'order' => 7,
                'child' => [
                    ['title' => 'Jurnal Umum', 'url' => '/keuangan/jurnal-umum', 'order' => 1],
                    ['title' => 'Pelaporan', 'url' => '/keuangan/pelaporan', 'order' => 2],
                ],
            ],
            [
                'title' => 'Pengaturan',
                'url' => '/master-pengaturan',
                'icon' => 'settings',
                'order' => 8,
            ],
        ];

        foreach ($menus as $menu) {
            $parent = \App\Models\Menu::create([
                'title' => $menu['title'],
                'url' => $menu['url'],
                'icon' => $menu['icon'],
                'order' => $menu['order'],
            ]);

            if (isset($menu['child'])) {
                foreach ($menu['child'] as $child) {
                    $childMenu = \App\Models\Menu::create([
                        'parent_id' => $parent->id,
                        'title' => $child['title'],
                        'url' => $child['url'],
                        'order' => $child['order'],
                    ]);

                    if (isset($child['child'])) {
                        foreach ($child['child'] as $subChild) {
                            \App\Models\Menu::create([
                                'parent_id' => $childMenu->id,
                                'title' => $subChild['title'],
                                'url' => $subChild['url'],
                                'order' => $subChild['order'],
                            ]);
                        }
                    }
                }
            }
        }
    }
}
