<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Stock\CutPlan;
use App\Models\Product;
use App\Models\Size;
use App\Services\Products\FinishedStockLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `StartBatchLine` in resources/js/types/domain.ts (INV-5-c, AC-2): the Start batch window's grid for one product, with what each
 * colour and size has on the shelf ("Shelf 2") and "Can produce now 112", from a one-unit plan on the stock saved now.
 *
 * @property array{line: array{product: Product, sizes: list<Size>, one_size: bool, stock: FinishedStockLevel}, plan: CutPlan} $resource
 */
final class StartBatchLineResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $plan = $this->resource['plan'];

        return [
            ...(new RestockLineResource($this->resource['line']))->toArray($request),
            'can_produce' => $plan->maxCuttable,
            'bottleneck' => $plan->bottleneckMaterialName,
        ];
    }
}
