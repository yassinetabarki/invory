<?php

use App\Models\Material;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| S-44-c, AC-1 and AC-2: a new sign-up opens an empty workshop, and every screen shows only its own workshop (ADR-0013)
|--------------------------------------------------------------------------
|
| A fresh install with the demo workshop of `test@example.com` (12 materials, 7 products, 60 sales). Sami signs up through the real
| route, adds one material and one product, and the figures of both workshops are read from the props of the screens. The other
| tests of this folder run the same checks over every page (WorkshopLeakScanTest) and over a shared workshop (SharedWorkshopTest).
*/

beforeEach(function () {
    $this->travelTo(DEMO_NOW);
    $this->seed(DatabaseSeeder::class);
    $this->demo = User::query()->where('email', 'test@example.com')->firstOrFail();
});

/** Sami registers as `sami@atelier-b.test` through the sign-up route and confirms his e-mail address; the test is then signed in as him. */
function signUpSami(TestCase $test): User
{
    $test->post(route('register.store'), [
        'name' => 'Sami',
        'email' => 'sami@atelier-b.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $sami = User::query()->where('email', 'sami@atelier-b.test')->firstOrFail();
    $sami->markEmailAsVerified();
    $test->actingAs($sami->fresh());

    return $sami;
}

/** Sami adds "Cotton canvas natural" and "Canvas Tote" (0.8 m of the canvas, no waste, overhead left empty), as in AC-2. */
function samiAddsTheCanvasTote(TestCase $test): void
{
    $test->post(route('materials.store'), validMaterialPayload([
        'sku' => 'B-CAN-001', 'name' => 'Cotton canvas natural', 'category' => 'fabric', 'unit' => 'm',
        'unit_cost' => '6', 'quantity_on_hand' => '40',
        'reorder_level' => null, 'supplier_name' => null, 'supplier_reference' => null, 'color' => null, 'composition' => null, 'notes' => null,
    ]))->assertSessionHasNoErrors();

    $test->post(route('products.store'), [
        'sku' => 'B-TOT-001', 'name' => 'Canvas Tote', 'category' => 'bag', 'status' => 'active',
        'labor_cost' => '5', 'overhead_cost' => '',
        'components' => [['material_id' => Material::query()->where('sku', 'B-CAN-001')->firstOrFail()->id, 'quantity' => '0.8', 'waste_percent' => '0']],
    ])->assertSessionHasNoErrors();
}

/** What `test@example.com` still sees: the AC-1 and AC-2 figures of the demo workshop, and nothing of Sami's. */
function expectTheDemoWorkshopUnchanged(TestCase $test): void
{
    $materials = $test->get(route('materials.index'))->assertOk();
    $products = $test->get(route('products.index'))->assertOk();
    $sales = $test->get(route('sales.index', ['period' => 'this_year']))->assertOk();
    $dashboard = loadedPageProps($test->get(route('dashboard'))->assertOk());
    $jacket = loadedPageProp($test->get(route('products.show', demoProduct('PRD-JKT-001')))->assertOk(), 'product');

    expect(pageProp($materials, 'materials.meta.total'))->toBe(12)
        ->and(pageProp($materials, 'summary'))->toBe(['materials_count' => 12, 'inventory_value' => '10544.3000'])
        ->and(pageProp($materials, 'materials.data.*.name'))->not->toContain('Cotton canvas natural')
        ->and(pageProp($products, 'products.meta.total'))->toBe(7)
        ->and(pageProp($products, 'products.data.*.name'))->not->toContain('Canvas Tote')
        ->and(pageProp($sales, 'sales.meta.total'))->toBe(60)
        ->and(pageProp($sales, 'products.*.name'))->not->toContain('Canvas Tote')
        ->and($dashboard['stats'])->toMatchArray(['materials_count' => 12, 'inventory_value' => '10544.3000', 'needs_restock_count' => 4, 'products_count' => 7])
        ->and($jacket['cost']['unit_cost'])->toBe('35.7540')
        ->and($jacket['capacity']['max_producible'])->toBe(112)
        ->and($jacket['capacity']['bottleneck']['name'])->toBe('Metal shank button 17mm');
}

it('opens an empty workshop for a new sign-up and leaves the demo workshop alone (AC-1)', function () {
    signUpSami($this);

    $materials = $this->get(route('materials.index'))->assertOk();
    $products = $this->get(route('products.index'))->assertOk();
    $sales = $this->get(route('sales.index'))->assertOk();
    $dashboard = loadedPageProps($this->get(route('dashboard'))->assertOk());

    expect(pageProp($materials, 'materials.data'))->toBe([])
        ->and(pageProp($materials, 'summary'))->toBe(['materials_count' => 0, 'inventory_value' => '0.0000'])
        ->and(pageProp($products, 'products.data'))->toBe([])
        ->and(pageProp($sales, 'sales.data'))->toBe([])
        ->and($dashboard['stats'])->toMatchArray([
            'revenue' => '0.0000', 'units_sold' => 0, 'sales_count' => 0,
            'inventory_value' => '0.0000', 'materials_count' => 0, 'needs_restock_count' => 0,
            'unproducible_count' => 0, 'products_count' => 0,
        ])
        ->and($dashboard['top_products'])->toBe(['by_units' => [], 'by_revenue' => []])
        ->and([$dashboard['needs_restock'], $dashboard['recent_sales'], $dashboard['alerts']])->toBe([[], [], []]);

    $this->actingAs($this->demo);

    expectTheDemoWorkshopUnchanged($this);
});

it('shows Sami only his own records, and the demo workshop only its own (AC-2)', function () {
    signUpSami($this);
    samiAddsTheCanvasTote($this);

    $materials = $this->get(route('materials.index'))->assertOk();
    $tote = loadedPageProp($this->get(route('products.show', Product::query()->where('sku', 'B-TOT-001')->firstOrFail()))->assertOk(), 'product');

    expect(pageProp($materials, 'materials.data.*.name'))->toBe(['Cotton canvas natural'])
        ->and(pageProp($materials, 'summary'))->toBe(['materials_count' => 1, 'inventory_value' => '240.0000'])
        ->and($tote['cost']['unit_cost'])->toBe('9.8000')
        ->and($tote['capacity']['max_producible'])->toBe(50)
        ->and($tote['capacity']['bottleneck']['name'])->toBe('Cotton canvas natural')
        // The composition picker searches `materials.options`; "Record a sale" and the sales filter read the `products` prop of the sales page.
        ->and($this->getJson(route('materials.options', ['search' => 'Denim']))->assertOk()->json())->toBe([])
        ->and(array_column($this->getJson(route('materials.options', ['search' => 'canvas']))->assertOk()->json(), 'name'))->toBe(['Cotton canvas natural'])
        ->and(pageProp($this->get(route('sales.index'))->assertOk(), 'products.*.name'))->toBe(['Canvas Tote']);

    $this->actingAs($this->demo);

    expectTheDemoWorkshopUnchanged($this);
    expect(array_column($this->getJson(route('materials.options', ['search' => 'canvas']))->assertOk()->json(), 'name'))->toBe(['Cotton canvas 10oz Natural']);
});
