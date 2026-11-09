<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\StartBatchLineResource;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Services\Costing\ProductCostingService;
use App\Services\Restocks\RestockBook;
use Illuminate\Support\Facades\Gate;

/**
 * The Start batch window's grid for one product (INV-5-c, AC-2; ADR-0009 rule 5): its sizes, its shelf per colour and size, and "Can
 * produce now" (the guaranteed figure of a product that varies by colour and size, INV-16-k). `{product}` is route-model bound: a
 * deleted or other workshop's product is a 404.
 */
final class ProductionProductController extends Controller
{
    public function show(Product $product, RestockBook $restocks, ProductCostingService $costing): StartBatchLineResource
    {
        Gate::authorize('create', ProductionBatch::class);
        Gate::authorize('view', $product);

        return new StartBatchLineResource([
            'line' => $restocks->line($product),
            'capacity' => $costing->cost($product->load('components.material'))->result->capacity,
        ]);
    }
}
