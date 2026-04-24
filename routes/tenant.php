<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Livewire\DaftarPembelian;
use App\Livewire\DaftarPenjualan;
use App\Livewire\DaftarReturPembelian;
use App\Livewire\DaftarReturPenjualan;
use App\Livewire\Dashboard;
use App\Livewire\Kategori;
use App\Livewire\Keuangan\JurnalUmum;
use App\Livewire\Keuangan\Laporan\Cetak;
use App\Livewire\Keuangan\Pelaporan;
use App\Livewire\Member;
use App\Livewire\Merek;
use App\Livewire\Pelanggan;
use App\Livewire\Pengaturan;
use App\Livewire\Penjualan\CetakStruk;
use App\Livewire\Penjualan\CetakStrukKasir;
use App\Livewire\Produk;
use App\Livewire\Profile;
use App\Livewire\Rak;
use App\Livewire\Role;
use App\Livewire\SalePos;
use App\Livewire\Satuan;
use App\Livewire\StockAdjustment;
use App\Livewire\StockOpname;
use App\Livewire\Supplier;
use App\Livewire\TambahPembelian;
use App\Livewire\TambahPenjualan;
use App\Livewire\TambahReturPembelian;
use App\Livewire\TambahReturPenjualan;
use App\Livewire\TambahStockAdjustment;
use App\Livewire\TambahStockOpname;
use App\Livewire\User;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    'web',
])->group(function () {
    Route::get('/', [AuthController::class, 'login']);
    Route::post('/auth', [AuthController::class, 'auth']);

    Route::group([
        'middleware' => ['auth', 'is_not_master'],
    ], function () {
            Route::get('/dashboard', Dashboard::class);
            Route::get('/profile', Profile::class);

            Route::get('/master-data/role', Role::class);
            Route::get('/master-data/user', User::class);
            Route::get('/master-data/member', Member::class);
            Route::get('/master-data/pelanggan', Pelanggan::class);
            Route::get('/master-data/supplier', Supplier::class);

            Route::get('/master-produk/satuan', Satuan::class);
            Route::get('/master-produk/kategori', Kategori::class);
            Route::get('/master-produk/merek', Merek::class);
            Route::get('/master-produk/rak', Rak::class);
            Route::get('/master-produk/produk', Produk::class);
            Route::get('/master-produk/produk/cetak-label', \App\Livewire\Produk\CetakLabel::class)->name('produk.cetak-label');

            Route::get('/pembelian/tambah', TambahPembelian::class);
            Route::get('/pembelian/daftar', DaftarPembelian::class);
            Route::get('/pembelian/edit/{id}', TambahPembelian::class);

            Route::get('/pembelian/daftar-retur', DaftarReturPembelian::class);
            Route::get('/pembelian/retur/{id}', TambahReturPembelian::class);

            Route::get('/stock', function () {
                return redirect('/stock/opname');
            });

            Route::get('/stock/opname', StockOpname::class);
            Route::get('/stock/opname/tambah', TambahStockOpname::class);
            Route::get('/stock/opname/daftar', StockOpname::class);
            Route::get('/stock/opname/edit/{id}', TambahStockOpname::class);

            Route::get('/stock/adjustment', StockAdjustment::class);
            Route::get('/stock/adjustment/tambah', TambahStockAdjustment::class);
            Route::get('/stock/adjustment/daftar', StockAdjustment::class);

            Route::get('/penjualan/tambah', TambahPenjualan::class);
            Route::get('/penjualan/daftar', DaftarPenjualan::class);
            Route::get('/penjualan/edit/{id}', TambahPenjualan::class);

            Route::get('/penjualan/retur/{id}', TambahReturPenjualan::class);
            Route::get('/penjualan/daftar-retur', DaftarReturPenjualan::class);
            Route::get('/penjualan/pos', SalePos::class);
            Route::get('/penjualan/cetak-struk/{id}', CetakStruk::class);
            Route::get('/penjualan/cetak-struk-kasir/{id}', CetakStrukKasir::class);

            Route::get('/keuangan/pelaporan', Pelaporan::class);
            Route::get('/keuangan/pelaporan/cetak', Cetak::class);

            Route::get('/keuangan/jurnal-umum', JurnalUmum::class);
            Route::get('/master-pengaturan', Pengaturan::class);
        });

        Route::group(['middleware' => 'auth'], function () {
            Route::post('/logout', [AuthController::class, 'logout']);
    });
});
