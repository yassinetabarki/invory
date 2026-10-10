<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `MaterialOption` in resources/js/types/domain.ts: a material the composition editor's picker can offer. Needs the `unit`
 * relation, which a material always carries (see `Material::$with`).
 *
 * @mixin Material
 */
final class MaterialOptionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            ...(new MaterialRefResource($this->resource))->resolve($request),
            'unit_label' => $this->unit->label(),
            'unit_cost' => $this->unit_cost,
            'quantity_on_hand' => $this->quantity_on_hand,
            'stock_status' => $this->stockStatus()->value,
        ];
    }
}
