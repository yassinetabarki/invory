<?php

use App\Domain\Sales\ProductSales;
use App\Domain\Sales\SaleLine;
use App\Domain\Sales\SalesCalculator;

require_once __DIR__.'/SalesTestSupport.php';

// Rules: invory-costing-domain skill, "Sales" (top products).
//   per product: units and revenue; ranked by the chosen metric desc, then the other metric desc, then the lowest product id;
//   percent_of_top = metric / metric of the first ranked * 100 (scale 2, HalfUp); every percent "0.00" when the first metric is 0.

// --- the fixture, last 30 days (Expected tables of the plan) -----------------------------------------------------

it('ranks the top 5 by units with the exact percent of top', function () {
    // [product id, units, revenue, percent of top]: T-shirt, Jeans, Denim Jacket, Belt, Tote.
    expect(salesTop(salesFixture(29), 'units'))->toBe([
        [2, 142, '3408.0000', '100.00'],
        [5, 64, '5056.0000', '45.07'],
        [1, 38, '3382.0000', '26.76'],
        [7, 31, '1395.0000', '21.83'],
        [4, 12, '2988.0000', '8.45'],
    ]);
});

it('ranks the top 5 by revenue with the exact percent of top', function () {
    // Jeans, T-shirt, Denim Jacket, Tote, Belt.
    expect(salesTop(salesFixture(29), 'revenue'))->toBe([
        [5, 64, '5056.0000', '100.00'],
        [2, 142, '3408.0000', '67.41'],
        [1, 38, '3382.0000', '66.89'],
        [4, 12, '2988.0000', '59.10'],
        [7, 31, '1395.0000', '27.59'],
    ]);
});

it('limits to 5 by default, although 6 products were sold', function () {
    expect(salesTop(salesFixture(29), 'units'))->toHaveCount(5)
        ->and(salesTop(salesFixture(29), 'revenue'))->toHaveCount(5);
});

it('respects the limit', function (int $limit, int $expected) {
    $ranked = salesTop(salesFixture(29), 'units', $limit);

    expect($ranked)->toHaveCount($expected)
        // The Sneaker (9 units, 621.0000 revenue, id 3) is the 6th product sold.
        ->and(array_column($ranked, 0))->toBe(array_slice([2, 5, 1, 7, 4, 3], 0, $expected));
})->with([
    'one' => [1, 1],
    'three' => [3, 3],
    'all six' => [6, 6],
    'more than there are products' => [50, 6],
]);

it('puts the product with the smallest metric last, with its percent', function () {
    $ranked = salesTop(salesFixture(29), 'units', 6);

    expect(end($ranked))->toBe([3, 9, '621.0000', '6.34']);
});

it('ranks the whole year of the fixture (6 products, totals of the plan: 429 units, 23752.0000)', function () {
    // Per-product totals of the 60 rows, computed independently from the plan's rows.
    expect(salesTop(salesFixture(), 'units', 6))->toBe([
        [2, 215, '5160.0000', '100.00'],
        [5, 85, '6715.0000', '39.53'],
        [1, 51, '4539.0000', '23.72'],
        [7, 46, '2070.0000', '21.40'],
        [4, 17, '4233.0000', '7.91'],
        [3, 15, '1035.0000', '6.98'],
    ]);

    expect(array_column(salesTop(salesFixture(), 'revenue', 6), 0))->toBe([5, 2, 1, 4, 7, 3]);
});

it('returns ProductSales objects with scale-4 revenue and scale-2 percent', function () {
    $ranked = (new SalesCalculator)->topProducts(salesFixture(29), 'units', 1);

    expect($ranked)->toBeArray()->toHaveCount(1)
        ->and(array_is_list($ranked))->toBeTrue()
        ->and($ranked[0])->toBeInstanceOf(ProductSales::class)
        ->and($ranked[0]->productId)->toBe(2)
        ->and($ranked[0]->unitsSold)->toBe(142)
        ->and($ranked[0]->revenue)->toBe('3408.0000')
        ->and($ranked[0]->percentOfTop)->toBe('100.00');
});

it('returns nothing when there are no sales', function (string $by) {
    expect(salesTop([], $by))->toBe([]);
})->with(['units', 'revenue']);

it('gives 100.00 to a single product', function (string $by) {
    expect(salesTop([new SaleLine(8, 3, '10.5000'), new SaleLine(8, 2, '10.5000')], $by))
        ->toBe([[8, 5, '52.5000', '100.00']]);
})->with(['units', 'revenue']);

it('does not depend on the order of the sales', function (string $by) {
    $sales = salesFixture(29);

    expect(salesTop(array_reverse($sales), $by))->toBe(salesTop($sales, $by));
})->with(['units', 'revenue']);

// --- aggregation ---------------------------------------------------------------------------------------------------

it('aggregates the sales of a product, including at different prices', function () {
    $ranked = salesTop([
        new SaleLine(1, 2, '10.0000'),
        new SaleLine(2, 1, '50.0000'),
        new SaleLine(1, 3, '12.0000'),
    ], 'revenue');

    // Product 1: 5 units, 20 + 36 = 56 revenue. Product 2: 1 unit, 50 revenue.
    expect($ranked)->toBe([
        [1, 5, '56.0000', '100.00'],
        [2, 1, '50.0000', '89.29'],
    ]);
});

// --- tie-breaks ----------------------------------------------------------------------------------------------------

it('breaks a tie on units with the higher revenue', function () {
    $ranked = salesTop([
        new SaleLine(10, 5, '10'),
        new SaleLine(11, 5, '20'),
        new SaleLine(12, 9, '1'),
    ], 'units');

    // 12: 9 units; then 10 and 11 tie on 5 units: 11 has the higher revenue.
    expect(array_column($ranked, 0))->toBe([12, 11, 10]);
});

it('breaks a tie on revenue with the higher units', function () {
    $ranked = salesTop([
        new SaleLine(10, 5, '20'),   // revenue 100, units 5
        new SaleLine(11, 10, '10'),  // revenue 100, units 10
        new SaleLine(12, 1, '300'),  // revenue 300
    ], 'revenue');

    expect(array_column($ranked, 0))->toBe([12, 11, 10])
        ->and(array_column($ranked, 3))->toBe(['100.00', '33.33', '33.33']);
});

it('breaks a tie on both metrics with the lowest product id, whatever the input order', function (array $order) {
    $sales = [
        20 => new SaleLine(20, 4, '25'),
        21 => new SaleLine(21, 4, '25'),
        22 => new SaleLine(22, 4, '25'),
    ];

    foreach (['units', 'revenue'] as $by) {
        $ranked = salesTop(array_map(fn (int $id): SaleLine => $sales[$id], $order), $by);

        expect(array_column($ranked, 0))->toBe([20, 21, 22]);
    }
})->with([
    'ascending' => [[20, 21, 22]],
    'descending' => [[22, 21, 20]],
    'shuffled' => [[21, 22, 20]],
]);

it('applies the limit after the tie-breaks', function () {
    $sales = [
        new SaleLine(3, 4, '25'),
        new SaleLine(1, 4, '25'),
        new SaleLine(2, 4, '25'),
    ];

    expect(array_column(salesTop($sales, 'units', 2), 0))->toBe([1, 2]);
});

it('uses the ranking metric before anything else', function () {
    // Product 1 has the most units but little revenue; product 2 the opposite.
    $sales = [new SaleLine(1, 100, '1'), new SaleLine(2, 2, '500')];

    expect(array_column(salesTop($sales, 'units'), 0))->toBe([1, 2])
        ->and(array_column(salesTop($sales, 'revenue'), 0))->toBe([2, 1]);
});

// --- percent of top ------------------------------------------------------------------------------------------------

it('rounds the percent of top half-up at scale 2', function (string $by, int $topUnits, string $topPrice, int $units, string $price, string $percent) {
    $ranked = salesTop([new SaleLine(1, $topUnits, $topPrice), new SaleLine(2, $units, $price)], $by);

    expect($ranked[0][3])->toBe('100.00')
        ->and($ranked[1][3])->toBe($percent);
})->with([
    // 1 / 32 * 100 = 3.125 (a tie): HalfUp -> 3.13 (HalfEven would give 3.12)
    'units, tie rounds up' => ['units', 32, '1', 1, '1', '3.13'],
    // 1 / 16 * 100 = 6.25 exactly
    'units, exact' => ['units', 16, '1', 1, '1', '6.25'],
    // 1 / 3 * 100 = 33.333...
    'units, repeating down' => ['units', 3, '1', 1, '1', '33.33'],
    // 2 / 3 * 100 = 66.666...
    'units, repeating up' => ['units', 3, '1', 2, '1', '66.67'],
    // revenue 32.0000 vs 1.0000
    'revenue, tie rounds up' => ['revenue', 1, '32', 1, '1', '3.13'],
    // revenue 0.0003 vs 0.0001 (units tie, the metric decides): 33.33
    'revenue, tiny amounts' => ['revenue', 1, '0.0003', 1, '0.0001', '33.33'],
]);

it('gives 0.00 to every product when the top metric is zero', function () {
    // Only free sales: revenue is zero everywhere, so the bars are all empty.
    $sales = [new SaleLine(3, 5, '0'), new SaleLine(1, 5, '0.0000'), new SaleLine(2, 9, '0')];

    expect(salesTop($sales, 'revenue'))->toBe([
        [2, 9, '0.0000', '0.00'],
        [1, 5, '0.0000', '0.00'],
        [3, 5, '0.0000', '0.00'],
    ]);
});

it('keeps real percents by units when the sales are free', function () {
    // Units are never zero (quantity >= 1), so the zero rule only concerns revenue.
    $sales = [new SaleLine(1, 4, '0'), new SaleLine(2, 2, '0')];

    expect(salesTop($sales, 'units'))->toBe([
        [1, 4, '0.0000', '100.00'],
        [2, 2, '0.0000', '50.00'],
    ]);
});

it('gives a zero-revenue product a 0.00 percent below a paying top product', function () {
    $sales = [new SaleLine(1, 1, '10'), new SaleLine(2, 50, '0')];

    expect(salesTop($sales, 'revenue'))->toBe([
        [1, 1, '10.0000', '100.00'],
        [2, 50, '0.0000', '0.00'],
    ]);
});

it('handles the largest sales without precision loss', function () {
    $ranked = salesTop([
        new SaleLine(1, 1_000_000, '99999999.9999'),
        new SaleLine(2, 1, '0.0001'),
    ], 'revenue');

    expect($ranked[0][2])->toBe('99999999999900.0000')
        ->and($ranked[1][3])->toBe('0.00');
});

// --- invalid input -------------------------------------------------------------------------------------------------

it('rejects an unknown ranking metric', function (string $by) {
    expect(fn () => (new SalesCalculator)->topProducts(salesFixture(29), $by))
        ->toThrow(InvalidArgumentException::class, '"units" or "revenue"');
})->with([
    'unknown' => ['profit'],
    'empty' => [''],
    'wrong case' => ['Units'],
    'singular' => ['unit'],
    'padded' => ['units '],
]);

it('rejects an unknown ranking metric even without sales', function () {
    expect(fn () => (new SalesCalculator)->topProducts([], 'profit'))
        ->toThrow(InvalidArgumentException::class, '"units" or "revenue"');
});

it('rejects a limit below one', function (int $limit) {
    expect(fn () => (new SalesCalculator)->topProducts(salesFixture(29), 'units', $limit))
        ->toThrow(InvalidArgumentException::class, 'limit must be at least 1');
})->with([
    'zero' => [0],
    'negative' => [-5],
]);

it('rejects an input that is not made of SaleLine objects', function (mixed $notASale) {
    $call = fn () => (new SalesCalculator)->topProducts([new SaleLine(1, 1, '10'), $notASale], 'units');

    expect($call)->toThrow(InvalidArgumentException::class, SaleLine::class);
})->with([
    'array' => [['productId' => 1, 'quantity' => 1, 'unitPrice' => '10']],
    'stdClass' => [new stdClass],
    'null' => [null],
    'string' => ['sale'],
]);
