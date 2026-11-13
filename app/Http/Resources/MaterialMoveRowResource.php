<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Stock\MaterialReturn;
use App\Domain\Stock\MaterialTake;
use App\Models\Colour;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `MaterialMoveRow` in resources/js/types/domain.ts: one row of the move window's materials table. It wraps
 * `['material' => Material, 'take' => MaterialTake, 'colours' => array<int, Colour>]` (Start batch) or `[… 'return' => MaterialReturn …]`
 * (Undo batch) and only renders the plan's figures; the colours were read once by the service that made the plan. A Start cutting row never has `not_returned`; a not-returned row shows what
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
                'colour' => $this->colour($return->colourId, $request),
                'missing' => false,
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
            'colour' => $this->colour($take->colourId, $request),
            'missing' => $take->missing,
            'quantity' => $take->needed,
            'in_stock' => $take->inStock,
            'after' => $take->after,
            'short_by' => $take->shortBy,
            'not_returned' => null,
        ];
    }

    /**
     * The colour of a per-colour stock (INV-21): the row of a one-stock material has none. A return row names the colour it goes back
     * into, or the colour the material no longer has.
     *
     * @return array<string, mixed>|null the `Colour` of domain.ts
     */
    private function colour(?int $colourId, Request $request): ?array
    {
        /** @var array<int, Colour> $colours */
        $colours = $this->resource['colours'];
        $colour = $colourId === null ? null : ($colours[$colourId] ?? null);

        return $colour === null ? null : (new ColourResource($colour))->resolve($request);
    }
}
