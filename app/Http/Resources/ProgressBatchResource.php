<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ProductionBatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `ProgressBatch` in resources/js/types/domain.ts (INV-5-f, AC-4): one row of a phase's table. Built from `ProgressBook::phase()`.
 *
 * @property array{batch: ProductionBatch, one_size: bool, since_days: int, on_shelf: int} $resource
 */
final class ProgressBatchResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $batch = $this->resource['batch'];

        return [
            'id' => $batch->id,
            'number' => $batch->label,
            'product' => (new RestockProductRefResource($batch->product))->resolve($request),
            'colour' => (new ColourResource($batch->colour))->resolve($request),
            'size' => ['id' => $batch->size->id, 'label' => $batch->size->label],
            'one_size' => $this->resource['one_size'],
            'units' => $batch->units,
            'since' => $batch->phase_since->toDateString(),
            'since_days' => $this->resource['since_days'],
            'on_shelf' => $this->resource['on_shelf'],
        ];
    }
}
