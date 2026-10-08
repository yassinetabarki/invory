<?php

declare(strict_types=1);

namespace App\Domain\Sales;

/** One row of a "top products" ranking. */
final readonly class ProductSales
{
    /**
     * @param  int  $unitsSold  Units of the product sold over the period.
     * @param  string  $revenue  Revenue of the product over the period, scale 4.
     * @param  string  $percentOfTop  The ranking metric as a percentage of the first ranked product's, scale 2 ("100.00" for the first).
     */
    public function __construct(
        public int $productId,
        public int $unitsSold,
        public string $revenue,
        public string $percentOfTop,
    ) {}
}
