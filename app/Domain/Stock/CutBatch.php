<?php

declare(strict_types=1);

namespace App\Domain\Stock;

/** One past "Start cutting" of an order line (a row of order_line_cuts) with what it remembered. */
final readonly class CutBatch
{
    /**
     * @param  list<CutBatchMaterial>  $materials
     * @param  list<CutGroup>  $groups  a cut made from INV-16 on; [] = one group (the cut's own units and each material's perUnitExact)
     */
    public function __construct(
        public int $cutId,
        public int $units,
        public int $unitsReturned,
        public array $materials,
        public array $groups = [],
    ) {}
}
