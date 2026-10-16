<?php

declare(strict_types=1);

namespace App\Services\Orders;

/**
 * What a move of units did: how many units moved and the sentence to tell the person. S-53 adds what was taken from or returned to stock.
 */
final readonly class MoveResult
{
    public function __construct(
        public int $units,
        public string $message,
    ) {}
}
