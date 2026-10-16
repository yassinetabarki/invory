<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MaterialOptionController;
use App\Http\Controllers\MaterialRestockController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderLineMoveController;
use App\Http\Controllers\OrderLineMovePreviewController;
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

    // {order} is route-model bound (an unknown, soft-deleted or other workshop's order is a 404); the digit constraint keeps a
    // malformed id a 404 too.
    Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
        ->where(['order' => '[0-9]{1,18}']);

    // Moving units of an order line between stages (S-49). `scopeBindings()`: a line that isn't the order's own is a 404.
    Route::post('orders/{order}/lines/{line}/moves', [OrderLineMoveController::class, 'store'])
        ->where(['order' => '[0-9]{1,18}', 'line' => '[0-9]{1,18}'])
        ->scopeBindings()
        ->name('orders.lines.moves.store');

    // The move window's materials table: JSON, read-only, no locks (S-53, ADR-0009 rule 5).
    Route::get('orders/{order}/lines/{line}/moves/preview', [OrderLineMovePreviewController::class, 'show'])
        ->where(['order' => '[0-9]{1,18}', 'line' => '[0-9]{1,18}'])
        ->scopeBindings()
        ->name('orders.lines.moves.preview');

    // {sale} is route-model bound; the digit constraint keeps a malformed id a 404 (a bigint query error on PostgreSQL otherwise).
    Route::resource('sales', SaleController::class)->only(['index', 'store', 'update', 'destroy'])
        ->where(['sale' => '[0-9]{1,18}']);
});

require __DIR__.'/settings.php';
