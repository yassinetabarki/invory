<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Stock\ReturnPlan;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `UndoBatchPreview` in resources/js/types/domain.ts (INV-5-e, AC-6): what Undo batch gives back, by material name ("7.92 m Denim
 * 12oz Indigo, 32 pc Metal shank button 17mm, …"). Every figure is the plan's.
 *
 * @property array{plan: ReturnPlan, materials: array<int, Material>} $resource
 */
final class UndoBatchPreviewResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $plan = $this->resource['plan'];
        $materials = $this->resource['materials'];
        $rows = [];

        foreach ($plan->returns as $return) {
            $rows[] = ['material' => $materials[$return->materialId], 'return' => $return];
        }

        usort($rows, static fn (array $a, array $b): int => [$a['material']->name, $a['material']->id] <=> [$b['material']->name, $b['material']->id]);

        return [
            'rows' => array_map(static fn (array $row): array => (new MaterialMoveRowResource($row))->resolve($request), $rows),
            'nothing_taken' => $plan->nothingTaken,
        ];
    }
}
