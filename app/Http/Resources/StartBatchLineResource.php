<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Costing\ProductionCapacity;
use App\Models\Product;
use App\Models\Size;
use App\Services\Products\FinishedStockLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `StartBatchLine` in resources/js/types/domain.ts (INV-5-c, AC-2): the Start batch window's grid for one product, with what each
 * colour and size has on the shelf ("Shelf 2") and "Can produce now 112", from the product's costing on the stock saved now. For a
 * product that varies by colour and size it is the guaranteed figure (INV-16-k), with the bottleneck of the variant that makes the fewest.
 *
 * @property array{line: array{product: Product, sizes: list<Size>, one_size: bool, stock: FinishedStockLevel}, capacity: ProductionCapacity} $resource
 */
final class StartBatchLineResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $capacity = $this->resource['capacity'];

        return [
            ...(new RestockLineResource($this->resource['line']))->toArray($request),
            'can_produce' => $capacity->maxProducible,
            'bottleneck' => $capacity->bottleneckMaterialName,
        ];
    }
}
