<?php

use App\Http\Controllers\Settings\ArchiveMaterialCategoryController;
use App\Http\Controllers\Settings\ArchiveProductCategoryController;
use App\Http\Controllers\Settings\ArchiveUnitController;
use App\Http\Controllers\Settings\ColourController;
use App\Http\Controllers\Settings\LabelSettingsController;
use App\Http\Controllers\Settings\MaterialCategoryController;
use App\Http\Controllers\Settings\ProductCategoryController;
use App\Http\Controllers\Settings\ProductionPhaseController;
use App\Http\Controllers\Settings\ProductionPhaseOrderController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\RestoreMaterialCategoryController;
use App\Http\Controllers\Settings\RestoreProductCategoryController;
use App\Http\Controllers\Settings\RestoreUnitController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\SizeController;
use App\Http\Controllers\Settings\UnitController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // Not Route::redirect(): Laravel 13's RedirectController registers a QUERY verb that Wayfinder
    // emits but Inertia's `Method` type rejects, which breaks `npm run types:check`.
    Route::get('settings', fn () => to_route('profile.edit'));

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

    // Reference data (ADR-0008): units and the two category lists, each with the same six routes. Any verified user may
    // manage them (decision Q3; the policies check it). Ids are 1 to 18 digits, like every other route.
    foreach ([
        'units' => [UnitController::class, 'unit', ArchiveUnitController::class, RestoreUnitController::class],
        'material-categories' => [MaterialCategoryController::class, 'material_category', ArchiveMaterialCategoryController::class, RestoreMaterialCategoryController::class],
        'product-categories' => [ProductCategoryController::class, 'product_category', ArchiveProductCategoryController::class, RestoreProductCategoryController::class],
    ] as $uri => [$controller, $parameter, $archive, $restore]) {
        $name = "settings.{$uri}";

        Route::get("settings/{$uri}", [$controller, 'index'])->name("{$name}.index");
        Route::post("settings/{$uri}", [$controller, 'store'])->name("{$name}.store");
        Route::put("settings/{$uri}/{{$parameter}}", [$controller, 'update'])->where($parameter, '[0-9]{1,18}')->name("{$name}.update");
        Route::delete("settings/{$uri}/{{$parameter}}", [$controller, 'destroy'])->where($parameter, '[0-9]{1,18}')->name("{$name}.destroy");
        Route::patch("settings/{$uri}/{{$parameter}}/archive", $archive)->where($parameter, '[0-9]{1,18}')->name("{$name}.archive");
        Route::patch("settings/{$uri}/{{$parameter}}/restore", $restore)->where($parameter, '[0-9]{1,18}')->name("{$name}.restore");
    }

    // The workshop's colours (INV-1, ADR-0018): list, add, edit. A colour is never deleted, archived or restored, so there is no more.
    Route::get('settings/colours', [ColourController::class, 'index'])->name('settings.colours.index');
    Route::post('settings/colours', [ColourController::class, 'store'])->name('settings.colours.store');
    Route::put('settings/colours/{colour}', [ColourController::class, 'update'])->where('colour', '[0-9]{1,18}')->name('settings.colours.update');

    // The workshop's sizes (INV-18, ADR-0028): list, add, edit. A size is never deleted, archived or restored, so there is no more.
    Route::get('settings/sizes', [SizeController::class, 'index'])->name('settings.sizes.index');
    Route::post('settings/sizes', [SizeController::class, 'store'])->name('settings.sizes.store');
    Route::put('settings/sizes/{size}', [SizeController::class, 'update'])->where('size', '[0-9]{1,18}')->name('settings.sizes.update');

    // How the workshop's work tickets print (INV-15-g, ADR-0024); INV-9 adds its label settings to the same page.
    Route::get('settings/labels', [LabelSettingsController::class, 'edit'])->name('settings.labels.edit');
    Route::put('settings/labels', [LabelSettingsController::class, 'update'])->name('settings.labels.update');

    // The workshop's production phases (INV-5-b, ADR-0023): list, add, rename, remove, and Move up / Move down.
    Route::get('settings/production-phases', [ProductionPhaseController::class, 'index'])->name('settings.production-phases.index');
    Route::post('settings/production-phases', [ProductionPhaseController::class, 'store'])->name('settings.production-phases.store');
    Route::put('settings/production-phases/{phase}', [ProductionPhaseController::class, 'update'])->where('phase', '[0-9]{1,18}')->name('settings.production-phases.update');
    Route::delete('settings/production-phases/{phase}', [ProductionPhaseController::class, 'destroy'])->where('phase', '[0-9]{1,18}')->name('settings.production-phases.destroy');
    Route::patch('settings/production-phases/{phase}/position', [ProductionPhaseOrderController::class, 'update'])->where('phase', '[0-9]{1,18}')->name('settings.production-phases.position');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
