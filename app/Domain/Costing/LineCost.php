<?php

declare(strict_types=1);

namespace App\Domain\Costing;

/**
 * The costing of one BOM line. Decimals are strings: scale 4 (scale 2 for the waste percentage), HALF_UP.
 *
 * When a material appears on several lines, `unitsPossible` and `isBottleneck` describe the material
 * (all its requirements aggregated), not the single line.
 */
final readonly class LineCost
{
    public function __construct(
        public int $materialId,
        public string $materialName,
        public string $unit,
        public string $quantity,
        public string $wastePercent,
        public string $effectiveQuantity,
        public string $unitCost,
        public string $lineCost,
        public string $stockOnHand,
        public int $unitsPossible,
        public bool $isBottleneck,
    ) {}
}
