<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Products\CreateProduct;
use App\Actions\Products\DeleteProduct;
use App\Actions\Products\UpdateProduct;
use App\Enums\ProductStatus;
use App\Http\Requests\Products\IndexProductRequest;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\SaleResource;
use App\Models\Product;
use App\Models\User;
use App\Services\Costing\ProductCostingService;
use App\Services\Products\ProductIndexQuery;
use App\Services\Sales\SalesReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Products are real: Eloquent reads, the S-04a Actions for writes, `ProductPolicy` on every route, and the Resources send
 * exactly the props of resources/js/types/domain.ts. The sales figures come from `SalesReport` (S-14-BE). A photo is stored on
 * the public disk by the Actions (S-15-BE).
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

                // The units sold come with the page's query (a sum, null without a sale).
                foreach ($page->items() as $product) {
                    $product->setAttribute('units_sold_last_30_days', (int) ($product->units_sold_last_30_days ?? 0));
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
        $product = $createProduct->handle($request->validated(), $this->actor($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product created.')]);

        return to_route('products.show', $product);
    }

    public function show(Product $product, ProductCostingService $costing, SalesReport $sales): Response
    {
        Gate::authorize('view', $product);

        return Inertia::render('products/show', [
            'product' => function () use ($product, $costing): ProductResource {
                $product->load(['category', 'components.material' => fn ($materials) => $materials->without('category')]);

                return $this->resource($product, $costing);
            },
            'sales_last_30_days' => fn (): array => $sales->lastThirtyDays($product),
            'recent_sales' => fn (): array => SaleResource::collection($sales->recentOf($product))->resolve(),
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
        $updateProduct->handle($product, $request->validated(), $this->actor($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product updated.')]);

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

        return new ProductResource($product);
    }

    /** The signed-in user, for the audit columns. The `auth` middleware and the request authorization guarantee one. */
    private function actor(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : abort(401);
    }
}
