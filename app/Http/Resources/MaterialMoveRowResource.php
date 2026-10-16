<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Stock\MaterialReturn;
use App\Domain\Stock\MaterialTake;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `MaterialMoveRow` in resources/js/types/domain.ts: one row of the move window's materials table. It wraps
 * `['material' => Material, 'take' => MaterialTake]` (Start cutting) or `['material' => Material, 'return' => MaterialReturn]` (Move
 * back to To start) and only renders the plan's figures. A Start cutting row never has `not_returned`; a not-returned row shows what
 * those units took and no "after".
 */
final class MaterialMoveRowResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Material $material */
        $material = $this->resource['material'];
        $ref = (new MaterialRefResource($material))->resolve($request);

        if (isset($this->resource['return'])) {
            /** @var MaterialReturn $return */
            $return = $this->resource['return'];

            return [
                'material' => $ref,
                'quantity' => $return->notReturned === null ? $return->returned : $return->taken,
                'in_stock' => $return->inStock ?? $material->quantity_on_hand,
                'after' => $return->after,
                'short_by' => null,
                'not_returned' => $return->notReturned,
            ];
        }

        /** @var MaterialTake $take */
        $take = $this->resource['take'];

        return [
            'material' => $ref,
            'quantity' => $take->needed,
            'in_stock' => $take->inStock,
            'after' => $take->after,
            'short_by' => $take->shortBy,
            'not_returned' => null,
        ];
    }
}
