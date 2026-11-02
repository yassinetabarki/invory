<?php

declare(strict_types=1);

namespace App\Services\Production;

use App\Models\ProductionPhase;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * The name rules of a phase that need the other phases (INV-5-b): not "In cutting" or "Put in stock", and not a name the workshop
 * already uses, ignoring capitals. Run by Add and Rename under the workshop's production lock, so two saves can't both take one name.
 */
final readonly class PhaseNames
{
    /**
     * @param  Collection<int, ProductionPhase>  $phases  the workshop's phases, read under its lock
     *
     * @throws ValidationException on `name`
     */
    public static function assertFree(string $name, Collection $phases, ?ProductionPhase $ignore = null): void
    {
        $key = mb_strtolower($name);

        if (in_array($key, [mb_strtolower(ProductionMessages::IN_CUTTING), mb_strtolower(ProductionMessages::PUT_IN_STOCK)], true)) {
            throw ValidationException::withMessages(['name' => ProductionMessages::nameReserved()]);
        }

        $taken = $phases->first(static fn (ProductionPhase $phase): bool => $phase->id !== $ignore?->id && mb_strtolower($phase->name) === $key);

        if ($taken !== null) {
            throw ValidationException::withMessages(['name' => ProductionMessages::nameTaken($taken->name)]);
        }
    }
}
