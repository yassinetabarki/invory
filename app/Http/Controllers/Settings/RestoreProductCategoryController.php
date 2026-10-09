<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Reference\RestoreProductCategory;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Restores a product category (ADR-0008). A separate invokable controller: the resource controller keeps to the standard methods.
 */
final class RestoreProductCategoryController extends Controller
{
    public function __invoke(ProductCategory $productCategory, RestoreProductCategory $action): RedirectResponse
    {
        Gate::authorize('update', $productCategory);

        $action->handle($productCategory);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category restored.')]);

        return back();
    }
}
