<?php

declare(strict_types=1);

namespace App\Domain\Costing;

use Brick\Math\BigDecimal;

/** The lowest and highest need and units covered of one material, over the variants whose composition uses it. */
final readonly class MaterialSpan
{
    public function __construct(
        public int $materialId,
        public string $materialName,
        public string $unit,
        public string $neededMin,   // effective quantity per unit, scale 4, HalfUp
        public string $neededMax,
        public int $unitsCoveredMin, // ⌊ stock / need ⌋ of the material alone
        public int $unitsCoveredMax,
    ) {}

    /** Whether the need differs across the variants that use the material (compared as decimals, not as strings). */
    public function varies(): bool
    {
        return ! BigDecimal::of($this->neededMin)->isEqualTo(BigDecimal::of($this->neededMax));
    }
}
