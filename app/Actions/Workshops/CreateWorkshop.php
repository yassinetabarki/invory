<?php

declare(strict_types=1);

namespace App\Actions\Workshops;

use App\Models\Workshop;

/**
 * Creates a new, empty workshop (ADR-0013). Sign-up and the demo seeder call it; S-45 and S-46 extend it.
 */
final readonly class CreateWorkshop
{
    public function handle(): Workshop
    {
        return Workshop::query()->create();
    }
}
