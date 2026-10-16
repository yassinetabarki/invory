<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Stock\MaterialTake;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `MaterialMoveRow` in resources/js/types/domain.ts: one row of the move window's materials table. It wraps
 * `['material' => Material, 'take' => MaterialTake]` and only renders the plan's figures. A Start cutting row never has
 * `not_returned`.
 */
final class MaterialMoveRowResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Material $material */
        $material = $this->resource['material'];
        /** @var MaterialTake $take */
        $take = $this->resource['take'];

        return [
            'material' => (new MaterialRefResource($material))->resolve($request),
            'quantity' => $take->needed,
            'in_stock' => $take->inStock,
            'after' => $take->after,
            'short_by' => $take->shortBy,
            'not_returned' => null,
        ];
    }
}
