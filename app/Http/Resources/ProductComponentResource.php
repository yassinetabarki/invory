<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Costing\LineCost;
use App\Models\ProductComponent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `ProductComponentLine`: one line of a product's composition with what the domain computed for it. The costing line, the 1-based
 * display position and the line's cells come from the product's resource, which knows the order of the lines, of the colours and of the
 * sizes.
 *
 * A "By colour and size" line (INV-16) has no costing line of its own: its quantity, effective quantity, line cost and units possible
 * are null ("Varies"), and its `quantities` are the filled cells. A "Same for every variant" line has none.
 *
 * @mixin ProductComponent
 */
final class ProductComponentResource extends JsonResource
{
    /**
     * @param  LineCost|null  $line  Null for a By colour and size line.
     * @param  bool  $isBottleneck  Whether the line's material is what limits the product (the binding variant's bottleneck).
     * @param  list<array{colour_id: int, size_id: int, quantity: string}>  $quantities  The cells, colours in list order, then sizes in order.
     * @param  list<array<string, mixed>>|null  $stockByColour  `ColourStockReading` for each of the product's colours that uses the line, in list order;
     *                                                          null for a one-stock material (INV-21). Worked out by the product's resource.
     */
    public function __construct(
        ProductComponent $component,
        private readonly ?LineCost $line,
        private readonly int $position,
        private readonly bool $isBottleneck,
        private readonly array $quantities = [],
        private readonly ?array $stockByColour = null,
    ) {
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
                'stock_mode' => $material->stock_mode->value,
                'cost_mode' => $material->cost_mode->value,
            ],
            'quantity_mode' => $this->quantity_mode->value,
            'quantities' => $this->quantities,
            'quantity' => $this->line?->quantity,
            'waste_percent' => $this->line->wastePercent ?? $this->waste_percent,
            'effective_quantity' => $this->line?->effectiveQuantity,
            'unit_cost' => $this->line->unitCost ?? $material->unit_cost,
            'line_cost' => $this->line?->lineCost,
            // A material kept per colour shows its total here; each colour's stock is in `stock_by_colour`.
            'stock_on_hand' => $this->stockByColour === null ? ($this->line->stockOnHand ?? $material->quantity_on_hand) : $material->quantity_on_hand,
            'units_possible' => $this->line?->unitsPossible,
            'stock_by_colour' => $this->stockByColour,
            'is_bottleneck' => $this->isBottleneck,
            'notes' => $this->notes,
        ];
    }
}
