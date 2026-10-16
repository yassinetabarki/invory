<?php

declare(strict_types=1);

namespace App\Domain\Costing;

final readonly class CostingResult
{
    /** @param list<MaterialRequirement> $requirements one per material, in the order each first appears in the BOM; [] for an empty BOM */
    public function __construct(
        public CostBreakdown $cost,
        public ProductionCapacity $capacity,
        public array $requirements,
    ) {}
}
