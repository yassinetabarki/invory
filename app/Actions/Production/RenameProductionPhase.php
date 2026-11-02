<?php

declare(strict_types=1);

namespace App\Actions\Production;

use App\Models\ProductionCounter;
use App\Models\ProductionPhase;
use App\Models\User;
use App\Services\Production\PhaseNames;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Renames a phase (INV-5-b), under the workshop's production lock. A phase that holds units can be renamed. */
final readonly class RenameProductionPhase
{
    /** @throws ValidationException on `name` */
    public function handle(ProductionPhase $phase, string $name, User $actor): ProductionPhase
    {
        return DB::transaction(function () use ($phase, $name): ProductionPhase {
            ProductionCounter::lock();
            $phases = ProductionPhase::query()->orderBy('id')->lockForUpdate()->get()->sortBy(['position', 'id'])->values();
            $locked = $phases->firstWhere('id', $phase->id) ?? abort(404);

            PhaseNames::assertFree($name, $phases, $locked);

            $locked->name = $name;
            $locked->save();

            return $locked;
        });
    }
}
