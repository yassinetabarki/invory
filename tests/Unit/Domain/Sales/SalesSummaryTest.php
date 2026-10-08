<?php

use App\Domain\Sales\SaleLine;
use App\Domain\Sales\SalesCalculator;
use App\Domain\Sales\SalesSummary;

require_once __DIR__.'/SalesTestSupport.php';

// Rules: invory-costing-domain skill, "Sales".
//   revenue = sum of sale totals (scale 4) · units = sum of quantities · count · average = revenue / count (scale 4, HalfUp, null when 0)
//   products sold = distinct product ids

it('summarizes no sales: zero everywhere and no average', function () {
    $summary = salesSummarize([]);

    expect($summary)->toBeInstanceOf(SalesSummary::class)
        ->and($summary->revenue)->toBe('0.0000')
        ->and($summary->unitsSold)->toBe(0)
        ->and($summary->salesCount)->toBe(0)
        ->and($summary->averageSale)->toBeNull()
        ->and($summary->productsSoldCount)->toBe(0);
});

it('summarizes one sale', function () {
    $summary = salesSummarize([new SaleLine(1, 4, '89.0000')]);

    expect($summary->revenue)->toBe('356.0000')
        ->and($summary->unitsSold)->toBe(4)
        ->and($summary->salesCount)->toBe(1)
        ->and($summary->averageSale)->toBe('356.0000')
        ->and($summary->productsSoldCount)->toBe(1);
});

// --- the 60-row fixture of the plan, per period (days ago relative to today = 2026-10-08) -------------------------

it('summarizes the fixture per period (Expected table of the plan)', function (?int $maxDaysAgo, string $revenue, int $units, int $count, string $average, int $products) {
    $summary = salesSummarize(salesFixture($maxDaysAgo));

    expect($summary->revenue)->toBe($revenue)
        ->and($summary->unitsSold)->toBe($units)
        ->and($summary->salesCount)->toBe($count)
        ->and($summary->averageSale)->toBe($average)
        ->and($summary->productsSoldCount)->toBe($products);
})->with([
    'last 7 days (days ago <= 6)' => [6, '3001.0000', 57, 8, '375.1250', 6],
    'last 30 days (days ago <= 29)' => [29, '16850.0000', 296, 48, '351.0417', 6],
    'this month (days ago <= 7)' => [7, '3446.0000', 62, 9, '382.8889', 6],
    'this year (all 60 rows)' => [null, '23752.0000', 429, 60, '395.8667', 6],
]);

it('fixture: the last-30-days window holds sale ids 13 to 60', function () {
    expect(count(salesFixture(29)))->toBe(48)
        ->and(count(salesFixture(6)))->toBe(8)
        ->and(count(salesFixture(7)))->toBe(9)
        ->and(count(salesFixture()))->toBe(60);
});

it('does not depend on the order of the sales', function () {
    $sales = salesFixture(29);

    expect(salesSummarize(array_reverse($sales)))->toEqual(salesSummarize($sales));
});

// --- aggregation and rounding -------------------------------------------------------------------------------------

it('counts a product once however many sales it has', function () {
    $summary = salesSummarize([
        new SaleLine(1, 1, '10'),
        new SaleLine(2, 1, '10'),
        new SaleLine(1, 2, '10'),
        new SaleLine(1, 3, '10'),
        new SaleLine(2, 4, '10'),
    ]);

    expect($summary->productsSoldCount)->toBe(2)
        ->and($summary->salesCount)->toBe(5)
        ->and($summary->unitsSold)->toBe(11);
});

it('counts free sales: they add a sale, units and a product but no revenue', function () {
    $summary = salesSummarize([new SaleLine(1, 3, '0'), new SaleLine(2, 2, '0.0000')]);

    expect($summary->revenue)->toBe('0.0000')
        ->and($summary->unitsSold)->toBe(5)
        ->and($summary->salesCount)->toBe(2)
        ->and($summary->averageSale)->toBe('0.0000')
        ->and($summary->productsSoldCount)->toBe(2);
});

it('rounds the average half-up at scale 4', function (array $prices, string $revenue, string $average) {
    $summary = salesSummarize(array_map(fn (string $price): SaleLine => new SaleLine(1, 1, $price), $prices));

    expect($summary->revenue)->toBe($revenue)
        ->and($summary->averageSale)->toBe($average);
})->with([
    'exact' => [['10', '20'], '30.0000', '15.0000'],
    'repeating, rounds down' => [['10', '10', '10.0001'], '30.0001', '10.0000'],
    'repeating, rounds up' => [['10', '10', '10.0002'], '30.0002', '10.0001'],
    // 0.0001 / 2 = 0.00005: a tie, rounds up (HalfEven would give 0.0000).
    'tie rounds up' => [['0.0001', '0'], '0.0001', '0.0001'],
    // 0.0003 / 2 = 0.00015: a tie, rounds up (HalfEven would also give 0.0002).
    'tie rounds up, odd digit' => [['0.0003', '0'], '0.0003', '0.0002'],
]);

it('sums exactly and rounds once, on output', function () {
    // Two sales of 0.00005 each: exact revenue 0.00010 -> "0.0001" (summing rounded totals would give 0.0002).
    $summary = salesSummarize([new SaleLine(1, 1, '0.00005'), new SaleLine(1, 1, '0.00005')]);

    expect($summary->revenue)->toBe('0.0001')
        ->and($summary->averageSale)->toBe('0.0001');
});

it('has no float artifacts', function () {
    // 10 sales of 0.1 sum to 1.0 exactly (floats: 0.9999999999999999).
    $summary = salesSummarize(array_fill(0, 10, new SaleLine(1, 1, '0.1')));

    expect($summary->revenue)->toBe('1.0000')
        ->and($summary->averageSale)->toBe('0.1000');
});

it('handles the largest sales without precision loss', function () {
    $summary = salesSummarize([
        new SaleLine(1, 1_000_000, '99999999.9999'),
        new SaleLine(2, 1_000_000, '99999999.9999'),
    ]);

    expect($summary->revenue)->toBe('199999999999800.0000')
        ->and($summary->unitsSold)->toBe(2_000_000)
        ->and($summary->averageSale)->toBe('99999999999900.0000');
});

it('rejects an input that is not made of SaleLine objects', function (mixed $notASale) {
    $call = fn () => (new SalesCalculator)->summarize([new SaleLine(1, 1, '10'), $notASale]);

    expect($call)->toThrow(InvalidArgumentException::class, SaleLine::class);
})->with([
    'array' => [['productId' => 1, 'quantity' => 1, 'unitPrice' => '10']],
    'stdClass' => [new stdClass],
    'null' => [null],
    'string' => ['sale'],
]);
