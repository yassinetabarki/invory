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
 * Removes an empty phase (INV-5-b, ADR-0023 decision 8), under the workshop's production lock, then closes the gap in the positions. A move
 * into the phase locks its row first, so a batch arriving meanwhile is counted here: "Quality check now holds 2 units, …".
 */
final readonly class RemoveProductionPhase
{
    public function __construct(private PhaseHoldings $holdings) {}

    /** @throws ValidationException on `phase` */
    public function handle(ProductionPhase $phase, User $actor): void
    {
        DB::transaction(function () use ($phase): void {
            ProductionCounter::lock();
            $phases = ProductionPhase::query()->orderBy('id')->lockForUpdate()->get()->sortBy(['position', 'id'])->values();
            $locked = $phases->firstWhere('id', $phase->id) ?? abort(404);
            $held = $this->holdings->of($locked->id);

            if ($held > 0) {
                throw ValidationException::withMessages(['phase' => ProductionMessages::phaseNowHolds($locked->name, $held)]);
            }

            $locked->delete();

            $position = 0;

            foreach ($phases as $remaining) {
                if ($remaining->id === $locked->id) {
                    continue;
                }

                $remaining->update(['position' => ++$position]);
            }
        });
    }
}
