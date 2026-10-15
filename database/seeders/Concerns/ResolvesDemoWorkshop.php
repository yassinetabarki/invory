<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use App\Actions\Workshops\CreateWorkshop;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

/**
 * The workshop the demo data belongs to (ADR-0013): the current one, else the one of `test@example.com`, else the first workshop,
 * else a new one. Every demo seeder asks it, so each can run alone (tests, `db:seed --class`) and still agree with the others.
 * There is no global scope yet, and the seeders filter by this id explicitly, which stays correct once there is one.
 */
trait ResolvesDemoWorkshop
{
    private function demoWorkshopId(): int
    {
        return CurrentWorkshop::id()
            ?? User::query()->where('email', 'test@example.com')->value('workshop_id')
            ?? Workshop::query()->orderBy('id')->value('id')
            ?? app(CreateWorkshop::class)->handle()->id;
    }
}
