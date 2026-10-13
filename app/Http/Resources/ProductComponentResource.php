<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Costing\LineCost;
use App\Models\ProductComponent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `ProductComponentLine`: one line of a product's composition with what the domain computed for it. The costing line and the
 * 1-based display position come from the product's resource, which knows the order of the lines.
 *
 * @mixin ProductComponent
 */
final class ProductComponentResource extends JsonResource
{
    public function __construct(ProductComponent $component, private readonly LineCost $line, private readonly int $position)
    {
        parent::__construct($component);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $material = $this->material;

        return [
            'id' => $this->id,
            'position' => $this->position,
            'material' => [
                ...(new MaterialRefResource($material))->resolve($request),
                'photo_url' => $material->photo_url,
                'stock_status' => $material->stockStatus()->value,
                'reorder_level' => $material->reorder_level,
            ],
            'quantity' => $this->line->quantity,
            'waste_percent' => $this->line->wastePercent,
            'effective_quantity' => $this->line->effectiveQuantity,
            'unit_cost' => $this->line->unitCost,
            'line_cost' => $this->line->lineCost,
            'stock_on_hand' => $this->line->stockOnHand,
            'units_possible' => $this->line->unitsPossible,
            'is_bottleneck' => $this->line->isBottleneck,
            'notes' => $this->notes,
        ];
    }
}
