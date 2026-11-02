<?php

declare(strict_types=1);

namespace App\Actions\Production;

use App\Models\ProductionCounter;
use App\Models\ProductionPhase;
use App\Models\User;
use App\Services\Production\PhaseNames;
use App\Services\Production\ProductionMessages;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Adds a phase at the end of the workshop's path (INV-5-b), under the workshop's production lock: at most 6, each name once. */
final readonly class AddProductionPhase
{
    /** @throws ValidationException on `name` */
    public function handle(string $name, User $actor): ProductionPhase
    {
        return DB::transaction(function () use ($name): ProductionPhase {
            ProductionCounter::lock();
            $phases = ProductionPhase::query()->orderBy('id')->lockForUpdate()->get()->sortBy(['position', 'id'])->values();

            PhaseNames::assertFree($name, $phases);

            if ($phases->count() >= ProductionPhase::MAX) {
                throw ValidationException::withMessages(['name' => ProductionMessages::tooManyPhases()]);
            }

            return ProductionPhase::query()->create(['name' => $name, 'position' => $phases->count() + 1]);
        });
    }
}
