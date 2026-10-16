<?php

declare(strict_types=1);

namespace App\Domain\Stock;

/** How many units come back out of one cut (the caller adds them to that cut's units_returned). */
final readonly class BatchReturn
{
    public function __construct(
        public int $cutId,
        public int $units,
    ) {}
}
