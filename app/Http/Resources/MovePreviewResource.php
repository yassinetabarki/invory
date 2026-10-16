<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Stock\CutPlan;
use App\Domain\Stock\MaterialReturn;
use App\Domain\Stock\MaterialTake;
use App\Domain\Stock\ReturnPlan;
use App\Enums\OrderLineMove;
use App\Models\Material;
use App\Services\Orders\CuttingPreview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `MovePreview` in resources/js/types/domain.ts: the data of the move window's materials table. It wraps the array
 * {@see CuttingPreview::forStartCutting()} or {@see CuttingPreview::forBackToStart()} returns and only renders it: the rows are the
 * plan's takes or returns by material name, every figure and the short-stock message come from the server. The fields that only mean
 * something for the other move keep a neutral value (`has_composition` true, `nothing_taken` false, `max_cuttable` null).
 */
final class MovePreviewResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var OrderLineMove $move */
        $move = $this->resource['move'];
        /** @var CutPlan|ReturnPlan $plan */
        $plan = $this->resource['plan'];
        /** @var array<int, Material> $materials */
        $materials = $this->resource['materials'];

        /** @var list<array{material: Material, take?: MaterialTake, return?: MaterialReturn}> $rows */
        $rows = [];

        foreach ($plan instanceof ReturnPlan ? $plan->returns : $plan->takes as $line) {
            $rows[] = $line instanceof MaterialReturn
                ? ['material' => $materials[$line->materialId], 'return' => $line]
                : ['material' => $materials[$line->materialId], 'take' => $line];
        }

        usort($rows, static fn (array $a, array $b): int => [$a['material']->name, $a['material']->id] <=> [$b['material']->name, $b['material']->id]);

        return [
            'move' => $move->value,
            'units' => $this->resource['units'],
            'rows' => array_map(static fn (array $row): array => (new MaterialMoveRowResource($row))->resolve($request), $rows),
            'has_composition' => $plan instanceof CutPlan ? $plan->hasComposition() : true,
            'nothing_taken' => $plan instanceof ReturnPlan && $plan->nothingTaken,
            'max_cuttable' => $plan instanceof CutPlan ? $plan->maxCuttable : null,
            'short_message' => $this->resource['short_message'],
        ];
    }
}
