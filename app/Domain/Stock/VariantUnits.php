<?php

declare(strict_types=1);

namespace App\Domain\Stock;

use App\Domain\Costing\BomLine;
use InvalidArgumentException;

/** One colour and size of a Start batch grid: its units (at least 1) and the composition of that variant (ADR-0027 decision 6). */
final readonly class VariantUnits
{
    /**
     * @param  list<BomLine>  $lines  the variant's lines in composition order; [] for an empty composition
     *
     * @throws InvalidArgumentException If $units is below 1.
     */
    public function __construct(
        public int $colourId,
        public int $sizeId,
        public int $units,
        public array $lines,
    ) {
        if ($units < 1) {
            throw new InvalidArgumentException(sprintf('units must be at least 1, got %d.', $units));
        }
    }
}
