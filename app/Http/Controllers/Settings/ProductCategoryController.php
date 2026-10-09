<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Reference\CreateProductCategory;
use App\Actions\Reference\DeleteProductCategory;
use App\Actions\Reference\UpdateProductCategory;
use App\Exceptions\ReferenceInUseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\StoreProductCategoryRequest;
use App\Http\Requests\Reference\UpdateProductCategoryRequest;
use App\Mock\PreviewFixture;
use App\Models\ProductCategory;
use App\Services\Reference\ReferenceData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Settings > Product categories (ADR-0008). Any verified user manages them (decision Q3); every route goes through
 * ProductCategoryPolicy.
 *
 * MOCK (ADR-0006, S-04 swaps to `withCount('products')`): products have no table yet, so the usage of a category is the
 * number of preview products that have its code.
 */
final class ProductCategoryController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', ProductCategory::class);

        return Inertia::render('settings/product-categories', [
            'categories' => function (): array {
                $usage = PreviewFixture::productCountsByCategory();

                return ProductCategory::query()
                    ->ordered()
                    ->get()
                    ->map(fn (ProductCategory $category): array => [...ReferenceData::category($category), 'usage_count' => $usage[$category->code] ?? 0])
                    ->all();
            },
        ]);
    }

    public function store(StoreProductCategoryRequest $request, CreateProductCategory $createCategory): RedirectResponse
    {
        $createCategory->handle($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        return back();
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory, UpdateProductCategory $updateCategory): RedirectResponse
    {
        $updateCategory->handle($productCategory, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return back();
    }

    public function destroy(ProductCategory $productCategory, DeleteProductCategory $deleteCategory): RedirectResponse
    {
        Gate::authorize('delete', $productCategory);

        try {
            $deleteCategory->handle($productCategory, PreviewFixture::productCountsByCategory()[$productCategory->code] ?? 0);
        } catch (ReferenceInUseException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category deleted.')]);

        return back();
    }
}
