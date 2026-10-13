<?php

use App\Models\Material;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
|--------------------------------------------------------------------------
| Dashboard on real sales and products (S-06-BE; the figures are those of the UI-first preview)
|--------------------------------------------------------------------------
|
| Time frozen at 2026-10-08, DemoCatalogSeeder + DemoSalesSeeder run (12 materials, 7 products, 60 sales). Every number is
| computed by the production code (SalesCalculator through SalesReport, ProductCostingService through ProductCatalog,
| InventorySummary): the "Expected" figures of docs/plans/phase-2-ui-first-preview.md still hold now that they come from tables.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/** A policy that refuses the dashboard's `viewAny`. */
class DenyDashboardViewPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }
}

function dashboardUrl(array $query = []): string
{
    return route('dashboard', $query);
}

/** A material reference as the contract sends it. */
function materialRef(string $sku): array
{
    $material = demoMaterial($sku);

    return ['id' => $material->id, 'sku' => $sku, 'name' => $material->name, 'unit' => $material->unit->code];
}

/** A product reference as the contract sends it (the demo products have no photo, D-6). `$number` is the plan's number of the demo product. */
function productRef(int $number, string $sku, string $name): array
{
    return ['id' => pid($number), 'sku' => $sku, 'name' => $name, 'photo_url' => null];
}

it('sends every figure of the dashboard for the last 30 days, from the database', function () {
    $response = actingAs($this->user)->get(dashboardUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->hasAll(['period', 'periods'])
            ->where('period', 'last_30_days')
            ->where('periods', [
                ['value' => 'last_7_days', 'label' => 'Last 7 days'],
                ['value' => 'last_30_days', 'label' => 'Last 30 days'],
                ['value' => 'this_month', 'label' => 'This month'],
                ['value' => 'this_year', 'label' => 'This year'],
            ]));

    // All deferred groups (primary + default) are merged into $loaded.
    $loaded = loadedPageProps($response);

    // Key order matches DashboardController: periodSummary() then inventory->summarize() then catalog counts.
    expect($loaded['stats'])->toBe([
        'revenue' => '16850.0000',
        'units_sold' => 296,
        'sales_count' => 48,
        'products_sold_count' => 6,
        'materials_count' => 12,
        'inventory_value' => '10544.3000',
        'needs_restock_count' => 4,
        'low_stock_count' => 3,
        'out_of_stock_count' => 1,
        'unproducible_count' => 1,
        'products_count' => 7,
    ]);

    // Six products sold: the sixth (the Sneaker, 9 units and 621.0000) is out of both top fives.
    expect(data_get($loaded, 'top_products.by_units'))->toBe([
        ['product' => productRef(2, 'PRD-TSH-002', 'Classic White T-shirt'), 'units_sold' => 142, 'revenue' => '3408.0000', 'percent_of_top' => '100.00'],
        ['product' => productRef(5, 'PRD-JNS-005', 'Denim Jeans 5-pocket'), 'units_sold' => 64, 'revenue' => '5056.0000', 'percent_of_top' => '45.07'],
        ['product' => productRef(1, 'PRD-JKT-001', 'Denim Jacket'), 'units_sold' => 38, 'revenue' => '3382.0000', 'percent_of_top' => '26.76'],
        ['product' => productRef(7, 'PRD-BLT-007', 'Leather Belt'), 'units_sold' => 31, 'revenue' => '1395.0000', 'percent_of_top' => '21.83'],
        ['product' => productRef(4, 'PRD-TOT-004', 'Leather Tote'), 'units_sold' => 12, 'revenue' => '2988.0000', 'percent_of_top' => '8.45'],
    ])->and(data_get($loaded, 'top_products.by_revenue'))->toBe([
        ['product' => productRef(5, 'PRD-JNS-005', 'Denim Jeans 5-pocket'), 'units_sold' => 64, 'revenue' => '5056.0000', 'percent_of_top' => '100.00'],
        ['product' => productRef(2, 'PRD-TSH-002', 'Classic White T-shirt'), 'units_sold' => 142, 'revenue' => '3408.0000', 'percent_of_top' => '67.41'],
        ['product' => productRef(1, 'PRD-JKT-001', 'Denim Jacket'), 'units_sold' => 38, 'revenue' => '3382.0000', 'percent_of_top' => '66.89'],
        ['product' => productRef(4, 'PRD-TOT-004', 'Leather Tote'), 'units_sold' => 12, 'revenue' => '2988.0000', 'percent_of_top' => '59.10'],
        ['product' => productRef(7, 'PRD-BLT-007', 'Leather Belt'), 'units_sold' => 31, 'revenue' => '1395.0000', 'percent_of_top' => '27.59'],
    ])->and(data_get($loaded, 'recent_sales.*.id'))->toBe(array_map(demoSaleId(...), [60, 59, 58, 57, 56]))
        ->and(data_get($loaded, 'recent_sales.*.sold_on'))->toBe(['2026-10-07', '2026-10-07', '2026-10-06', '2026-10-05', '2026-10-05'])
        ->and(array_keys($loaded['needs_restock'][0]))->toEqualCanonicalizing(materialContractKeys())
        ->and(data_get($loaded, 'recent_sales.0'))->toBe([
            'id' => demoSaleId(60),
            'product' => productRef(5, 'PRD-JNS-005', 'Denim Jeans 5-pocket'),
            'quantity' => 6,
            'unit_price' => '79.0000',
            'total' => '474.0000',
            'sold_on' => '2026-10-07',
            'note' => 'Boutique Lyon',
        ])->and($loaded['alerts'])->toBe([[
            'product' => productRef(3, 'PRD-SNK-003', 'Canvas Low Sneaker'),
            'bottleneck' => [...materialRef('MAT-SOL-042'), 'stock_status' => 'out'],
        ]])->and(data_get($loaded, 'needs_restock.*.name'))->toBe([
            'Rubber cupsole 42', 'Cotton jersey 180gsm White', 'Metal shank button 17mm', 'Woven brand label',
        ]);
});

it('renders immediately with only metadata; stats and top products arrive in the primary group; secondary panels in the default group', function () {
    actingAs($this->user)->get(dashboardUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->hasAll(['period', 'periods'])
            ->missingAll(['stats', 'top_products', 'needs_restock', 'recent_sales', 'alerts'])
            ->loadDeferredProps('primary', fn (Assert $reloaded) => $reloaded->hasAll(['stats', 'top_products']))
            ->loadDeferredProps('default', fn (Assert $reloaded) => $reloaded->hasAll(['needs_restock', 'recent_sales', 'alerts'])));
});

it('switches the sales figures with the period and keeps the inventory and product ones', function (string $period, int $count, int $units, string $revenue, int $products) {
    actingAs($this->user)->get(dashboardUrl(['period' => $period]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('period', $period)
            ->loadDeferredProps('primary', fn (Assert $primary) => $primary
                ->where('stats.sales_count', $count)
                ->where('stats.units_sold', $units)
                ->where('stats.revenue', $revenue)
                ->where('stats.products_sold_count', $products)
                ->where('stats.inventory_value', '10544.3000')
                ->where('stats.unproducible_count', 1)
                ->where('stats.products_count', 7)));
})->with([
    'last 7 days' => ['last_7_days', 8, 57, '3001.0000', 6],
    'last 30 days' => ['last_30_days', 48, 296, '16850.0000', 6],
    'this month' => ['this_month', 9, 62, '3446.0000', 6],
    'this year' => ['this_year', 60, 429, '23752.0000', 6],
]);

it('reads an empty period as the default and rejects an unknown one', function () {
    actingAs($this->user)->get(dashboardUrl(['period' => '']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('period', 'last_30_days')
            ->loadDeferredProps('primary', fn (Assert $primary) => $primary->where('stats.sales_count', 48)));

    foreach (['last_year', 'custom', ['last_7_days']] as $period) {
        actingAs($this->user)->get(dashboardUrl(['period' => $period]))->assertSessionHasErrors('period');
    }
});

it('reports a period without sales as zero, with scale-4 revenue, and still lists the newest sales', function () {
    // The newest demo sales are dated 2026-10-01 to 2026-10-07: without them "this month" (today is 2026-10-08) is empty.
    Sale::query()->where('sold_on', '>=', '2026-10-01')->delete();

    $response = actingAs($this->user)->get(dashboardUrl(['period' => 'this_month']));

    $response->assertInertia(fn (Assert $page) => $page
        ->loadDeferredProps('primary', fn (Assert $primary) => $primary
            ->where('stats.sales_count', 0)
            ->where('stats.units_sold', 0)
            ->where('stats.revenue', '0.0000')
            ->where('stats.products_sold_count', 0)
            ->where('top_products', ['by_units' => [], 'by_revenue' => []])));

    expect(loadedPageProp($response, 'recent_sales.0.sold_on'))->toBe('2026-09-30');
});

it('changes the figures when a sale is recorded', function () {
    // 3 Denim Jackets at 89.0000 today (validSalePayload).
    actingAs($this->user)->post(route('sales.store'), validSalePayload())->assertSessionHasNoErrors();

    $response = actingAs($this->user)->get(dashboardUrl());

    $loaded = loadedPageProps($response);

    expect(data_get($loaded, 'stats.revenue'))->toBe('17117.0000')
        ->and(data_get($loaded, 'stats.units_sold'))->toBe(299)
        ->and(data_get($loaded, 'stats.sales_count'))->toBe(49)
        ->and(data_get($loaded, 'top_products.by_units.2.units_sold'))->toBe(41)
        ->and(data_get($loaded, 'recent_sales.0.sold_on'))->toBe('2026-10-08')
        ->and(data_get($loaded, 'recent_sales.0.total'))->toBe('267.0000');
});

it('keeps the sales of a deleted product in the figures but not the product in the counts or alerts (D-3)', function () {
    // The T-shirt sold the most; the Sneaker is the product that can't be produced.
    $tshirtId = pid(2);
    demoProduct('PRD-TSH-002')->forceFill(['photo_path' => 'products/tshirt.jpg'])->save();
    demoProduct('PRD-TSH-002')->delete();
    demoProduct('PRD-SNK-003')->delete();

    $loaded = loadedPageProps(actingAs($this->user)->get(dashboardUrl()));

    expect($loaded['stats'])->toMatchArray([
        'revenue' => '16850.0000',
        'units_sold' => 296,
        'sales_count' => 48,
        'products_sold_count' => 6,
        'products_count' => 5,
        'unproducible_count' => 0,
    ])->and(data_get($loaded, 'top_products.by_units.0.product'))->toBe(['id' => $tshirtId, 'sku' => 'PRD-TSH-002', 'name' => 'Classic White T-shirt', 'photo_url' => Storage::disk('public')->url('products/tshirt.jpg')])
        ->and(data_get($loaded, 'recent_sales.1.product.sku'))->toBe('PRD-TSH-002')
        ->and($loaded['alerts'])->toBe([]);
});

it('sends 0.00 as the percent of every product when the top metric is zero', function () {
    Sale::query()->delete();
    Sale::factory()->create(['product_id' => pid(1), 'quantity' => 3, 'unit_price' => '0.0000']);
    Sale::factory()->create(['product_id' => pid(2), 'quantity' => 2, 'unit_price' => '0.0000']);

    $loaded = loadedPageProps(actingAs($this->user)->get(dashboardUrl()));

    // By revenue every product earned nothing: the units break the tie, and no bar has anything to measure against.
    expect(data_get($loaded, 'top_products.by_revenue.*.product.sku'))->toBe(['PRD-JKT-001', 'PRD-TSH-002'])
        ->and(data_get($loaded, 'top_products.by_revenue.*.percent_of_top'))->toBe(['0.00', '0.00'])
        ->and(data_get($loaded, 'top_products.by_units.*.percent_of_top'))->toBe(['100.00', '66.67'])
        ->and(data_get($loaded, 'stats.revenue'))->toBe('0.0000');
});

describe('stock-driven figures', function () {
    it('alerts about every product that can\'t be produced, by name, and counts them', function () {
        // 7 buttons don't cover one Denim Jacket (8): the button is low (7 <= 1000), not out.
        demoMaterial('MAT-BTN-017')->update(['quantity_on_hand' => '7']);

        $loaded = loadedPageProps(actingAs($this->user)->get(dashboardUrl()));

        expect(data_get($loaded, 'alerts.*.product.name'))->toBe(['Canvas Low Sneaker', 'Denim Jacket'])
            ->and(data_get($loaded, 'alerts.*.bottleneck.sku'))->toBe(['MAT-SOL-042', 'MAT-BTN-017'])
            ->and(data_get($loaded, 'alerts.*.bottleneck.stock_status'))->toBe(['out', 'low'])
            ->and(data_get($loaded, 'stats.unproducible_count'))->toBe(2);
    });

    it('does not count a product without a composition as one that can\'t be produced', function () {
        ProductComponent::query()->delete();

        $loaded = loadedPageProps(actingAs($this->user)->get(dashboardUrl()));

        expect(data_get($loaded, 'stats.unproducible_count'))->toBe(0)
            ->and(data_get($loaded, 'stats.products_count'))->toBe(7)
            ->and($loaded['alerts'])->toBe([]);
    });
});

describe('needs restock', function () {
    it('lists the out-of-stock material first, then the low ones by name, as complete Material resources', function () {
        Material::query()->where('sku', 'MAT-JRS-180')->update(['photo_path' => 'materials/jersey.jpg']);

        $loaded = loadedPageProps(actingAs($this->user)->get(dashboardUrl()));

        expect(data_get($loaded, 'needs_restock.*.stock_status'))->toBe(['out', 'low', 'low', 'low'])
            ->and(data_get($loaded, 'needs_restock.*.used_in_count'))->toBe([1, 1, 3, 6])
            ->and(data_get($loaded, 'needs_restock.*.photo_url'))->toBe([null, Storage::disk('public')->url('materials/jersey.jpg'), null, null])
            ->and(data_get($loaded, 'needs_restock.1'))->toMatchArray([
                'sku' => 'MAT-JRS-180',
                'unit_cost' => '4.2000',
                'quantity_on_hand' => '64.0000',
                'reorder_level' => '80.0000',
                'stock_value' => '268.8000',
                'stock_status' => 'low',
            ]);
    });

    it('shows at most five, the out-of-stock ones first, each group by name', function () {
        Material::factory()->create(['name' => 'Aaa empty', 'quantity_on_hand' => '0', 'reorder_level' => '10']);
        Material::factory()->create(['name' => 'Zzz empty', 'quantity_on_hand' => '0', 'reorder_level' => null]);

        $loaded = loadedPageProps(actingAs($this->user)->get(dashboardUrl()));

        // 6 materials need a restock (3 out, 3 low): the list keeps 5 and the count stays the real total.
        expect(data_get($loaded, 'needs_restock.*.name'))->toBe([
            'Aaa empty', 'Rubber cupsole 42', 'Zzz empty', 'Cotton jersey 180gsm White', 'Metal shank button 17mm',
        ])->and(data_get($loaded, 'stats.needs_restock_count'))->toBe(6)
            ->and(data_get($loaded, 'stats.out_of_stock_count'))->toBe(3);
    });

    it('is empty when every material is well stocked, and leaves soft-deleted ones out', function () {
        demoMaterial('MAT-SOL-042')->delete();

        $loaded = loadedPageProps(actingAs($this->user)->get(dashboardUrl()));

        expect(data_get($loaded, 'needs_restock.*.sku'))->toBe(['MAT-JRS-180', 'MAT-BTN-017', 'MAT-LBL-WOV'])
            ->and(data_get($loaded, 'stats.materials_count'))->toBe(11)
            ->and(data_get($loaded, 'stats.out_of_stock_count'))->toBe(0);

        Material::query()->withTrashed()->update(['quantity_on_hand' => '100000']);

        $loaded = loadedPageProps(actingAs($this->user)->get(dashboardUrl()));

        expect($loaded['needs_restock'])->toBe([])
            ->and($loaded['alerts'])->toBe([]);
    });
});

describe('access', function () {
    it('redirects guests to the login page', function () {
        get(dashboardUrl())->assertRedirect(route('login'));
    });

    it('asks the policies of materials, products and sales, before validating the period', function (string $model) {
        Gate::policy($model, DenyDashboardViewPolicy::class);

        actingAs($this->user)->get(dashboardUrl(['period' => 'not-a-period']))->assertForbidden();
    })->with([Material::class, Product::class, Sale::class]);
});

it('answers within the query budget, in the first request and in both deferred groups, whatever the number of sales, products and materials', function () {
    $measure = function (): array {
        $first = null;
        $firstQueries = queryLog(function () use (&$first) {
            $first = actingAs($this->user)->get(dashboardUrl())->assertOk();
        });
        $primaryQueries = queryLog(function () use ($first) {
            $first->assertInertia(fn (Assert $page) => $page->loadDeferredProps('primary', fn (Assert $reloaded) => $reloaded->hasAll(['stats', 'top_products'])));
        });
        $deferredQueries = queryLog(function () use ($first) {
            $first->assertInertia(fn (Assert $page) => $page->loadDeferredProps('default', fn (Assert $reloaded) => $reloaded->hasAll(['needs_restock', 'recent_sales', 'alerts'])));
        });

        return [$firstQueries, $primaryQueries, $deferredQueries];
    };

    $measure(); // warm-up: one-off framework queries
    [$few, $fewPrimary, $fewDeferred] = $measure();

    Material::factory()->count(40)->create();
    Product::factory()->count(30)->withComponents(5)->create()->each(
        fn (Product $product) => Sale::factory()->count(3)->create(['product_id' => $product->id]),
    );

    [$many, $manyPrimary, $manyDeferred] = $measure();

    expect(count($few))->toBeLessThanOrEqual(10, implode(PHP_EOL, $few))
        ->and(count($many))->toBe(count($few), implode(PHP_EOL, $many))
        ->and(count($fewPrimary))->toBeLessThanOrEqual(10, implode(PHP_EOL, $fewPrimary))
        ->and(count($manyPrimary))->toBe(count($fewPrimary), implode(PHP_EOL, $manyPrimary))
        ->and(count($fewDeferred))->toBeLessThanOrEqual(10, implode(PHP_EOL, $fewDeferred))
        ->and(count($manyDeferred))->toBe(count($fewDeferred), implode(PHP_EOL, $manyDeferred));
});
