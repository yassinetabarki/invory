<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\Size;

/**
 * The finished units of a product on the shelf (S-56): every size of its list, in order, 0 included, and the total. Whole units,
 * no money.
 */
final readonly class FinishedStockLevel
{
    /** @param  list<array{size: Size, units: int}>  $sizes */
    public function __construct(
        public int $units,
        public array $sizes,
    ) {}
}
