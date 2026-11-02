<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Production\StartBatchPreviewRequest;
use App\Http\Resources\StartBatchPreviewResource;
use App\Models\Product;
use App\Services\Orders\CuttingPreview;
use Illuminate\Support\Facades\Gate;

/**
 * The Start batch window's materials table (INV-5-c, AC-2; ADR-0009 rule 5): what starting N units would take, on the stock saved now,
 * read without locks. Start batch redoes the same plan under the locks. A deleted or other workshop's product is a 404.
 */
final class ProductionProductMaterialController extends Controller
{
    public function show(StartBatchPreviewRequest $request, Product $product, CuttingPreview $preview): StartBatchPreviewResource
    {
        Gate::authorize('view', $product);

        return new StartBatchPreviewResource($preview->plan($product, $request->units()));
    }
}
