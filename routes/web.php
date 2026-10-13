<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MaterialOptionController;
use App\Http\Controllers\MaterialRestockController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // The BOM picker's typeahead (S-25). Registered before the resource, which would otherwise read `options` as a {material}.
    Route::get('materials/options', [MaterialOptionController::class, 'index'])->name('materials.options');

    // 1 to 18 digits: anything else is a 404 instead of a type error (int $id overflow) or, on PostgreSQL, a bigint query error.
    Route::resource('materials', MaterialController::class)->where(['material' => '[0-9]{1,18}']);

    Route::post('materials/{material}/restocks', [MaterialRestockController::class, 'store'])
        ->where('material', '[0-9]{1,18}')
        ->name('materials.restocks.store');

    // {product} is route-model bound (an unknown or soft-deleted product is a 404). The digit constraint keeps a malformed id a 404
    // too, instead of a bigint query error on PostgreSQL.
    Route::resource('products', ProductController::class)->where(['product' => '[0-9]{1,18}']);

    // {sale} is route-model bound; the digit constraint keeps a malformed id a 404 (a bigint query error on PostgreSQL otherwise).
    Route::resource('sales', SaleController::class)->only(['index', 'store', 'update', 'destroy'])
        ->where(['sale' => '[0-9]{1,18}']);
});

require __DIR__.'/settings.php';
