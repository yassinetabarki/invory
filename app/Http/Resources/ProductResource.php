<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use App\Models\ProductComponent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

/**
 * `Product`: the detail of one product with its cost breakdown, margin, capacity and composition. The product must carry its
 * category, its composition (with the materials) and the attribute the controller sets (`costing`).
 *
 * @mixin Product
 */
final class ProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $costing = $this->costing;
        $margin = $costing->margin;

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'category' => $this->category->code,
            'category_label' => $this->category->name,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'description' => $this->description,
            'photo_url' => $this->photo_url,
            'labor_cost' => $this->labor_cost,
            'overhead_cost' => $this->overhead_cost,
            'selling_price' => $this->selling_price,
            'cost' => (new CostBreakdownResource($this->resource))->resolve($request),
            'margin' => $margin === null ? null : ['amount' => $margin->amount, 'percent' => $margin->percent],
            'capacity' => (new CapacityResource($this->resource))->resolve($request),
            'components' => $this->components
                ->values()
                ->map(fn (ProductComponent $component, int $index): array => (new ProductComponentResource(
                    $component,
                    $costing->result->cost->lines[$index] ?? throw new LogicException('The costing has one line per component.'),
                    $index + 1,
                ))->resolve($request))
                ->all(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
