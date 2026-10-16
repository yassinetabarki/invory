<?php

declare(strict_types=1);

namespace App\Services\Orders;

/**
 * What a move of units did: how many units moved, the sentence to tell the person, and whether any material's stock changed (S-53:
 * the sentence then ends with what was taken from or returned to stock).
 */
final readonly class MoveResult
{
    public function __construct(
        public int $units,
        public string $message,
        public bool $stockChanged = false,
    ) {}
}
