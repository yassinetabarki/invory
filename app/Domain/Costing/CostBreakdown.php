<?php

declare(strict_types=1);

namespace App\Domain\Costing;

/** Cost of one unit of product. Decimals are scale-4 strings, HALF_UP. */
final readonly class CostBreakdown
{
    /**
     * @param  list<LineCost>  $lines  One per input line, in input order.
     */
    public function __construct(
        public array $lines,
        public string $materialCost,
        public string $laborCost,
        public string $overheadCost,
        public string $unitCost,
    ) {}
}
