<?php

declare(strict_types=1);

namespace App\Domain\Stock;

/** One past "Start cutting" of an order line (a row of order_line_cuts) with what it remembered. */
final readonly class CutBatch
{
    /** @param list<CutBatchMaterial> $materials */
    public function __construct(
        public int $cutId,
        public int $units,
        public int $unitsReturned,
        public array $materials,
    ) {}
}
