<?php

declare(strict_types=1);

namespace App\Actions\Workshops;

use App\Actions\Colours\CreateDefaultColours;
use App\Actions\Sizes\CreateDefaultSizes;
use App\Models\Workshop;

/**
 * Creates a new workshop (ADR-0013), empty except for its 20 default colours (INV-1, R6) and its 19 default sizes (INV-18). Sign-up and the demo seeder call it; S-45
 * and S-46 extend it.
 */
final readonly class CreateWorkshop
{
    public function __construct(private CreateDefaultColours $createDefaultColours, private CreateDefaultSizes $createDefaultSizes) {}

    public function handle(): Workshop
    {
        $workshop = Workshop::query()->create();

        $this->createDefaultColours->handle($workshop);
        $this->createDefaultSizes->handle($workshop);

        return $workshop;
    }
}
