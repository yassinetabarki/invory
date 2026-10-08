<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ProductCategory;
use App\Enums\ProductStatus;
use App\Enums\SalesPeriod;
use App\Http\Requests\Products\IndexProductRequest;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Mock\PreviewFixture;
use App\Mock\PreviewMaterials;
use App\Mock\PreviewPage;
use App\Mock\PreviewProducts;
use App\Mock\PreviewSales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * MOCK data layer (ADR-0006, removed by S-17): products come from App\Mock, costed by the production domain code.
 * Writes are validated by their final FormRequests and persist nothing. {product} is a plain integer id until S-04 adds
 * model binding; an unknown id is a 404.
 */
final class ProductController extends Controller
{
    public function index(IndexProductRequest $request): Response
    {
        Gate::authorize('access-preview');

        $filters = $request->filters();
        $catalog = PreviewProducts::load();
        $products = PreviewProducts::filter(
            PreviewProducts::byName($catalog->all()),
            $filters['search'],
            $filters['category'],
            $filters['status'],
            $filters['cant_produce'],
        );

        return Inertia::render('products/index', [
            'products' => PreviewPage::paginate($catalog->listItems($products), $request, route('products.index')),
            'filters' => $filters,
            'categories' => ProductCategory::options(),
            'statuses' => ProductStatus::options(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('access-preview');

        return Inertia::render('products/create', [
            'categories' => ProductCategory::options(),
            'statuses' => ProductStatus::options(),
            'materials' => PreviewMaterials::options(PreviewMaterials::byName()),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'info', 'message' => __("Preview: the product passed validation but wasn't saved.")]);

        return to_route('products.index');
    }

    public function show(int $product): Response
    {
        Gate::authorize('access-preview');
        $this->ensureExists($product);

        $catalog = PreviewProducts::load();
        $sales = PreviewSales::ofProduct(PreviewSales::all(), $product);

        return Inertia::render('products/show', [
            'product' => $catalog->detail($catalog->find($product) ?? abort(404)),
            'sales_last_30_days' => Arr::only(
                PreviewSales::totals(PreviewSales::inPeriod($sales, SalesPeriod::LastThirtyDays)),
                ['units_sold', 'revenue'],
            ),
            'recent_sales' => PreviewSales::recent($sales),
        ]);
    }

    public function edit(int $product): Response
    {
        Gate::authorize('access-preview');
        $this->ensureExists($product);

        // One query: the picker lists every material and the product is costed against the same rows.
        $materials = PreviewMaterials::byName();
        $catalog = PreviewProducts::from($materials);

        return Inertia::render('products/edit', [
            'categories' => ProductCategory::options(),
            'statuses' => ProductStatus::options(),
            'materials' => PreviewMaterials::options($materials),
            'product' => $catalog->detail($catalog->find($product) ?? abort(404)),
        ]);
    }

    public function update(UpdateProductRequest $request, int $product): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'info', 'message' => __("Preview: the product passed validation but wasn't saved.")]);

        return to_route('products.show', $product);
    }

    public function destroy(int $product): RedirectResponse
    {
        Gate::authorize('access-preview');
        $this->ensureExists($product);

        Inertia::flash('toast', ['type' => 'info', 'message' => __('Preview: nothing was deleted.')]);

        return to_route('products.index');
    }

    /** MOCK: an unknown product id is a 404, as route model binding will make it. */
    private function ensureExists(int $product): void
    {
        abort_if(PreviewFixture::product($product) === null, 404);
    }
}
