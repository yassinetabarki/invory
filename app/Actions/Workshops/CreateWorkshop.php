<?php

declare(strict_types=1);

namespace App\Actions\Workshops;

use App\Actions\Colours\CreateDefaultColours;
use App\Models\Workshop;

/**
 * Creates a new workshop (ADR-0013), empty except for its 20 default colours (INV-1, R6). Sign-up and the demo seeder call it; S-45
 * and S-46 extend it.
 */
final readonly class CreateWorkshop
{
    public function __construct(private CreateDefaultColours $createDefaultColours) {}

    public function handle(): Workshop
    {
        $workshop = Workshop::query()->create();

        $this->createDefaultColours->handle($workshop);

        return $workshop;
    }
}
