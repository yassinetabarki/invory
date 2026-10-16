<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Stock\CutPlan;
use App\Domain\Stock\MaterialTake;
use App\Enums\OrderLineMove;
use App\Models\Material;
use App\Services\Orders\CuttingPreview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `MovePreview` in resources/js/types/domain.ts: the data of the move window's materials table. It wraps the array
 * {@see CuttingPreview::forStartCutting()} returns and only renders it: the rows are the plan's takes by
 * material name, every figure and the short-stock message come from the server.
 */
final class MovePreviewResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var OrderLineMove $move */
        $move = $this->resource['move'];
        /** @var CutPlan $plan */
        $plan = $this->resource['plan'];
        /** @var array<int, Material> $materials */
        $materials = $this->resource['materials'];

        $takes = $plan->takes;
        usort($takes, static fn (MaterialTake $a, MaterialTake $b): int => [$a->materialName, $a->materialId] <=> [$b->materialName, $b->materialId]);

        return [
            'move' => $move->value,
            'units' => $this->resource['units'],
            'rows' => array_map(
                static fn (MaterialTake $take): array => (new MaterialMoveRowResource(['material' => $materials[$take->materialId], 'take' => $take]))->resolve($request),
                $takes,
            ),
            'has_composition' => $plan->hasComposition(),
            'nothing_taken' => false,
            'max_cuttable' => $plan->maxCuttable,
            'short_message' => $this->resource['short_message'],
        ];
    }
}
