<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `ProductListItem`: the lightweight row of the products list. The product must carry its category, its composition (with the
 * materials) and the attributes the controller sets (`costing`, `units_sold_last_30_days`).
 *
 * @mixin Product
 */
final class ProductListResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $margin = $this->costing->margin;

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'category' => $this->category->code,
            'category_label' => $this->category->name,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'photo_url' => $this->photo_url,
            'components_count' => $this->components->count(),
            'unit_cost' => $this->costing->result->cost->unitCost,
            'selling_price' => $this->selling_price,
            'margin' => $margin === null ? null : ['amount' => $margin->amount, 'percent' => $margin->percent],
            'capacity' => (new CapacityResource($this->resource))->resolve($request),
            'units_sold_last_30_days' => $this->units_sold_last_30_days,
        ];
    }
}
