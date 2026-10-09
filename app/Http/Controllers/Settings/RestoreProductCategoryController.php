<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Reference\RestoreProductCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\RestoreProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Restores a product category (ADR-0008). Refused with an error on `name` while an active
 * row has the same name (the request). A separate invokable controller: the resource controller keeps to the standard methods.
 */
final class RestoreProductCategoryController extends Controller
{
    public function __invoke(RestoreProductCategoryRequest $request, ProductCategory $productCategory, RestoreProductCategory $action): RedirectResponse
    {
        $action->handle($productCategory);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category restored.')]);

        return back();
    }
}
