<?php

use App\Models\Material;
use App\Models\Product;
use App\Models\ProductComponent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Dashboard on the real products (ADR-0006, S-13c, S-04b)
|--------------------------------------------------------------------------
|
| Time frozen at 2026-10-08, DemoCatalogSeeder run (the 12 materials and the 7 demo products). Every number is computed by the
| production domain code (SalesCalculator, ProductCostingService, InventorySummary); these tests pin the "Expected" tables of
| docs/plans/phase-2-ui-first-preview.md exactly, which is the proof that the figures did not move when the products left the fixture.
*/

beforeEach(fn () => $this->user = preparePreview($this));

function dashboardUrl(array $query = []): string
{
    return route('dashboard', $query);
}

/** A material reference as the contract sends it. */
function materialRef(string $sku): array
{
    $material = previewMaterial($sku);

    return ['id' => $material->id, 'sku' => $sku, 'name' => $material->name, 'unit' => $material->unit->code];
}

/** A product reference as the contract sends it (`photo` is null for the draft Chore Jacket only). `$number` is the plan's number of the demo product. */
function productRef(int $number, string $sku, string $name): array
{
    return ['id' => pid($number), 'sku' => $sku, 'name' => $name, 'photo_url' => asset('mock/photos/'.strtolower($sku).'.svg')];
}

it('sends the DashboardProps contract', function () {
    actingAs($this->user)->get(dashboardUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->hasAll(['period', 'periods', 'stats', 'top_products', 'needs_restock', 'recent_sales', 'alerts'])
            ->has('stats', fn (Assert $stats) => $stats->hasAll([
                'revenue', 'units_sold', 'sales_count', 'products_sold_count', 'inventory_value', 'materials_count',
                'needs_restock_count', 'low_stock_count', 'out_of_stock_count', 'unproducible_count', 'products_count',
            ]))
            ->has('top_products', fn (Assert $top) => $top->hasAll(['by_units', 'by_revenue']))
            ->has('top_products.by_units.0', fn (Assert $row) => $row->hasAll(['product', 'units_sold', 'revenue', 'percent_of_top']))
            ->has('top_products.by_revenue.0', fn (Assert $row) => $row->hasAll(['product', 'units_sold', 'revenue', 'percent_of_top']))
            ->has('needs_restock.0', fn (Assert $material) => $material->hasAll(materialContractKeys()))
            ->has('recent_sales.0', fn (Assert $sale) => $sale->hasAll(['id', 'product', 'quantity', 'unit_price', 'total', 'sold_on', 'note']))
            ->has('alerts.0', fn (Assert $alert) => $alert->hasAll(['product', 'bottleneck'])));
});

it('defaults to the last 30 days and lists the four period presets', function () {
    actingAs($this->user)->get(dashboardUrl())
        ->assertInertia(fn (Assert $page) => $page
            ->where('period', 'last_30_days')
            ->where('periods', [
                ['value' => 'last_7_days', 'label' => 'Last 7 days'],
                ['value' => 'last_30_days', 'label' => 'Last 30 days'],
                ['value' => 'this_month', 'label' => 'This month'],
                ['value' => 'this_year', 'label' => 'This year'],
            ]));
});

describe('stats', function () {
    it('computes the plan\'s expected figures for the last 30 days', function () {
        actingAs($this->user)->get(dashboardUrl())
            ->assertInertia(fn (Assert $page) => $page->where('stats', [
                'revenue' => '16850.0000',
                'units_sold' => 296,
                'sales_count' => 48,
                'products_sold_count' => 6,
                'inventory_value' => '10544.3000',
                'materials_count' => 12,
                'needs_restock_count' => 4,
                'low_stock_count' => 3,
                'out_of_stock_count' => 1,
                'unproducible_count' => 1,
                'products_count' => 7,
            ]));
    });

    it('switches the sales figures with the period and keeps the inventory ones', function (string $period, int $count, int $units, string $revenue) {
        actingAs($this->user)->get(dashboardUrl(['period' => $period]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('period', $period)
                ->where('stats.sales_count', $count)
                ->where('stats.units_sold', $units)
                ->where('stats.revenue', $revenue)
                ->where('stats.products_sold_count', 6)
                ->where('stats.inventory_value', '10544.3000')
                ->where('stats.materials_count', 12)
                ->where('stats.needs_restock_count', 4)
                ->where('stats.unproducible_count', 1)
                ->where('stats.products_count', 7));
    })->with([
        'last 7 days' => ['last_7_days', 8, 57, '3001.0000'],
        'last 30 days' => ['last_30_days', 48, 296, '16850.0000'],
        'this month' => ['this_month', 9, 62, '3446.0000'],
        'this year' => ['this_year', 60, 429, '23752.0000'],
    ]);

    it('treats an empty period as the default', function () {
        actingAs($this->user)->get(dashboardUrl(['period' => '']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('period', 'last_30_days')
                ->where('stats.sales_count', 48));
    });

    it('rejects an unknown period', function (mixed $period) {
        actingAs($this->user)->get(dashboardUrl(['period' => $period]))->assertSessionHasErrors('period');
    })->with(['unknown preset' => 'last_year', 'custom range' => 'custom', 'array' => [['last_7_days']]]);

    it('reports no sales as zero, with scale-4 revenue', function () {
        // The fixture dates are relative to today and none is dated today: on the 1st of the month "this month" is empty.
        $this->travelTo(CarbonImmutable::parse('2026-10-01 12:00:00'));

        actingAs($this->user)->get(dashboardUrl(['period' => 'this_month']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.sales_count', 0)
                ->where('stats.units_sold', 0)
                ->where('stats.revenue', '0.0000')
                ->where('stats.products_sold_count', 0)
                ->where('top_products', ['by_units' => [], 'by_revenue' => []])
                // The recent sales are not limited to the period.
                ->where('recent_sales.0.id', 60)
                ->where('recent_sales.0.sold_on', '2026-09-30'));
    });

    it('counts a product that can\'t be produced once its bottleneck runs out', function () {
        previewMaterial('MAT-BTN-017')->update(['quantity_on_hand' => '7']);

        actingAs($this->user)->get(dashboardUrl())
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.unproducible_count', 2)
                ->where('stats.low_stock_count', 3)
                ->where('stats.out_of_stock_count', 1));
    });
});

describe('top products', function () {
    it('ranks the last 30 days by units with the plan\'s percent of top', function () {
        $response = actingAs($this->user)->get(dashboardUrl());

        expect(pageProp($response, 'top_products.by_units'))->toBe([
            ['product' => productRef(2, 'PRD-TSH-002', 'Classic White T-shirt'), 'units_sold' => 142, 'revenue' => '3408.0000', 'percent_of_top' => '100.00'],
            ['product' => productRef(5, 'PRD-JNS-005', 'Denim Jeans 5-pocket'), 'units_sold' => 64, 'revenue' => '5056.0000', 'percent_of_top' => '45.07'],
            ['product' => productRef(1, 'PRD-JKT-001', 'Denim Jacket'), 'units_sold' => 38, 'revenue' => '3382.0000', 'percent_of_top' => '26.76'],
            ['product' => productRef(7, 'PRD-BLT-007', 'Leather Belt'), 'units_sold' => 31, 'revenue' => '1395.0000', 'percent_of_top' => '21.83'],
            ['product' => productRef(4, 'PRD-TOT-004', 'Leather Tote'), 'units_sold' => 12, 'revenue' => '2988.0000', 'percent_of_top' => '8.45'],
        ]);
    });

    it('ranks the last 30 days by revenue with the plan\'s percent of top', function () {
        $response = actingAs($this->user)->get(dashboardUrl());

        expect(pageProp($response, 'top_products.by_revenue'))->toBe([
            ['product' => productRef(5, 'PRD-JNS-005', 'Denim Jeans 5-pocket'), 'units_sold' => 64, 'revenue' => '5056.0000', 'percent_of_top' => '100.00'],
            ['product' => productRef(2, 'PRD-TSH-002', 'Classic White T-shirt'), 'units_sold' => 142, 'revenue' => '3408.0000', 'percent_of_top' => '67.41'],
            ['product' => productRef(1, 'PRD-JKT-001', 'Denim Jacket'), 'units_sold' => 38, 'revenue' => '3382.0000', 'percent_of_top' => '66.89'],
            ['product' => productRef(4, 'PRD-TOT-004', 'Leather Tote'), 'units_sold' => 12, 'revenue' => '2988.0000', 'percent_of_top' => '59.10'],
            ['product' => productRef(7, 'PRD-BLT-007', 'Leather Belt'), 'units_sold' => 31, 'revenue' => '1395.0000', 'percent_of_top' => '27.59'],
        ]);
    });

    it('keeps the five best of six sold products, breaking unit ties by revenue (last 7 days)', function () {
        $response = actingAs($this->user)->get(dashboardUrl(['period' => 'last_7_days']));

        // Tote and Sneaker both sold 2 units: the Tote earned more, so it takes the fifth place and the Sneaker drops out.
        expect(pageProp($response, 'top_products.by_units.*.product.id'))->toBe(array_map(pid(...), [2, 5, 1, 7, 4]))
            ->and(pageProp($response, 'top_products.by_units.*.units_sold'))->toBe([32, 14, 4, 3, 2])
            ->and(pageProp($response, 'top_products.by_units.*.percent_of_top'))->toBe(['100.00', '43.75', '12.50', '9.38', '6.25'])
            // By revenue the Belt (135) is the sixth and drops out instead.
            ->and(pageProp($response, 'top_products.by_revenue.*.product.id'))->toBe(array_map(pid(...), [5, 2, 4, 1, 3]))
            ->and(pageProp($response, 'top_products.by_revenue.*.revenue'))->toBe(['1106.0000', '768.0000', '498.0000', '356.0000', '138.0000'])
            ->and(pageProp($response, 'top_products.by_revenue.*.percent_of_top'))->toBe(['100.00', '69.44', '45.03', '32.19', '12.48']);
    });

    it('ranks the whole year with at most five rows per ranking', function () {
        $response = actingAs($this->user)->get(dashboardUrl(['period' => 'this_year']));

        expect(pageProp($response, 'top_products.by_units'))->toHaveCount(5)
            ->and(pageProp($response, 'top_products.by_revenue'))->toHaveCount(5)
            ->and(pageProp($response, 'top_products.by_units.0.percent_of_top'))->toBe('100.00')
            ->and(pageProp($response, 'top_products.by_revenue.0.percent_of_top'))->toBe('100.00');
    });

    it('serialises the photo of each ranked product', function () {
        $response = actingAs($this->user)->get(dashboardUrl());

        expect(pageProp($response, 'top_products.by_units.0.product.photo_url'))->toBe(asset('mock/photos/prd-tsh-002.svg'));
    });
});

describe('needs restock', function () {
    it('lists the out-of-stock material first, then the low ones by name', function () {
        $response = actingAs($this->user)->get(dashboardUrl());

        expect(pageProp($response, 'needs_restock.*.name'))->toBe([
            'Rubber cupsole 42', 'Cotton jersey 180gsm White', 'Metal shank button 17mm', 'Woven brand label',
        ])->and(pageProp($response, 'needs_restock.*.stock_status'))->toBe(['out', 'low', 'low', 'low']);
    });

    it('sends complete Material resources with the used-in count and the photo', function () {
        $response = actingAs($this->user)->get(dashboardUrl());

        expect(pageProp($response, 'needs_restock.*.used_in_count'))->toBe([1, 1, 3, 6])
            ->and(pageProp($response, 'needs_restock.*.photo_url'))->toBe([
                asset('mock/photos/mat-sol-042.svg'),
                asset('mock/photos/mat-jrs-180.svg'),
                asset('mock/photos/mat-btn-017.svg'),
                asset('mock/photos/mat-lbl-wov.svg'),
            ])
            ->and(pageProp($response, 'needs_restock.1'))->toMatchArray([
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

        $response = actingAs($this->user)->get(dashboardUrl());

        // 6 materials need a restock (3 out, 3 low): the list keeps 5 and the count stays the real total.
        expect(pageProp($response, 'needs_restock.*.name'))->toBe([
            'Aaa empty', 'Rubber cupsole 42', 'Zzz empty', 'Cotton jersey 180gsm White', 'Metal shank button 17mm',
        ])->and(pageProp($response, 'stats.needs_restock_count'))->toBe(6)
            ->and(pageProp($response, 'stats.out_of_stock_count'))->toBe(3);
    });

    it('is empty when every material is well stocked', function () {
        Material::query()->update(['quantity_on_hand' => '100000']);

        $response = actingAs($this->user)->get(dashboardUrl());

        expect(pageProp($response, 'needs_restock'))->toBe([])
            ->and(pageProp($response, 'stats.needs_restock_count'))->toBe(0)
            ->and(pageProp($response, 'alerts'))->toBe([])
            ->and(pageProp($response, 'stats.unproducible_count'))->toBe(0);
    });

    it('leaves soft-deleted materials out of the list and the counts', function () {
        previewMaterial('MAT-SOL-042')->delete();

        $response = actingAs($this->user)->get(dashboardUrl());

        expect(pageProp($response, 'needs_restock.*.sku'))->toBe(['MAT-JRS-180', 'MAT-BTN-017', 'MAT-LBL-WOV'])
            ->and(pageProp($response, 'stats.materials_count'))->toBe(11)
            ->and(pageProp($response, 'stats.out_of_stock_count'))->toBe(0)
            // 10544.3 - 0 (the cupsole is worth nothing at stock 0).
            ->and(pageProp($response, 'stats.inventory_value'))->toBe('10544.3000');
    });
});

describe('recent sales and alerts', function () {
    it('lists the newest five sales whatever the period', function (string $period) {
        $response = actingAs($this->user)->get(dashboardUrl(['period' => $period]));

        expect(pageProp($response, 'recent_sales.*.id'))->toBe([60, 59, 58, 57, 56])
            ->and(pageProp($response, 'recent_sales.*.sold_on'))->toBe(['2026-10-07', '2026-10-07', '2026-10-06', '2026-10-05', '2026-10-05']);
    })->with(['last_7_days', 'last_30_days', 'this_month', 'this_year']);

    it('sends each recent sale with its product, total and note', function () {
        $response = actingAs($this->user)->get(dashboardUrl());

        expect(pageProp($response, 'recent_sales.0'))->toBe([
            'id' => 60,
            'product' => productRef(5, 'PRD-JNS-005', 'Denim Jeans 5-pocket'),
            'quantity' => 6,
            'unit_price' => '79.0000',
            'total' => '474.0000',
            'sold_on' => '2026-10-07',
            'note' => 'Boutique Lyon',
        ]);
    });

    it('alerts about the one product that can\'t be produced', function () {
        $response = actingAs($this->user)->get(dashboardUrl());

        expect(pageProp($response, 'alerts'))->toBe([[
            'product' => productRef(3, 'PRD-SNK-003', 'Canvas Low Sneaker'),
            'bottleneck' => [...materialRef('MAT-SOL-042'), 'stock_status' => 'out'],
        ]]);
    });

    it('alerts about every product that can\'t be produced, by name, with the stock status of its bottleneck', function () {
        // 7 buttons don't cover one Denim Jacket (8): the button is low (7 <= 1000), not out.
        previewMaterial('MAT-BTN-017')->update(['quantity_on_hand' => '7']);

        $response = actingAs($this->user)->get(dashboardUrl());

        expect(pageProp($response, 'alerts.*.product.name'))->toBe(['Canvas Low Sneaker', 'Denim Jacket'])
            ->and(pageProp($response, 'alerts.*.bottleneck.sku'))->toBe(['MAT-SOL-042', 'MAT-BTN-017'])
            ->and(pageProp($response, 'alerts.*.bottleneck.stock_status'))->toBe(['out', 'low']);
    });

    it('does not count a product without a composition as one that can\'t be produced', function () {
        // Every composition is empty: no capacity is 0 (it is null), so nothing raises an alert.
        ProductComponent::query()->delete();

        $response = actingAs($this->user)->get(dashboardUrl());

        expect(pageProp($response, 'stats.unproducible_count'))->toBe(0)
            ->and(pageProp($response, 'stats.products_count'))->toBe(7)
            ->and(pageProp($response, 'stats.materials_count'))->toBe(12)
            ->and(pageProp($response, 'alerts'))->toBe([]);
    });

    it('stops alerting about a product once its bottleneck is taken off its composition', function () {
        // Without the cupsole the Sneaker's composition is canvas, laces, thread and label: the label limits it to 400.
        ProductComponent::query()->where('product_id', pid(3))->where('material_id', previewMaterial('MAT-SOL-042')->id)->delete();

        $response = actingAs($this->user)->get(dashboardUrl());

        expect(pageProp($response, 'alerts'))->toBe([])
            ->and(pageProp($response, 'stats.unproducible_count'))->toBe(0);
    });

    it('counts the real products: a new one, a deleted one, a draft', function () {
        Product::factory()->draft()->create();
        previewProduct('PRD-BLT-007')->delete();

        $response = actingAs($this->user)->get(dashboardUrl());

        expect(pageProp($response, 'stats.products_count'))->toBe(7)
            // The deleted Belt is not a product any more, and its sales are skipped with it: 31 units of the last 30 days.
            ->and(pageProp($response, 'stats.units_sold'))->toBe(296 - 31)
            ->and(pageProp($response, 'stats.sales_count'))->toBe(48 - 8);
    });

    it('skips the preview sales of a product that was deleted', function () {
        previewProduct('PRD-TSH-002')->delete();

        $response = actingAs($this->user)->get(dashboardUrl());

        expect(pageProp($response, 'top_products.by_units.*.product.sku'))->toBe(['PRD-JNS-005', 'PRD-JKT-001', 'PRD-BLT-007', 'PRD-TOT-004', 'PRD-SNK-003'])
            ->and(pageProp($response, 'top_products.by_units.0.percent_of_top'))->toBe('100.00')
            ->and(pageProp($response, 'recent_sales.*.id'))->toBe([60, 58, 57, 56, 54]);
    });
});

it('answers within a bounded number of queries, whatever the number of materials and products', function () {
    $queries = function (): int {
        DB::enableQueryLog();
        DB::flushQueryLog();
        actingAs($this->user)->get(dashboardUrl())->assertOk();

        return count(DB::getQueryLog());
    };

    $baseline = $queries();

    Material::factory()->count(40)->create();
    Product::factory()->count(30)->withComponents(5)->create();

    // The units, the products with their lines and the materials of those (3), the inventory totals, the restock list with its
    // categories (the units are shared with the compositions), and the reference lists of a full load.
    expect($baseline)->toBeLessThanOrEqual(7 + REFERENCE_QUERIES)
        ->and($queries())->toBe($baseline);
});
