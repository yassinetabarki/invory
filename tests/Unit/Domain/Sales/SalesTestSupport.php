<?php

use App\Domain\Sales\ProductSales;
use App\Domain\Sales\SaleLine;
use App\Domain\Sales\SalesCalculator;
use App\Domain\Sales\SalesSummary;

/*
|--------------------------------------------------------------------------
| Shared builders for the sales domain tests
|--------------------------------------------------------------------------
|
| Loaded with require_once by each test file in this folder (this file is not a *Test.php,
| so PHPUnit never runs it). All names are prefixed with "sales" to stay clear of other global helpers.
|
*/

/**
 * The 60-row sales fixture of the UI-first plan ("Preview fixture > Sales"), transcribed from the plan.
 * Row: [sale id, days ago, product id, quantity]. The unit price of every sale is the product's selling price.
 *
 * @return list<array{int, int, int, int}>
 */
function salesFixtureRows(): array
{
    return [
        [1, 150, 2, 30], [2, 138, 5, 12], [3, 121, 1, 6], [4, 110, 7, 10], [5, 96, 4, 3],
        [6, 84, 2, 25], [7, 73, 3, 6], [8, 61, 5, 9], [9, 52, 1, 7], [10, 44, 2, 18],
        [11, 38, 7, 5], [12, 33, 4, 2], [13, 29, 2, 10], [14, 29, 7, 4], [15, 28, 5, 5],
        [16, 27, 1, 3], [17, 27, 4, 1], [18, 26, 5, 5], [19, 26, 2, 8], [20, 25, 7, 5],
        [21, 24, 5, 7], [22, 24, 3, 3], [23, 23, 2, 12], [24, 22, 1, 6], [25, 21, 7, 3],
        [26, 21, 4, 3], [27, 20, 2, 14], [28, 20, 5, 6], [29, 19, 7, 6], [30, 18, 1, 4],
        [31, 18, 2, 6], [32, 17, 5, 8], [33, 17, 3, 1], [34, 16, 4, 2], [35, 16, 7, 2],
        [36, 15, 2, 10], [37, 15, 5, 4], [38, 14, 1, 5], [39, 14, 7, 4], [40, 13, 1, 5],
        [41, 13, 2, 15], [42, 13, 4, 2], [43, 12, 1, 6], [44, 11, 5, 10], [45, 11, 2, 10],
        [46, 10, 7, 4], [47, 10, 3, 3], [48, 9, 2, 10], [49, 9, 4, 2], [50, 9, 5, 5],
        [51, 8, 2, 15], [52, 7, 1, 5], [53, 6, 5, 8], [54, 5, 3, 2], [55, 4, 2, 12],
        [56, 3, 7, 3], [57, 3, 1, 4], [58, 2, 4, 2], [59, 1, 2, 20], [60, 1, 5, 6],
    ];
}

/**
 * Selling price of each product of the fixture (product 6, the draft Chore Jacket, has no price and no sales).
 *
 * @return array<int, string>
 */
function salesFixturePrices(): array
{
    return [1 => '89.0000', 2 => '24.0000', 3 => '69.0000', 4 => '249.0000', 5 => '79.0000', 7 => '45.0000'];
}

/**
 * The fixture's sales whose date is within the last $maxDaysAgo days before today (days ago 0 = today),
 * in id order: 29 = last 30 days, 6 = last 7 days, 7 = this month (today is 2026-10-08), omitted = this year.
 *
 * @return list<SaleLine>
 */
function salesFixture(?int $maxDaysAgo = null): array
{
    $prices = salesFixturePrices();
    $lines = [];

    foreach (salesFixtureRows() as [, $daysAgo, $productId, $quantity]) {
        if ($maxDaysAgo === null || $daysAgo <= $maxDaysAgo) {
            $lines[] = new SaleLine($productId, $quantity, $prices[$productId]);
        }
    }

    return $lines;
}

/** @param list<SaleLine> $sales */
function salesSummarize(array $sales): SalesSummary
{
    return (new SalesCalculator)->summarize($sales);
}

/**
 * @param  list<SaleLine>  $sales
 * @return list<array{int, int, string, string}> [product id, units sold, revenue, percent of top] per ranked product
 */
function salesTop(array $sales, string $by, int $limit = 5): array
{
    return array_map(
        fn (ProductSales $p): array => [$p->productId, $p->unitsSold, $p->revenue, $p->percentOfTop],
        (new SalesCalculator)->topProducts($sales, $by, $limit),
    );
}
