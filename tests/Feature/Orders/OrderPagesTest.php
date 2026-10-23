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

it('offers only the active products of the workshop on New order, by name', function () {
    $response = actingAs($this->user)->get(route('orders.create'))->assertOk();

    expect(pageProp($response, 'products.*.name'))->toBe([
        'Canvas Low Sneaker', 'Classic White T-shirt', 'Denim Jacket', 'Denim Jeans 5-pocket', 'Leather Belt', 'Leather Tote',
    ])
        ->and(pageProp($response, 'products.2'))->toBe(['id' => pid(1), 'sku' => 'PRD-JKT-001', 'name' => 'Denim Jacket', 'status' => 'active', 'deleted' => false, 'size_list_code' => 's_to_xl']);
});

it('sends the three size lists by code, once each, and the code of each product\'s list, on New order and Edit order (S-60)', function () {
    $order = bookOrder();

    foreach ([route('orders.create'), route('orders.edit', $order)] as $uri) {
        $response = actingAs($this->user)->get($uri)->assertOk();

        expect(array_keys(pageProp($response, 'size_lists')))->toBe(['s_to_xl', 'eu_36_to_46', 'one_size'])
            ->and(pageProp($response, 'size_lists.s_to_xl.name'))->toBe('S, M, L, XL')
            ->and(pageProp($response, 'size_lists.s_to_xl.sizes.*.label'))->toBe(['S', 'M', 'L', 'XL'])
            ->and(pageProp($response, 'size_lists.eu_36_to_46.name'))->toBe('36 to 46')
            ->and(pageProp($response, 'size_lists.eu_36_to_46.sizes'))->toHaveCount(11)
            ->and(pageProp($response, 'size_lists.one_size'))->toMatchArray(['code' => 'one_size', 'name' => 'One size'])
            ->and(array_column(pageProp($response, 'products'), 'size_list_code', 'name'))->toMatchArray([
                'Denim Jacket' => 's_to_xl', 'Canvas Low Sneaker' => 'eu_36_to_46', 'Leather Tote' => 'one_size',
            ]);
    }
});

it('offers on Edit order the active products plus every product already on the order, whatever its status', function () {
    $order = bookOrder();
    demoProduct('PRD-JKT-001')->update(['status' => ProductStatus::Archived]);
    demoProduct('PRD-JNS-005')->delete();

    $response = actingAs($this->user)->get(route('orders.edit', $order))->assertOk();

    // The archived and the deleted products of the order stay on offer; the draft, the archived and deleted ones of no order and the
    // foreign one don't.
    expect(array_map(fn (array $product): array => [$product['name'], $product['status'], $product['deleted']], pageProp($response, 'products')))->toBe([
        ['Canvas Low Sneaker', 'active', false], ['Classic White T-shirt', 'active', false], ['Denim Jacket', 'archived', false],
        ['Denim Jeans 5-pocket', 'active', true], ['Leather Belt', 'active', false], ['Leather Tote', 'active', false],
    ])
        ->and(pageProp($response, 'order'))->toMatchArray(['id' => $order->id, 'client' => 'Boutique Lina', 'ordered_on' => '2026-10-15', 'units' => 80])
        ->and(pageProp($response, 'order.lines.*.quantity'))->toBe([50, 30])
        ->and(pageProp($response, 'order.lines.*.product.name'))->toBe(['Denim Jacket', 'Denim Jeans 5-pocket']);
});
