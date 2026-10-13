<?php

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The sales log on real data (S-14-c)
|--------------------------------------------------------------------------
|
| Time frozen at 2026-10-08 with the demo seed (12 materials, 7 products, 60 sales dated relative to today). The expected values
| are the costing skill's worked example plus sums worked out by hand from the demo rows. A sale is named by its number in the
| seeder (1 to 60, oldest first): `demoSaleId()` gives its database id. Every total comes from SalesCalculator.
*/

/** The most queries any page may run (the plan's budget). */
const QUERY_BUDGET_SALES = 10;

beforeEach(fn () => $this->user = prepareDemo($this));

/**
 * @param  array<string, mixed>  $query
 */
function salesLog(array $query = []): TestResponse
{
    return actingAs(test()->user)->get(route('sales.index', $query));
}

it('shows the last 30 days with the totals of the costing skill\'s worked example', function (string $period, int $count, int $units, string $revenue, string $average) {
    $response = salesLog($period === 'last_30_days' ? [] : ['period' => $period]);

    expect(pageProp($response, 'totals'))->toBe([
        'revenue' => $revenue,
        'units_sold' => $units,
        'sales_count' => $count,
        'average_sale' => $average,
    ])
        ->and(pageProp($response, 'sales.meta.total'))->toBe($count)
        ->and(pageProp($response, 'filters.period'))->toBe($period);
})->with([
    'last 7 days' => ['last_7_days', 8, 57, '3001.0000', '375.1250'],
    'last 30 days (the default)' => ['last_30_days', 48, 296, '16850.0000', '351.0417'],
    'this month' => ['this_month', 9, 62, '3446.0000', '382.8889'],
    'this year' => ['this_year', 60, 429, '23752.0000', '395.8667'],
]);

it('narrows the log and the totals together, newest first', function (array $query, array $numbers, int $count, int $units, string $revenue, ?string $average) {
    // A `product_id` given as a SKU stands for that demo product.
    if (isset($query['product_id'])) {
        $query['product_id'] = demoProduct($query['product_id'])->id;
    }

    $response = salesLog($query);

    // Sale numbers, newest first: the first 25 are the first page.
    expect(pageProp($response, 'sales.data.*.id'))->toBe(array_map(demoSaleId(...), $numbers))
        ->and(pageProp($response, 'sales.meta.total'))->toBe($count)
        ->and(pageProp($response, 'totals'))->toBe([
            'revenue' => $revenue,
            'units_sold' => $units,
            'sales_count' => $count,
            'average_sale' => $average,
        ]);
})->with([
    // Notes of the last 30 days: 15, 23, 29, 32, 38, 41, 43, 53 and 55 are "Wholesale order" (77 units, 3844 in all).
    'a note, other case' => [['search' => 'WHOLESALE'], [55, 53, 43, 41, 38, 32, 29, 23, 15], 9, 77, '3844.0000', '427.1111'],
    'a note, trimmed' => [['search' => '  wholesale  '], [55, 53, 43, 41, 38, 32, 29, 23, 15], 9, 77, '3844.0000', '427.1111'],
    // The Leather Tote: sales 17, 26, 34, 42, 49 and 58 (the 12 units of the costing skill's top 5).
    'a SKU' => [['search' => 'prd-tot'], [58, 49, 42, 34, 26, 17], 6, 12, '2988.0000', '498.0000'],
    'a product name' => [['search' => 'leather tote'], [58, 49, 42, 34, 26, 17], 6, 12, '2988.0000', '498.0000'],
    // The Denim Jacket sold 38 units in 8 sales over the last 30 days.
    'a product' => [['product_id' => 'PRD-JKT-001'], [57, 52, 43, 40, 38, 30, 24, 16], 8, 38, '3382.0000', '422.7500'],
    'the last 7 days' => [['period' => 'last_7_days'], [60, 59, 58, 57, 56, 55, 54, 53], 8, 57, '3001.0000', '375.1250'],
    // The 1st to the 8th: the last 7 days plus sale 52 (7 days ago, the 1st).
    'this month' => [['period' => 'this_month'], [60, 59, 58, 57, 56, 55, 54, 53, 52], 9, 62, '3446.0000', '382.8889'],
    // The Jacket's "Showroom" sales: 3 (6 units, 121 days ago), 24 (6) and 52 (5).
    'product, period and search together' => [['product_id' => 'PRD-JKT-001', 'period' => 'this_year', 'search' => 'showroom'], [52, 24, 3], 3, 17, '1513.0000', '504.3333'],
    'nothing matches' => [['search' => 'zzz'], [], 0, 0, '0.0000', null],
]);

it('sends the SalesIndexProps contract, 25 sales to a page, and keeps the filters (not the record opener) in the links', function () {
    demoProduct('PRD-JNS-005')->forceFill(['photo_path' => 'products/jeans.png'])->save();

    $response = salesLog();

    expect(pageProp($response, 'sales.data.0'))->toBe([
        'id' => demoSaleId(60),
        'product' => ['id' => pid(5), 'sku' => 'PRD-JNS-005', 'name' => 'Denim Jeans 5-pocket', 'photo_url' => Storage::disk('public')->url('products/jeans.png')],
        'quantity' => 6,
        'unit_price' => '79.0000',
        'total' => '474.0000',
        'sold_on' => '2026-10-07',
        'note' => 'Boutique Lyon',
    ])
        ->and(pageProp($response, 'sales.data'))->toHaveCount(25)
        ->and(pageProp($response, 'sales.meta'))->toMatchArray(['total' => 48, 'last_page' => 2, 'per_page' => 25, 'from' => 1, 'to' => 25])
        // A sale without a note sends null, never an empty string (sale 19: 8 T-shirts, no note).
        ->and(pageProp(salesLog(['page' => 2]), 'sales.data.*.note'))->toContain(null);

    $response->assertInertia(fn (Assert $page) => $page
        ->component('sales/index')
        ->hasAll(['sales', 'filters', 'totals', 'periods', 'products', 'record'])
        ->where('filters', ['search' => null, 'product_id' => null, 'period' => 'last_30_days'])
        ->where('record', null)
        ->has('periods', 4));

    $next = pageProp(salesLog(['period' => 'this_year', 'search' => 'o', 'record' => '1', 'record_product_id' => (string) pid(1)]), 'sales.links.next');

    expect($next)->toContain('page=2')->toContain('period=this_year')->toContain('search=o')->not->toContain('record');
});

it('keeps the sales of a soft-deleted product in the log and the totals, but not in the product options (D-3)', function () {
    // The Tote has 8 sales over the year; deleting the product does not delete them.
    demoProduct('PRD-TOT-004')->delete();

    $response = salesLog(['period' => 'this_year']);

    expect(pageProp($response, 'sales.meta.total'))->toBe(60)
        ->and(pageProp($response, 'totals.sales_count'))->toBe(60)
        ->and(pageProp($response, 'totals.units_sold'))->toBe(429)
        ->and(pageProp($response, 'sales.data.*.product.sku'))->toContain('PRD-TOT-004')
        ->and(pageProp($response, 'products.*.sku'))->not->toContain('PRD-TOT-004')
        // Searching still finds them by the deleted product's name.
        ->and(pageProp(salesLog(['period' => 'this_year', 'search' => 'leather tote']), 'sales.meta.total'))->toBe(8);
});

it('offers every non-deleted product, and pre-selects a product to sell only when it is active', function () {
    demoProduct('PRD-JKT-001')->forceFill(['photo_path' => 'products/jacket.jpg'])->save();

    $options = salesLog();

    expect(pageProp($options, 'products.*.name'))->toBe([
        'Canvas Chore Jacket', 'Canvas Low Sneaker', 'Classic White T-shirt', 'Denim Jacket',
        'Denim Jeans 5-pocket', 'Leather Belt', 'Leather Tote',
    ])
        ->and(pageProp($options, 'products.*.status'))->toBe(['draft', 'active', 'active', 'active', 'active', 'active', 'active'])
        ->and(pageProp($options, 'products.3'))->toBe([
            'id' => pid(1), 'sku' => 'PRD-JKT-001', 'name' => 'Denim Jacket', 'photo_url' => Storage::disk('public')->url('products/jacket.jpg'),
            'status' => 'active', 'selling_price' => '89.0000',
        ]);

    $beltId = pid(7);
    demoProduct('PRD-BLT-007')->delete();

    foreach ([
        'no product' => [['record' => '1'], null],
        'an active product' => [['record' => '1', 'record_product_id' => (string) pid(1)], pid(1)],
        'a draft product' => [['record' => '1', 'record_product_id' => (string) pid(6)], null],
        'a deleted product' => [['record' => '1', 'record_product_id' => (string) $beltId], null],
        'an unknown product' => [['record' => '1', 'record_product_id' => (string) NO_SUCH_ID], null],
    ] as [$query, $expected]) {
        expect(pageProp(salesLog($query), 'record'))->toBe(['product_id' => $expected]);
    }

    expect(pageProp(salesLog(['record_product_id' => (string) pid(1)]), 'record'))->toBeNull();
});

it('rejects invalid filters', function (array $query, string $field) {
    salesLog($query)->assertSessionHasErrors($field);
})->with([
    'unknown period' => [['period' => 'last_year'], 'period'],
    'array search' => [['search' => ['a']], 'search'],
    'long search' => [['search' => str_repeat('a', 101)], 'search'],
    'text product' => [['product_id' => 'abc'], 'product_id'],
    'page zero' => [['page' => '0'], 'page'],
    'record not a boolean' => [['record' => 'maybe'], 'record'],
]);

it('runs the same queries however many sales there are', function () {
    Sale::query()->delete();
    $product = Product::factory()->create();
    Sale::factory()->count(5)->for($product)->create();

    $few = queryLog(fn () => salesLog(['period' => 'this_year', 'search' => 'PRD'])->assertOk());

    Sale::factory()->count(55)->for($product)->create();

    $many = queryLog(fn () => salesLog(['period' => 'this_year', 'search' => 'PRD'])->assertOk());

    expect(count($few))->toBeLessThanOrEqual(QUERY_BUDGET_SALES, implode(PHP_EOL, $few))
        ->and(count($many))->toBe(count($few), implode(PHP_EOL, $many));

    // And the page itself stays inside the budget with a full page of 25 rows.
    expect(fn () => salesLog()->assertOk())->toRunAtMostQueries(QUERY_BUDGET_SALES);
});
