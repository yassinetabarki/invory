<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MaterialRestockController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // 1 to 18 digits: anything else is a 404 instead of a type error (int $id overflow) or, on PostgreSQL, a bigint query error.
    Route::resource('materials', MaterialController::class)->where(['material' => '[0-9]{1,18}']);

    Route::post('materials/{material}/restocks', [MaterialRestockController::class, 'store'])
        ->where('material', '[0-9]{1,18}')
        ->name('materials.restocks.store');

    // MOCK (ADR-0006): until the products and sales tables exist, {product} and {sale} are plain integer ids.
    // Backend stories switch these to model binding.
    Route::resource('products', ProductController::class)->where(['product' => '[0-9]{1,18}']);
    Route::resource('sales', SaleController::class)->only(['index', 'store', 'update', 'destroy'])
        ->where(['sale' => '[0-9]{1,18}']);
});

require __DIR__.'/settings.php';
