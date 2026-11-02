<?php

declare(strict_types=1);

namespace App\Actions\Production;

use App\Models\ProductionCounter;
use App\Models\ProductionPhase;
use App\Models\User;
use App\Services\Production\PhaseHoldings;
use App\Services\Production\ProductionMessages;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Move up / Move down (INV-5-b): swaps a phase with its neighbour, under the workshop's production lock. A phase that holds units keeps its
 * place, so the swap is refused when either of the two holds units, with that phase's sentence (the human's answer, 2026-11-02). The
 * first phase can't go up nor the last down: nothing changes.
 */
final readonly class MoveProductionPhase
{
    public function __construct(private PhaseHoldings $holdings) {}

    /**
     * @param  'up'|'down'  $direction
     *
     * @throws ValidationException on `phase`
     */
    public function handle(ProductionPhase $phase, string $direction, User $actor): void
    {
        DB::transaction(function () use ($phase, $direction): void {
            ProductionCounter::lock();
            $phases = ProductionPhase::query()->orderBy('id')->lockForUpdate()->get()->sortBy(['position', 'id'])->values();
            $index = $phases->search(static fn (ProductionPhase $candidate): bool => $candidate->id === $phase->id);

            if ($index === false) {
                abort(404);
            }

            $neighbour = $phases->get($direction === 'up' ? $index - 1 : $index + 1);

            if ($neighbour === null) {
                return;
            }

            $held = $this->holdings->all();

            foreach ([$phases[$index], $neighbour] as $candidate) {
                if (($held[$candidate->id] ?? 0) > 0) {
                    throw ValidationException::withMessages(['phase' => ProductionMessages::phaseHolds($candidate->name, $held[$candidate->id])]);
                }
            }

            [$a, $b] = [$phases[$index]->position, $neighbour->position];
            $phases[$index]->update(['position' => $b]);
            $neighbour->update(['position' => $a]);
        });
    }
}
