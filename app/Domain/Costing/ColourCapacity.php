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
        public ?int $bottleneckColourId = null, // the colour of the bottleneck's stock; null for a one-stock material (INV-21)
        public bool $bottleneckMissing = false, // the bottleneck material lacks that colour (stock 0)
    ) {}
}
