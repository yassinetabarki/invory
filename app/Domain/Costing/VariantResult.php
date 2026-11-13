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
        public ?string $materialCost,           // scale 4; "0.0000" for an empty composition; null when a line has no unit cost (INV-21)
        public ?string $unitCost,               // scale 4; null when a line has no unit cost: a colour the material lacks, cost per colour
        public ?int $maxProducible,             // null for an empty composition
        public ?int $bottleneckMaterialId,
        public ?string $bottleneckMaterialName,
        public ?Margin $margin,                 // null when the product has no selling price, or no unit cost
        public ?int $bottleneckColourId = null, // the colour of the bottleneck's stock; null for a one-stock material (INV-21)
        public bool $bottleneckMissing = false, // the bottleneck material lacks that colour (stock 0)
    ) {}
}
