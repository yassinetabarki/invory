<?php

declare(strict_types=1);

namespace App\Domain\Sales;

use App\Domain\Costing\Decimals;
use InvalidArgumentException;

/** One recorded sale, reduced to what the sales maths needs: the product, the whole units sold and the price of one unit. */
final readonly class SaleLine
{
    /**
     * @param  int  $quantity  Whole units sold, >= 1.
     * @param  string  $unitPrice  Price of one unit as a plain decimal string ("89.0000"), >= 0.
     *
     * @throws InvalidArgumentException If the quantity is below 1, or the price is malformed or negative.
     */
    public function __construct(
        public int $productId,
        public int $quantity,
        public string $unitPrice,
    ) {
        if ($quantity < 1) {
            throw new InvalidArgumentException(sprintf('quantity must be at least 1, got %d.', $quantity));
        }

        Decimals::parseNonNegative($unitPrice, 'unitPrice');
    }
}
