<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Master App API (kontrak cross-app-bus)
|--------------------------------------------------------------------------
| Endpoint ini diakses di central domain (bumdesmart.test / astamart.net),
| bukan di tenant domain. Tenant resolution TIDAK dilakukan untuk request
| dari Master (signature sendiri sudah jadi auth).
*/
Route::get('/master/auth/sso', [\App\Http\Controllers\Api\Master\SsoController::class, 'consume'])->middleware('guest');

Route::middleware('verify.master.signature')->prefix('master/tenants')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\Master\TenantController::class, 'index']);
    Route::get('/{tenantRefId}', [\App\Http\Controllers\Api\Master\TenantController::class, 'show']);
    Route::post('/', [\App\Http\Controllers\Api\Master\TenantController::class, 'register']);
    Route::put('/{tenantRefId}', [\App\Http\Controllers\Api\Master\TenantController::class, 'update']);
    Route::patch('/{tenantRefId}/status', [\App\Http\Controllers\Api\Master\TenantController::class, 'toggleStatus']);
});
