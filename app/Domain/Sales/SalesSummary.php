<?php

declare(strict_types=1);

namespace App\Domain\Sales;

/** The sales of a period in numbers. */
final readonly class SalesSummary
{
    /**
     * @param  string  $revenue  Sum of the sale totals, scale 4.
     * @param  int  $unitsSold  Sum of the quantities.
     * @param  int  $salesCount  Number of sales.
     * @param  string|null  $averageSale  Revenue divided by the number of sales, scale 4. Null when there is no sale.
     * @param  int  $productsSoldCount  Number of distinct products sold.
     */
    public function __construct(
        public string $revenue,
        public int $unitsSold,
        public int $salesCount,
        public ?string $averageSale,
        public int $productsSoldCount,
    ) {}
}
