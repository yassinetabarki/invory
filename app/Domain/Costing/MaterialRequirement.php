<?php

declare(strict_types=1);

namespace App\Domain\Costing;

/** The total need of one material for one unit of product, all its BOM lines summed (costing skill: required(m)). */
final readonly class MaterialRequirement
{
    public function __construct(
        public int $materialId,
        public string $materialName,
        public string $unit,
        public string $perUnit,       // Σ effective_qty over the material's lines, scale 4, HalfUp (display)
        public string $perUnitExact,  // the same sum, unrounded (at most 8 decimals): what any multiplication starts from
        public string $stockOnHand,   // scale 4
        public int $unitsPossible,    // ⌊ stock / perUnitExact ⌋
        public bool $isBottleneck,
    ) {}
}
