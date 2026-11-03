<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientLookupController;
use App\Http\Controllers\ClientOptionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MaterialOptionController;
use App\Http\Controllers\MaterialRestockController;
use App\Http\Controllers\OrderCancellationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDeliveryController;
use App\Http\Controllers\OrderProductSearchController;
use App\Http\Controllers\ProductBarcodeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionBatchController;
use App\Http\Controllers\ProductionBatchMoveController;
use App\Http\Controllers\ProductionBatchStockController;
use App\Http\Controllers\ProductionBatchUndoController;
use App\Http\Controllers\ProductionProductController;
use App\Http\Controllers\ProductionProductMaterialController;
use App\Http\Controllers\ProductionTicketController;
use App\Http\Controllers\ProductStockController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\RestockBarcodeController;
use App\Http\Controllers\RestockController;
use App\Http\Controllers\RestockProductController;
use App\Http\Controllers\RestockUndoController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ScanBatchController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\ScanLookupController;
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

    // "Update stock" (S-56): the finished units of a product, set size by size.
    Route::put('products/{product}/stock', [ProductStockController::class, 'update'])
        ->where('product', '[0-9]{1,18}')
        ->name('products.stock.update');

    // "Generate" on the Barcodes tab (INV-8-c): in-store numbers for the variants asked for, all or nothing.
    Route::post('products/{product}/barcodes', [ProductBarcodeController::class, 'store'])
        ->where('product', '[0-9]{1,18}')
        ->name('products.barcodes.store');

    // New restock's product picker and a product's line (INV-3-d): JSON, read-only. Registered before the resource.
    Route::get('restocks/products/options', [RestockProductController::class, 'index'])->name('restocks.products.options');
    Route::get('restocks/products/{product}', [RestockProductController::class, 'show'])
        ->where('product', '[0-9]{1,18}')
        ->name('restocks.products.line');

    // New restock's scan field (INV-3-e): what a barcode stands for. JSON, read-only.
    Route::get('restocks/barcode', [RestockBarcodeController::class, 'show'])->name('restocks.barcode');

    // Restocks of finished products (INV-3): never edited or deleted.
    Route::resource('restocks', RestockController::class)->only(['index', 'create', 'store']);

    // Undo restock (INV-3-c). {restock} is route-model bound (an unknown or other workshop's restock is a 404).
    Route::post('restocks/{restock}/undo', [RestockUndoController::class, 'store'])
        ->where('restock', '[0-9]{1,18}')
        ->name('restocks.undo');

    // Progress, production for the shelf (INV-5, ADR-0023). The Start batch window's grid and materials table are JSON, read-only.
    Route::get('progress', [ProgressController::class, 'index'])->name('progress.index');
    Route::get('progress/products/{product}', [ProductionProductController::class, 'show'])
        ->where('product', '[0-9]{1,18}')
        ->name('progress.products.line');
    Route::get('progress/products/{product}/materials', [ProductionProductMaterialController::class, 'show'])
        ->where('product', '[0-9]{1,18}')
        ->name('progress.products.materials');
    Route::post('progress/batches', [ProductionBatchController::class, 'store'])->name('progress.batches.store');
    // The work tickets of some batches (INV-15-b, ADR-0024): a page with no app frame that prints itself, opened in a new tab.
    Route::get('progress/tickets', [ProductionTicketController::class, 'index'])->name('progress.tickets');
    Route::post('progress/batches/{batch}/moves', [ProductionBatchMoveController::class, 'store'])
        ->where('batch', '[0-9]{1,18}')
        ->name('progress.batches.moves.store');
    Route::post('progress/batches/{batch}/stock', [ProductionBatchStockController::class, 'store'])
        ->where('batch', '[0-9]{1,18}')
        ->name('progress.batches.stock');
    Route::get('progress/batches/{batch}/undo', [ProductionBatchUndoController::class, 'show'])
        ->where('batch', '[0-9]{1,18}')
        ->name('progress.batches.undo-preview');
    Route::post('progress/batches/{batch}/undo', [ProductionBatchUndoController::class, 'store'])
        ->where('batch', '[0-9]{1,18}')
        ->name('progress.batches.undo');

    // Scan (INV-15-c, ADR-0024): the page, a ticket's QR link (guests sign in and come back), the field's lookup (JSON, read-only) and
    // the confirm's button.
    Route::get('scan', [ScanController::class, 'index'])->name('scan.index');
    Route::get('scan/t/{code}', [ScanController::class, 'show'])->where('code', '[A-Za-z0-9]{32}')->name('scan.ticket');
    Route::get('scan/lookup', [ScanLookupController::class, 'show'])->name('scan.lookup');
    Route::post('scan/batches/{batch}', [ScanBatchController::class, 'store'])
        ->where('batch', '[0-9]{1,18}')
        ->name('scan.batches.store');

    // New order's Client card (INV-4-b): the client with this phone, or with this name. JSON, read-only (ADR-0009 rule 5).
    Route::get('clients/lookup', [ClientLookupController::class, 'show'])->name('clients.lookup');

    // The client picker's typeahead (INV-4-c): JSON, read-only.
    Route::get('clients/options', [ClientOptionController::class, 'index'])->name('clients.options');

    // Clients (INV-4-c): never edited or removed. {client} is route-model bound; the digit constraint keeps a malformed id a 404.
    Route::resource('clients', ClientController::class)->only(['index', 'store', 'show'])
        ->where(['client' => '[0-9]{1,18}']);

    // {order} is route-model bound (an unknown, soft-deleted or other workshop's order is a 404); the digit constraint keeps a
    // malformed id a 404 too.
    // "Search products" on New order and Edit order (INV-13-c): JSON, read-only. Registered before the resource.
    Route::get('orders/products/search', [OrderProductSearchController::class, 'index'])->name('orders.products.search');

    Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
        ->where(['order' => '[0-9]{1,18}']);

    // Mark delivered, its move back and Cancel units on an order line (INV-14-d). `scopeBindings()`: a line that isn't the order's own is a 404.
    Route::post('orders/{order}/lines/{line}/deliveries', [OrderDeliveryController::class, 'store'])
        ->where(['order' => '[0-9]{1,18}', 'line' => '[0-9]{1,18}'])
        ->scopeBindings()
        ->name('orders.lines.deliveries.store');
    Route::post('orders/{order}/lines/{line}/deliveries/back', [OrderDeliveryController::class, 'destroy'])
        ->where(['order' => '[0-9]{1,18}', 'line' => '[0-9]{1,18}'])
        ->scopeBindings()
        ->name('orders.lines.deliveries.destroy');
    Route::post('orders/{order}/lines/{line}/cancellations', [OrderCancellationController::class, 'store'])
        ->where(['order' => '[0-9]{1,18}', 'line' => '[0-9]{1,18}'])
        ->scopeBindings()
        ->name('orders.lines.cancellations.store');

    // {sale} is route-model bound; the digit constraint keeps a malformed id a 404 (a bigint query error on PostgreSQL otherwise).
    Route::resource('sales', SaleController::class)->only(['index', 'store', 'update', 'destroy'])
        ->where(['sale' => '[0-9]{1,18}']);
});

require __DIR__.'/settings.php';
