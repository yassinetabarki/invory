<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Stock\CutPlan;
use App\Models\Material;
use App\Services\Orders\OrderMessages;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `StartBatchPreview` in resources/js/types/domain.ts (INV-5-c, AC-2): the table "Materials for 16 units" (Material · Taken · In stock
 * · After, and "Short by" when stock is short), by material name, with the refusal the save would give. Every figure is the plan's.
 *
 * @property array{plan: CutPlan, materials: array<int, Material>} $resource
 */
final class StartBatchPreviewResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $plan = $this->resource['plan'];
        $materials = $this->resource['materials'];
        $rows = [];

        foreach ($plan->takes as $take) {
            $rows[] = ['material' => $materials[$take->materialId], 'take' => $take];
        }

        usort($rows, static fn (array $a, array $b): int => [$a['material']->name, $a['material']->id] <=> [$b['material']->name, $b['material']->id]);

        return [
            'units' => $plan->units,
            'rows' => array_map(static fn (array $row): array => (new MaterialMoveRowResource($row))->resolve($request), $rows),
            'has_composition' => $plan->hasComposition(),
            'max_cuttable' => $plan->maxCuttable,
            'short_message' => $plan->allowed ? null : OrderMessages::notEnoughStock($plan),
        ];
    }
}
