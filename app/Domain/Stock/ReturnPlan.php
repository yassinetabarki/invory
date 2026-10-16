<?php

declare(strict_types=1);

namespace App\Domain\Stock;

use Brick\Math\BigDecimal;

/** What "Move back to To start" of N units would give back, cut by cut and material by material. */
final readonly class ReturnPlan
{
    /**
     * @param  list<BatchReturn>  $batches  newest first, only cuts that give back at least one unit
     * @param  list<MaterialReturn>  $returns  first-appearance order, newest cut first
     */
    public function __construct(
        public int $units,
        public int $unitsCovered,
        public array $batches,
        public array $returns,
        public bool $nothingTaken,
    ) {}

    public function changesStock(): bool
    {
        foreach ($this->returns as $return) {
            if ($return->notReturned === null && BigDecimal::of($return->returned)->isPositive()) {
                return true;
            }
        }

        return false;
    }
}
