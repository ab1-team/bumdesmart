<?php

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
use App\Livewire\Master\MasterOwner;
use App\Livewire\MasterData\MasterBusiness;
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

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        Route::get('/', [AuthController::class, 'login']);
        Route::post('/auth', [AuthController::class, 'auth']);
    });
}

use App\Livewire\Master\MasterDashboard;

// === MASTER ROUTES ===
Route::group([
    'middleware' => ['auth:central', 'is_master'],
    'prefix' => 'master',
], function () {
    Route::get('/dashboard', MasterDashboard::class);
    Route::get('/owner', MasterOwner::class);
    Route::get('/business', MasterBusiness::class);
});

// Fix 404: Redirect central /dashboard to /master/dashboard
Route::get('/dashboard', function() {
    return redirect('/master/dashboard');
})->middleware(['auth:central', 'is_master']);

Route::group(['middleware' => 'auth'], function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});
