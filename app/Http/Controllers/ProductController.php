<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
use App\Models\Material;
use App\Services\Reference\ReferenceOptions;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
    public function index(IndexProductRequest $request, ReferenceOptions $options): Response
    {
        Gate::authorize('access-preview');

        $filters = $request->filters();

        // Lazy (a closure): costing every product is the expensive part, and a partial reload that does not list `products` skips it.
        return Inertia::render('products/index', [
            'products' => function () use ($request, $filters): AnonymousResourceCollection {
                $catalog = PreviewProducts::load();
                $products = PreviewProducts::filter(
                    PreviewProducts::byName($catalog->all()),
                    $filters['search'],
                    $filters['category'],
                    $filters['status'],
                    $filters['cant_produce'],
                );

                return PreviewPage::paginate($catalog->listItems($products), $request, route('products.index'));
            },
            'filters' => $filters,
            'categories' => fn (): array => $options->productCategories($filters['category']),
            'statuses' => ProductStatus::options(),
        ]);
    }

    public function create(ReferenceOptions $options): Response
    {
        Gate::authorize('access-preview');

        return Inertia::render('products/create', [
            'categories' => fn (): array => $options->productCategories(),
            'statuses' => ProductStatus::options(),
            'materials' => fn (): array => PreviewMaterials::options(PreviewMaterials::byName()),
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

        /** @var list<array{id: int, date: string, product_id: int, quantity: int, unit_price: string, note: string|null}>|null $loaded */
        $loaded = null;
        $sales = function () use (&$loaded, $product): array {
            return $loaded ??= PreviewSales::ofProduct(PreviewSales::all(), $product);
        };

        return Inertia::render('products/show', [
            'product' => function () use ($product): array {
                $catalog = PreviewProducts::load();

                return $catalog->detail($catalog->find($product) ?? abort(404));
            },
            'sales_last_30_days' => fn (): array => Arr::only(
                PreviewSales::totals(PreviewSales::inPeriod($sales(), SalesPeriod::LastThirtyDays)),
                ['units_sold', 'revenue'],
            ),
            'recent_sales' => fn (): array => PreviewSales::recent($sales()),
        ]);
    }

    public function edit(int $product, ReferenceOptions $options): Response
    {
        Gate::authorize('access-preview');
        $this->ensureExists($product);

        // One query, however many of the two props below are sent: the picker lists every material and the product is
        // costed against the same rows. A partial reload that lists neither runs no query.
        /** @var Collection<int, Material>|null $loaded */
        $loaded = null;
        $materials = function () use (&$loaded): Collection {
            return $loaded ??= PreviewMaterials::byName();
        };

        return Inertia::render('products/edit', [
            // Active categories, plus the product's own when it has been archived since (MOCK: its category comes from the fixture).
            'categories' => fn (): array => $options->productCategories(PreviewFixture::product($product)['category'] ?? null),
            'statuses' => ProductStatus::options(),
            'materials' => fn (): array => PreviewMaterials::options($materials()),
            'product' => function () use ($materials, $product): array {
                $catalog = PreviewProducts::from($materials());

                return $catalog->detail($catalog->find($product) ?? abort(404));
            },
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
