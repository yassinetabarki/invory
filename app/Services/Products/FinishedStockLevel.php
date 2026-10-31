<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\Colour;
use App\Models\Size;

/**
 * The finished units of a product on the shelf (S-56, INV-2): every colour of the product in list order, each with every size of its
 * list in order, 0 included, and each colour's total; `sizes` and `units` are the totals over every colour. Whole units, no money.
 */
final readonly class FinishedStockLevel
{
    /**
     * @param  list<array{size: Size, units: int}>  $sizes  per size, over every colour
     * @param  list<array{colour: Colour, units: int, sizes: list<array{size: Size, units: int}>}>  $colours
     */
    public function __construct(
        public int $units,
        public array $sizes,
        public array $colours,
    ) {}
}
