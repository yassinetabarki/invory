<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Products\CreateProduct;
use App\Actions\Products\DeleteProduct;
use App\Actions\Products\UpdateProduct;
use App\Enums\ProductStatus;
use App\Enums\SalesPeriod;
use App\Http\Requests\Products\IndexProductRequest;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Mock\PreviewProducts;
use App\Mock\PreviewSales;
use App\Models\Product;
use App\Models\User;
use App\Services\Costing\ProductCostingService;
use App\Services\Products\ProductIndexQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Products are real: Eloquent reads, the S-04a Actions for writes, `ProductPolicy` on every route, and the Resources send
 * exactly the props of resources/js/types/domain.ts. What has no table yet comes from App\Mock (ADR-0006, removed by S-17): the
 * photo swatches (S-15-BE) and the sales (S-14-BE).
 *
 * @phpstan-import-type DatedSale from PreviewSales
 */
final class ProductController extends Controller
{
    public function index(IndexProductRequest $request, ProductIndexQuery $products): Response
    {
        Gate::authorize('viewAny', Product::class);

        $filters = $request->filters();

        // Lazy (a closure): costing the products is the expensive part, and a partial reload that does not list `products` skips it.
        return Inertia::render('products/index', [
            'products' => function () use ($products, $filters): AnonymousResourceCollection {
                $page = $products->paginate($filters, (int) config('invory.per_page'));

                PreviewProducts::decorate($page->items());

                // MOCK (ADR-0006, S-14-BE): the units sold come from the preview sales of the products on this page.
                $sold = PreviewSales::unitsSoldLastThirtyDays($page->items());

                foreach ($page->items() as $product) {
                    $product->setAttribute('units_sold_last_30_days', $sold[$product->id]);
                }

                return ProductListResource::collection($page);
            },
            'filters' => $filters,
            'statuses' => ProductStatus::options(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Product::class);

        return Inertia::render('products/create', [
            'statuses' => ProductStatus::options(),
        ]);
    }

    public function store(StoreProductRequest $request, CreateProduct $createProduct): RedirectResponse
    {
        // Photos are a preview (ADR-0006): validated, but neither stored nor part of the product.
        $product = $createProduct->handle($request->safe()->except(['photo', 'remove_photo']), $this->actor($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => $this->toastMessage($request, __('Product created.'))]);

        return to_route('products.show', $product);
    }

    public function show(Product $product, ProductCostingService $costing): Response
    {
        Gate::authorize('view', $product);

        /** @var list<DatedSale>|null $loaded */
        $loaded = null;
        // MOCK (ADR-0006, S-14-BE): the sales of this product, from the fixture. Read once for both props below.
        $sales = function () use (&$loaded, $product): array {
            return $loaded ??= PreviewSales::ofProduct(PreviewSales::resolve([$product]), $product->id);
        };

        return Inertia::render('products/show', [
            'product' => function () use ($product, $costing): ProductResource {
                $product->load(['category', 'components.material' => fn ($materials) => $materials->without('category')]);

                return $this->resource($product, $costing);
            },
            'sales_last_30_days' => fn (): array => Arr::only(
                PreviewSales::totals(PreviewSales::inPeriod($sales(), SalesPeriod::LastThirtyDays)),
                ['units_sold', 'revenue'],
            ),
            'recent_sales' => fn (): array => PreviewSales::recent($sales()),
        ]);
    }

    public function edit(Product $product, ProductCostingService $costing): Response
    {
        Gate::authorize('update', $product);

        return Inertia::render('products/edit', [
            'statuses' => ProductStatus::options(),
            // The picker searches the materials itself (`materials.options`, S-25): only the materials of the lines are loaded here.
            'product' => function () use ($product, $costing): ProductResource {
                $product->load(['category', 'components.material' => fn ($materials) => $materials->without('category')]);

                return $this->resource($product, $costing);
            },
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, UpdateProduct $updateProduct): RedirectResponse
    {
        $updateProduct->handle($product, $request->safe()->except(['photo', 'remove_photo']), $this->actor($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => $this->toastMessage($request, __('Product updated.'))]);

        return to_route('products.show', $product);
    }

    public function destroy(Product $product, DeleteProduct $deleteProduct): RedirectResponse
    {
        Gate::authorize('delete', $product);

        $deleteProduct->handle($product);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product deleted.')]);

        return to_route('products.index');
    }

    /** A product with its composition loaded, costed and ready for `ProductResource`. */
    private function resource(Product $product, ProductCostingService $costing): ProductResource
    {
        $product->setAttribute('costing', $costing->cost($product));
        PreviewProducts::decorate([$product]);

        return new ProductResource($product);
    }

    /** The signed-in user, for the audit columns. The `auth` middleware and the request authorization guarantee one. */
    private function actor(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : abort(401);
    }

    /**
     * MOCK (ADR-0006): says so when a photo was sent (or removed), because photos are not saved yet.
     */
    private function toastMessage(StoreProductRequest|UpdateProductRequest $request, string $message): string
    {
        if ($request->hasFile('photo') || $request->boolean('remove_photo')) {
            return $message.' '.__("Photos are a preview and weren't saved.");
        }

        return $message;
    }
}
