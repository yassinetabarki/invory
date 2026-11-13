<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `CostBreakdown`: the figures of the domain's cost breakdown, as the scale-4 strings it already holds (nothing is computed here).
 * The material and unit cost are ranges, lowest to highest over every colour and size (one number twice when nothing varies); both are null when no colour and size has a unit cost (INV-21).
 *
 * @mixin Product
 */
final class CostBreakdownResource extends JsonResource
{
    /** @return array{material_cost: array{min: string, max: string}|null, labor_cost: string, overhead_cost: string, unit_cost: array{min: string, max: string}|null} */
    public function toArray(Request $request): array
    {
        $costing = $this->costing;
        $cost = $costing->result->cost;

        return [
            'material_cost' => $costing->materialCostRange(),
            'labor_cost' => $cost->laborCost,
            'overhead_cost' => $cost->overheadCost,
            'unit_cost' => $costing->unitCostRange(),
        ];
    }
}
