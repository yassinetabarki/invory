<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Reference\CreateProductCategory;
use App\Actions\Reference\DeleteProductCategory;
use App\Actions\Reference\UpdateProductCategory;
use App\Domain\Sizes\SizeLine;
use App\Exceptions\ReferenceInUseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\StoreProductCategoryRequest;
use App\Http\Requests\Reference\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use App\Models\Size;
use App\Services\Products\ProductSizes;
use App\Services\Reference\ReferenceData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Settings > Product categories (ADR-0008). Any verified user manages them (decision Q3); every route goes through
 * ProductCategoryPolicy.
 *
 * `usage_count` counts the non-deleted products of the category. Deleting is refused while any product still points at it,
 * soft-deleted ones included (the foreign key would refuse anyway, and restoring such a product must find its category).
 */
final class ProductCategoryController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', ProductCategory::class);

        return Inertia::render('settings/product-categories', [
            'categories' => fn (): array => $this->rows(),
            'sizes' => fn (): array => array_values(app(ProductSizes::class)->workshop()->map(fn (Size $size): array => [
                'id' => $size->id,
                'label' => $size->label,
                'one_size' => (bool) $size->is_one_size,
            ])->all()),
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
            $deleteCategory->handle($productCategory);
        } catch (ReferenceInUseException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category deleted.')]);

        return back();
    }

    /**
     * The rows of the table (INV-18-i): the reference row, its usage, and what it suggests in this workshop: the size ids in list order
     * (no row = the workshop's One size, `ProductSizes::suggestedIds()`) and the Suggests line the server writes (`SizeLine`).
     *
     * @return array<int, array<string, mixed>>
     */
    private function rows(): array
    {
        $sizes = app(ProductSizes::class);
        $suggested = $sizes->suggestedIds();
        $labels = $sizes->workshop()->pluck('label', 'id');
        $oneSizeIds = $sizes->workshop()->filter(fn (Size $size): bool => (bool) $size->is_one_size)->pluck('id')->all();
        $workshopLabels = fn (): array => array_values($labels->map(fn (mixed $label): string => (string) $label)->all());

        return ProductCategory::query()
            ->withCount('products')
            ->ordered()
            ->get()
            ->map(function (ProductCategory $category) use ($suggested, $labels, $oneSizeIds, $workshopLabels): array {
                $ids = $suggested[$category->code] ?? [];

                return [
                    ...ReferenceData::category($category),
                    'usage_count' => $category->products_count,
                    'suggested_size_ids' => $ids,
                    'suggests' => SizeLine::suggests(
                        array_map(fn (int $id): string => (string) $labels[$id], $ids),
                        $workshopLabels,
                        count($ids) === 1 && in_array($ids[0], $oneSizeIds, true),
                    ),
                ];
            })
            ->all();
    }
}
