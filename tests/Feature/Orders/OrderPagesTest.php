<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The data of the New order and Edit order pages (S-48-b, AC-2, AC-6 and AC-7 server side)
|--------------------------------------------------------------------------
|
| The demo has six active products and a draft (Canvas Chore Jacket). On top of it: an archived product and a deleted one that are on
| no order, and a product of another workshop.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);

    Product::factory()->create(['sku' => 'PRD-OLD-100', 'name' => 'Old Anorak', 'status' => ProductStatus::Archived]);
    Product::factory()->create(['sku' => 'PRD-GON-101', 'name' => 'Retired Cap'])->delete();
    CurrentWorkshop::run(Workshop::factory()->create(), fn () => Product::factory()->create(['sku' => 'ZZ-FOR-001', 'name' => 'Foreign Tote']));
});

it('sends no product list to New order: "Search products" asks the search route (INV-13 AC-1)', function () {
    $response = actingAs($this->user)->get(route('orders.create'))->assertOk();

    expect(pageProp($response, 'products'))->toBeNull()
        ->and(pageProp($response, 'size_lists'))->toBeNull();
});

it('sends Edit order each line\'s product with its sizes and shelf stock, in line order, whatever its status (INV-13 AC-2, AC-4)', function () {
    $order = bookOrder();
    setFinishedStock(demoProduct('PRD-JNS-005'), ['Indigo' => [0, 0, 5, 2, 0, 0, 0]]);
    demoProduct('PRD-JKT-001')->update(['status' => ProductStatus::Archived]);
    demoProduct('PRD-JNS-005')->delete();

    $response = actingAs($this->user)->get(route('orders.edit', $order))->assertOk();

    expect(pageProp($response, 'products'))->toBeNull()
        ->and(array_map(fn (array $line): array => [$line['product']['name'], $line['product']['status'], $line['product']['deleted']], pageProp($response, 'line_products')))->toBe([
            ['Denim Jacket', 'archived', false], ['Denim Jeans 5-pocket', 'active', true],
        ])
        // Both lines were recorded before sizes: they count by "One size", whatever their product's sizes.
        ->and(pageProp($response, 'line_products.0.one_size'))->toBeTrue()
        ->and(pageProp($response, 'line_products.0.sizes.*.label'))->toBe(['One size'])
        ->and(pageProp($response, 'line_products.0.product'))->toHaveKey('photo_url')
        ->and(pageProp($response, 'line_products.1.stock.units'))->toBe(7)
        ->and(pageProp($response, 'line_products.1.stock.colours.*.colour.name'))->toBe(['White', 'Indigo'])
        ->and(pageProp($response, 'order'))->toMatchArray(['id' => $order->id, 'client' => 'Boutique Lina', 'ordered_on' => '2026-10-15', 'units' => 80])
        ->and(pageProp($response, 'order.lines.*.quantity'))->toBe([50, 30])
        ->and(pageProp($response, 'order.lines.*.product.name'))->toBe(['Denim Jacket', 'Denim Jeans 5-pocket']);
});

it('sends a line counted by size its product\'s own sizes on Edit order (INV-13 AC-4)', function () {
    $order = bookSizedOrder();

    $response = actingAs($this->user)->get(route('orders.edit', $order))->assertOk();

    expect(pageProp($response, 'line_products.0.one_size'))->toBeFalse()
        ->and(pageProp($response, 'line_products.0.sizes.*.label'))->toBe(['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'])
        ->and(pageProp($response, 'order.lines.0.sizes.0.colour'))->toBe(['id' => demoColour('WHT')->id, 'code' => 'WHT', 'name' => 'White', 'swatch' => '#FFFFFF']);
});
