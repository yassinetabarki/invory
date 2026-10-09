<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Reference\ArchiveProductCategory;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Archives a product category (ADR-0008). A separate invokable controller: the resource controller keeps to the standard methods.
 */
final class ArchiveProductCategoryController extends Controller
{
    public function __invoke(ProductCategory $productCategory, ArchiveProductCategory $action): RedirectResponse
    {
        Gate::authorize('update', $productCategory);

        $action->handle($productCategory);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category archived.')]);

        return back();
    }
}
