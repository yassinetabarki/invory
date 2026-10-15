<?php

declare(strict_types=1);

namespace Database\Factories\Concerns;

use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

/**
 * The workshop a factory puts a new root record or user in when the test doesn't say: the current one, else the first one
 * (lowest id), else a new one. So a test that never mentions workshops lives in one workshop, and a test that wants a second one
 * creates it and passes it. Call it inside a closure so it is resolved when the row is made, not when the factory is defined.
 */
final class DefaultWorkshop
{
    public static function id(): int
    {
        return CurrentWorkshop::id()
            ?? Workshop::query()->orderBy('id')->value('id')
            ?? Workshop::factory()->create()->id;
    }
}
