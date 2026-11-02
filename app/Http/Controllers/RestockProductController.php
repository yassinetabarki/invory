<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Restocks\RestockProductOptionsRequest;
use App\Http\Resources\RestockLineResource;
use App\Http\Resources\RestockProductRefResource;
use App\Models\Product;
use App\Models\ProductRestock;
use App\Services\Restocks\RestockBook;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * New restock's product picker and a product's line (INV-3, AC-3; ADR-0009 rule 5): small session-authenticated JSON routes, so the
 * page never carries every product. `{product}` is route-model bound: a deleted or other workshop's product is a 404.
 */
final class RestockProductController extends Controller
{
    public function index(RestockProductOptionsRequest $request, RestockBook $restocks): AnonymousResourceCollection
    {
        return RestockProductRefResource::collection($restocks->options($request->term(), $request->limit(), $request->included()));
    }

    public function show(Product $product, RestockBook $restocks): RestockLineResource
    {
        Gate::authorize('create', ProductRestock::class);
        Gate::authorize('view', $product);

        return new RestockLineResource($restocks->line($product));
    }
}
