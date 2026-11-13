<?php

declare(strict_types=1);

namespace App\Domain\Costing;

use Brick\Math\BigDecimal;

/**
 * The lowest and highest need and units covered of one material, over the variants whose composition uses it.
 *
 * Of a material kept per colour (INV-21), `$byColour` also gives, per product colour that uses it, the lowest and highest units
 * possible over that colour's sizes ("Indigo 103–142 units") and whether the material lacks that colour. Empty for a one-stock material.
 */
final readonly class MaterialSpan
{
    /** @param  array<int, array{min: int, max: int, missing: bool}>  $byColour  By product colour id, in the order the colours were costed. */
    public function __construct(
        public int $materialId,
        public string $materialName,
        public string $unit,
        public string $neededMin,   // effective quantity per unit, scale 4, HalfUp
        public string $neededMax,
        public int $unitsCoveredMin, // ⌊ stock / need ⌋ of the material alone
        public int $unitsCoveredMax,
        public array $byColour = [],
    ) {}

    /** Whether the need differs across the variants that use the material (compared as decimals, not as strings). */
    public function varies(): bool
    {
        return ! BigDecimal::of($this->neededMin)->isEqualTo(BigDecimal::of($this->neededMax));
    }
}
