<?php

declare(strict_types=1);

namespace App\Domain\Costing;

final readonly class CostingResult
{
    public function __construct(
        public CostBreakdown $cost,
        public ProductionCapacity $capacity,
    ) {}
}
