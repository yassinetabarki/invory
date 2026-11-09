<?php

declare(strict_types=1);

namespace App\Domain\Costing;

/** What one colour can make: its smallest and largest variant capacity. All null when no size has a composition ("No composition"). */
final readonly class ColourCapacity
{
    public function __construct(
        public int $colourId,
        public ?int $guaranteed,
        public ?int $best,
        public ?int $bindingSizeId,
        public ?int $bottleneckMaterialId,
        public ?string $bottleneckMaterialName,
    ) {}
}
