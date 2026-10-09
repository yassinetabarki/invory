<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `CostBreakdown`: the figures of the domain's cost breakdown, as the scale-4 strings it already holds (nothing is computed here).
 *
 * @mixin Product
 */
final class CostBreakdownResource extends JsonResource
{
    /** @return array{material_cost: string, labor_cost: string, overhead_cost: string, unit_cost: string} */
    public function toArray(Request $request): array
    {
        $cost = $this->costing->result->cost;

        return [
            'material_cost' => $cost->materialCost,
            'labor_cost' => $cost->laborCost,
            'overhead_cost' => $cost->overheadCost,
            'unit_cost' => $cost->unitCost,
        ];
    }
}
