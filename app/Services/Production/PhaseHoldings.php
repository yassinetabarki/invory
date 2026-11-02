<?php

declare(strict_types=1);

namespace App\Services\Production;

use App\Models\ProductionBatch;

/** How many units each phase of the workshop holds now: the batches in production in it (INV-5). In cutting is not a phase. */
final readonly class PhaseHoldings
{
    /** @return array<int, int> units by phase id, only the phases that hold some */
    public function all(): array
    {
        return ProductionBatch::query()
            ->inProduction()
            ->whereNotNull('phase_id')
            ->groupBy('phase_id')
            ->toBase()
            ->selectRaw('phase_id, SUM(units) as units')
            ->pluck('units', 'phase_id')
            ->map(static fn (mixed $units): int => (int) $units)
            ->all();
    }

    public function of(int $phaseId): int
    {
        return (int) ProductionBatch::query()->inProduction()->where('phase_id', $phaseId)->sum('units');
    }
}
