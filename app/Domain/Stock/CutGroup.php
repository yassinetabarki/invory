<?php

declare(strict_types=1);

namespace App\Domain\Stock;

/**
 * The units of one colour and size inside a cut, with the exact amount each unit takes of every material it uses
 * (a row of production_cut_variants and its production_cut_variant_materials, ADR-0027 decision 7).
 */
final readonly class CutGroup
{
    /** @param array<int, string> $perUnit exact amount per unit (at most 8 decimals) by material id; only the materials this variant uses */
    public function __construct(
        public int $colourId,
        public int $sizeId,
        public int $units,
        public int $unitsReturned,
        public array $perUnit,
    ) {}

    public function outstanding(): int
    {
        return $this->units - $this->unitsReturned;
    }
}
