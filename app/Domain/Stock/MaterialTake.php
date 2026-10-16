<?php

declare(strict_types=1);

namespace App\Domain\Stock;

/** One material of a planned cut: what it needs, what is in stock, what is left, and by how much it falls short. */
final readonly class MaterialTake
{
    public function __construct(
        public int $materialId,
        public string $materialName,
        public string $unit,
        public string $perUnitExact,  // exact need per unit (at most 8 decimals); stored as-is in the cut
        public string $needed,        // round4(perUnitExact × units), HalfUp, scale 4
        public string $inStock,       // scale 4
        public ?string $after,        // inStock − needed, scale 4; null when short
        public ?string $shortBy,      // round4(units × perUnitExact − stock) when that is above 0; else null
    ) {}
}
