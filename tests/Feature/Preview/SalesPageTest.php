<?php

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Sales log on the preview fixture (ADR-0006, S-13c)
|--------------------------------------------------------------------------
|
| Time frozen at 2026-10-08. The 60 fixture sales are dated relative to today, the unit price of a sale is the selling price
| of its product, and every total comes from SalesCalculator. The expected values are the plan's "Expected" tables plus
| sums worked out by hand from the fixture rows (noted next to each).
*/

beforeEach(fn () => $this->user = preparePreview($this));

function salesUrl(array $query = []): string
{
    return route('sales.index', $query);
}

/** Newest first: in the fixture a higher id is never older, so that is the id descending. */
function saleIdsDescending(int $from, int $to): array
{
    return range($from, $to);
}

it('sends the SalesIndexProps contract', function () {
    actingAs($this->user)->get(salesUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sales/index')
            ->hasAll(['sales', 'filters', 'totals', 'periods', 'products', 'record'])
            ->has('sales.data', 25)
            ->has('sales.data.0', fn (Assert $sale) => $sale->hasAll(['id', 'product', 'quantity', 'unit_price', 'total', 'sold_on', 'note']))
            ->has('sales.data.0.product', fn (Assert $product) => $product->hasAll(['id', 'sku', 'name', 'photo_url']))
            ->has('sales.links', fn (Assert $links) => $links->hasAll(['first', 'last', 'prev', 'next']))
            ->has('sales.meta', fn (Assert $meta) => $meta
                ->hasAll(['current_page', 'from', 'last_page', 'links', 'path', 'per_page', 'to', 'total']))
            ->has('totals', fn (Assert $totals) => $totals->hasAll(['revenue', 'units_sold', 'sales_count', 'average_sale']))
            ->where('filters', ['search' => null, 'product_id' => null, 'period' => 'last_30_days'])
            ->where('periods', [
                ['value' => 'last_7_days', 'label' => 'Last 7 days'],
                ['value' => 'last_30_days', 'label' => 'Last 30 days'],
                ['value' => 'this_month', 'label' => 'This month'],
                ['value' => 'this_year', 'label' => 'This year'],
            ])
            ->where('record', null));
});

describe('list', function () {
    it('starts with the newest sales, which are the dashboard\'s recent sales', function () {
        $response = actingAs($this->user)->get(salesUrl());

        expect(array_slice(pageProp($response, 'sales.data.*.id'), 0, 5))->toBe([60, 59, 58, 57, 56]);
    });

    it('lists the last 30 days newest first, 25 per page', function () {
        $response = actingAs($this->user)->get(salesUrl());

        // Sales 13 to 60 are the 48 of the last 30 days.
        expect(pageProp($response, 'sales.data.*.id'))->toBe(saleIdsDescending(60, 36))
            ->and(pageProp($response, 'sales.meta.total'))->toBe(48)
            ->and(pageProp($response, 'sales.meta.last_page'))->toBe(2)
            ->and(pageProp($response, 'sales.meta.per_page'))->toBe(25)
            ->and(pageProp($response, 'sales.meta.from'))->toBe(1)
            ->and(pageProp($response, 'sales.meta.to'))->toBe(25);
    });

    it('serves the second page with the rest of the sales', function () {
        $response = actingAs($this->user)->get(salesUrl(['page' => 2]));

        expect(pageProp($response, 'sales.data.*.id'))->toBe(saleIdsDescending(35, 13))
            ->and(pageProp($response, 'sales.meta.current_page'))->toBe(2)
            ->and(pageProp($response, 'sales.meta.from'))->toBe(26)
            ->and(pageProp($response, 'sales.meta.to'))->toBe(48)
            ->and(pageProp($response, 'sales.links.next'))->toBeNull()
            ->and(pageProp($response, 'sales.links.prev'))->toContain('page=1');
    });

    it('answers an empty page beyond the last one, with the totals of the whole filtered set', function () {
        $response = actingAs($this->user)->get(salesUrl(['page' => 9]));

        expect(pageProp($response, 'sales.data'))->toBe([])
            ->and(pageProp($response, 'sales.meta.total'))->toBe(48)
            ->and(pageProp($response, 'totals.sales_count'))->toBe(48);
    });

    it('keeps the filters in the pagination links but not the "record a sale" opener', function () {
        // The whole year (60 sales, 3 pages), searched for "o" (in every note and every product name).
        $response = actingAs($this->user)->get(salesUrl(['period' => 'this_year', 'search' => 'o', 'record' => '1', 'record_product_id' => '1']));

        $next = pageProp($response, 'sales.links.next');

        expect($next)->toBeString()->toContain('page=2')->toContain('period=this_year')->toContain('search=o')
            ->and($next)->not->toContain('record')
            ->and(pageProp($response, 'sales.meta.path'))->toBe(route('sales.index'));
    });

    it('sends each sale with its product, a server-side total and the date', function () {
        $response = actingAs($this->user)->get(salesUrl());

        expect(pageProp($response, 'sales.data.0'))->toBe([
            'id' => 60,
            'product' => ['id' => 5, 'sku' => 'PRD-JNS-005', 'name' => 'Denim Jeans 5-pocket', 'photo_url' => asset('mock/photos/prd-jns-005.svg')],
            'quantity' => 6,
            'unit_price' => '79.0000',
            'total' => '474.0000',
            'sold_on' => '2026-10-07',
            'note' => 'Boutique Lyon',
        ]);
    });

    it('sends a null note, never an empty string', function () {
        // Sale 19: 26 days ago, 8 T-shirts at 24, no note.
        $response = actingAs($this->user)->get(salesUrl(['page' => 2]));
        $sale = collect(pageProp($response, 'sales.data'))->firstWhere('id', 19);

        expect($sale)->toMatchArray(['quantity' => 8, 'unit_price' => '24.0000', 'total' => '192.0000', 'sold_on' => '2026-09-12', 'note' => null]);
    });
});

describe('totals', function () {
    it('computes the plan\'s expected totals for each period', function (string $period, int $count, int $units, string $revenue, string $average) {
        actingAs($this->user)->get(salesUrl(['period' => $period]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.period', $period)
                ->where('totals', [
                    'revenue' => $revenue,
                    'units_sold' => $units,
                    'sales_count' => $count,
                    'average_sale' => $average,
                ])
                ->where('sales.meta.total', $count));
    })->with([
        'last 7 days' => ['last_7_days', 8, 57, '3001.0000', '375.1250'],
        'last 30 days' => ['last_30_days', 48, 296, '16850.0000', '351.0417'],
        'this month' => ['this_month', 9, 62, '3446.0000', '382.8889'],
        'this year' => ['this_year', 60, 429, '23752.0000', '395.8667'],
    ]);

    it('lists the sales of each period', function (string $period, array $firstIds) {
        $response = actingAs($this->user)->get(salesUrl(['period' => $period]));

        expect(array_slice(pageProp($response, 'sales.data.*.id'), 0, count($firstIds)))->toBe($firstIds);
    })->with([
        'last 7 days' => ['last_7_days', [60, 59, 58, 57, 56, 55, 54, 53]],
        // The 1st to the 8th: the last 7 days plus sale 52 (7 days ago, the 1st).
        'this month' => ['this_month', [60, 59, 58, 57, 56, 55, 54, 53, 52]],
        'this year' => ['this_year', [60, 59, 58, 57, 56]],
    ]);

    it('has exactly the sales of its period, no more', function () {
        $sevenDays = actingAs($this->user)->get(salesUrl(['period' => 'last_7_days']));
        $month = actingAs($this->user)->get(salesUrl(['period' => 'this_month']));
        $year = actingAs($this->user)->get(salesUrl(['period' => 'this_year', 'page' => 3]));

        expect(pageProp($sevenDays, 'sales.data.*.id'))->toBe(saleIdsDescending(60, 53))
            ->and(pageProp($month, 'sales.data.*.id'))->toBe(saleIdsDescending(60, 52))
            // Page 3 of the 60 sales of the year: the oldest ten.
            ->and(pageProp($year, 'sales.data.*.id'))->toBe(saleIdsDescending(10, 1));
    });
});

describe('filters', function () {
    it('searches the product name, SKU and note case-insensitively', function (string $search, array $ids, int $count, int $units, string $revenue, string $average) {
        $response = actingAs($this->user)->get(salesUrl(['search' => $search]));

        expect(pageProp($response, 'sales.data.*.id'))->toBe($ids)
            ->and(pageProp($response, 'totals'))->toBe([
                'revenue' => $revenue,
                'units_sold' => $units,
                'sales_count' => $count,
                'average_sale' => $average,
            ])
            ->and(pageProp($response, 'filters.search'))->toBe(trim($search));
    })->with([
        // Notes of the last 30 days: ids 15, 23, 29, 32, 38, 41, 43, 53 and 55 are "Wholesale order" (77 units, 3844 in all).
        'a note, other case' => ['WHOLESALE', [55, 53, 43, 41, 38, 32, 29, 23, 15], 9, 77, '3844.0000', '427.1111'],
        'a note, trimmed' => ['  wholesale  ', [55, 53, 43, 41, 38, 32, 29, 23, 15], 9, 77, '3844.0000', '427.1111'],
        // The Leather Tote: sales 17, 26, 34, 42, 49 and 58 (the 12 units of the plan's top 5).
        'a SKU' => ['prd-tot', [58, 49, 42, 34, 26, 17], 6, 12, '2988.0000', '498.0000'],
        'a product name' => ['leather tote', [58, 49, 42, 34, 26, 17], 6, 12, '2988.0000', '498.0000'],
    ]);

    it('matches a name or a SKU as well as a note', function () {
        // "denim" is in the names of the Jacket (1) and the Jeans (5), and in no note.
        $response = actingAs($this->user)->get(salesUrl(['search' => 'denim']));

        expect(array_unique(pageProp($response, 'sales.data.*.product.id')))->toEqualCanonicalizing([1, 5])
            ->and(pageProp($response, 'totals.units_sold'))->toBe(38 + 64);
    });

    it('filters by product', function () {
        $response = actingAs($this->user)->get(salesUrl(['product_id' => 1]));

        // The Denim Jacket sold 38 units in 8 sales over the last 30 days (plan, top 5).
        expect(pageProp($response, 'sales.data.*.id'))->toBe([57, 52, 43, 40, 38, 30, 24, 16])
            ->and(pageProp($response, 'totals'))->toBe([
                'revenue' => '3382.0000',
                'units_sold' => 38,
                'sales_count' => 8,
                'average_sale' => '422.7500',
            ])
            ->and(pageProp($response, 'filters.product_id'))->toBe(1);
    });

    it('combines the product, the period and the search', function () {
        $year = actingAs($this->user)->get(salesUrl(['product_id' => 1, 'period' => 'this_year']));
        $combined = actingAs($this->user)->get(salesUrl(['product_id' => 1, 'period' => 'this_year', 'search' => 'showroom']));

        expect(pageProp($year, 'sales.data.*.id'))->toBe([57, 52, 43, 40, 38, 30, 24, 16, 9, 3])
            ->and(pageProp($year, 'totals.units_sold'))->toBe(51)
            ->and(pageProp($year, 'totals.revenue'))->toBe('4539.0000')
            // The Jacket's "Showroom" sales: 3 (6 units, 121 days ago), 24 (6) and 52 (5).
            ->and(pageProp($combined, 'sales.data.*.id'))->toBe([52, 24, 3])
            ->and(pageProp($combined, 'totals.units_sold'))->toBe(17)
            ->and(pageProp($combined, 'filters'))->toBe(['search' => 'showroom', 'product_id' => 1, 'period' => 'this_year']);
    });

    it('answers an empty list and zero totals when nothing matches', function (array $query) {
        $response = actingAs($this->user)->get(salesUrl($query));

        expect(pageProp($response, 'sales.data'))->toBe([])
            ->and(pageProp($response, 'sales.meta.total'))->toBe(0)
            ->and(pageProp($response, 'sales.meta.from'))->toBeNull()
            ->and(pageProp($response, 'totals'))->toBe(['revenue' => '0.0000', 'units_sold' => 0, 'sales_count' => 0, 'average_sale' => null]);
    })->with([
        'no match' => [['search' => 'zzz']],
        'a product without sales' => [['product_id' => 6]],
        'an unknown product' => [['product_id' => 99]],
        'the draft product in the last 7 days' => [['product_id' => 6, 'period' => 'last_7_days']],
    ]);

    it('echoes a trimmed search and drops a blank one', function () {
        $blank = actingAs($this->user)->get(salesUrl(['search' => '   ']));

        expect(pageProp($blank, 'filters.search'))->toBeNull()
            ->and(pageProp($blank, 'sales.meta.total'))->toBe(48);
    });

    it('rejects invalid filters', function (array $query, string $field) {
        actingAs($this->user)->get(salesUrl($query))->assertSessionHasErrors($field);
    })->with([
        'unknown period' => [['period' => 'last_year'], 'period'],
        'array search' => [['search' => ['a']], 'search'],
        'long search' => [['search' => str_repeat('a', 101)], 'search'],
        'text product' => [['product_id' => 'abc'], 'product_id'],
        'product zero' => [['product_id' => '0'], 'product_id'],
        'page zero' => [['page' => '0'], 'page'],
        'page text' => [['page' => 'abc'], 'page'],
        'record not a boolean' => [['record' => 'maybe'], 'record'],
        'record product text' => [['record' => '1', 'record_product_id' => 'abc'], 'record_product_id'],
    ]);
});

describe('record a sale', function () {
    it('opens nothing by default', function (array $query) {
        actingAs($this->user)->get(salesUrl($query))
            ->assertInertia(fn (Assert $page) => $page->where('record', null));
    })->with([
        'no query' => [[]],
        'record off' => [['record' => '0']],
        'only a product' => [['record_product_id' => '1']],
    ]);

    it('opens the form, with the product when it is an active one', function (array $query, ?int $productId) {
        actingAs($this->user)->get(salesUrl($query))
            ->assertInertia(fn (Assert $page) => $page->where('record', ['product_id' => $productId]));
    })->with([
        'no product' => [['record' => '1'], null],
        'an active product' => [['record' => '1', 'record_product_id' => '1'], 1],
        'the last product' => [['record' => '1', 'record_product_id' => '7'], 7],
        'a draft product' => [['record' => '1', 'record_product_id' => '6'], null],
        'an unknown product' => [['record' => '1', 'record_product_id' => '99'], null],
        'another active product' => [['record' => '1', 'record_product_id' => '3'], 3],
    ]);

    it('does not narrow the list or the totals', function () {
        actingAs($this->user)->get(salesUrl(['record' => '1', 'record_product_id' => '1']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('sales.meta.total', 48)
                ->where('totals.sales_count', 48)
                ->where('filters', ['search' => null, 'product_id' => null, 'period' => 'last_30_days']));
    });
});

describe('product options', function () {
    it('lists every product by name with its status and selling price', function () {
        $response = actingAs($this->user)->get(salesUrl());

        expect(pageProp($response, 'products.*.name'))->toBe([
            'Canvas Chore Jacket', 'Canvas Low Sneaker', 'Classic White T-shirt', 'Denim Jacket',
            'Denim Jeans 5-pocket', 'Leather Belt', 'Leather Tote',
        ])->and(pageProp($response, 'products.*.status'))->toBe(['draft', 'active', 'active', 'active', 'active', 'active', 'active'])
            ->and(pageProp($response, 'products.*.selling_price'))->toBe([null, '69.0000', '24.0000', '89.0000', '79.0000', '45.0000', '249.0000'])
            ->and(pageProp($response, 'products.3'))->toBe([
                'id' => 1,
                'sku' => 'PRD-JKT-001',
                'name' => 'Denim Jacket',
                'photo_url' => asset('mock/photos/prd-jkt-001.svg'),
                'status' => 'active',
                'selling_price' => '89.0000',
            ])
            ->and(pageProp($response, 'products.0.photo_url'))->toBeNull();
    });
});

it('answers within a bounded number of queries', function () {
    DB::enableQueryLog();
    actingAs($this->user)->get(salesUrl(['period' => 'this_year', 'page' => 2]))->assertOk();

    // Only the reference lists of a full load: the sales and the products come from the fixture.
    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(REFERENCE_QUERIES);
});
