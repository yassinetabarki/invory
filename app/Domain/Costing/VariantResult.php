<?php

declare(strict_types=1);

namespace App\Domain\Costing;

/**
 * The costing of one variant (colour and size): the figures a CostingCalculator::calculate() of its composition gives for the cost
 * and the capacity (VariantCostingTest checks they agree), without its per-line breakdown.
 */
final readonly class VariantResult
{
    public function __construct(
        public int $colourId,
        public int $sizeId,
        public string $materialCost,            // scale 4; "0.0000" for an empty composition
        public string $unitCost,                // scale 4
        public ?int $maxProducible,             // null for an empty composition
        public ?int $bottleneckMaterialId,
        public ?string $bottleneckMaterialName,
        public ?Margin $margin,                 // null when the product has no selling price
    ) {}
}
