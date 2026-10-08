<?php

declare(strict_types=1);

namespace App\Domain\Costing;

/** How many whole units can be produced from the stock on hand, and which material limits it. All null for an empty BOM. */
final readonly class ProductionCapacity
{
    public function __construct(
        public ?int $maxProducible,
        public ?int $bottleneckMaterialId,
        public ?string $bottleneckMaterialName,
    ) {}
}
