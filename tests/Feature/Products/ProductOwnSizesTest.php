<?php

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductionBatch;
use App\Models\ProductRestockLine;
use App\Models\Size;
use App\Models\User;
use App\Services\Workshops\CurrentWorkshop;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| A product's sizes are its own (INV-18-c, AC-3, AC-7, ADR-0028)
|--------------------------------------------------------------------------
|
| A new workshop starts with the 19 sizes; a category changes none of a product's sizes, whatever the product is on; and a product given
| S · M · L · XL offers exactly those four wherever sizes are offered.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

it('gives a workshop made at sign-up the 19 sizes, numbered 10 to 190, with One size flagged', function () {
    $this->post(route('register.store'), [
        'name' => 'Sami', 'email' => 'sami@atelier-b.test', 'password' => 'password', 'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $workshopId = User::query()->where('email', 'sami@atelier-b.test')->firstOrFail()->workshop_id;

    $sizes = CurrentWorkshop::run($workshopId, fn () => Size::query()->ordered()->get());

    expect($sizes->pluck('label')->map(fn (mixed $label): string => (string) $label)->all())
        ->toBe(['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46', 'One size'])
        ->and($sizes->pluck('position')->all())->toBe(range(10, 190, 10))
        ->and($sizes->where('is_one_size', true)->pluck('label')->all())->toBe(['One size'])
        // No row of another workshop: the demo workshop keeps its own 19.
        ->and(Size::query()->count())->toBe(19);
});

it('keeps the sizes of a product on orders, in stock and in production when its category changes', function (string $category) {
    $jacket = demoProduct('PRD-JKT-001');
    $labels = fn (): array => sizesOf(Product::query()->findOrFail($jacket->id))->pluck('label')->all();
    $expected = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

    bookOrder(lines: [[$jacket, 10]]);
    setFinishedStock($jacket, ['Indigo' => [0, 2, 0, 5, 0, 0, 0]]);
    ProductionBatch::factory()->create(['product_id' => $jacket->id, 'colour_id' => demoColour('IND')->id, 'size_id' => sizeId('M', $jacket), 'units' => 4]);
    expect($labels())->toBe($expected);

    actingAs($this->user)
        ->put(route('products.update', $jacket), validProductPayload([
            'sku' => $jacket->sku, 'name' => $jacket->name, 'status' => 'active', 'category' => $category,
            'colours' => $jacket->colours()->pluck('colours.id')->all(),
        ]))
        ->assertSessionHasNoErrors();

    expect(Product::query()->findOrFail($jacket->id)->category_id)->toBe(ProductCategory::query()->where('code', $category)->value('id'))
        ->and($labels())->toBe($expected);
})->with(['Footwear' => 'footwear', 'Bag' => 'bag', 'Accessory' => 'accessory']);

/**
 * Where a product's sizes are offered, as the labels each place offers the product (a product page, a search, a tracker, a window), in
 * the order they appear.
 *
 * @return array<string, Closure(Product, Order): list<string>>
 */
dataset('places that offer sizes', [
    'Update stock' => [fn (Product $product): array => array_column(array_column(loadedPageProp(actingAs(test()->user)->get(route('products.show', $product)), 'finished_stock')['sizes'], 'size'), 'label')],
    'New restock' => [fn (Product $product): array => array_column(actingAs(test()->user)->getJson(route('restocks.products.line', $product))->assertOk()->json('sizes'), 'label')],
    'the barcodes' => [fn (Product $product): array => array_column(array_column(loadedPageProp(actingAs(test()->user)->get(route('products.show', $product)), 'barcodes')['colours'][0]['variants'], 'size'), 'label')],
    'the order search' => [fn (Product $product): array => array_column(actingAs(test()->user)->getJson(route('orders.products.search', ['q' => 'Slim']))->assertOk()->json('products.0.sizes'), 'label')],
    'the order tracker' => [function (Product $product, Order $order): array {
        $tracker = loadedPageProp(actingAs(test()->user)->get(route('orders.show', $order)), 'tracker');

        return array_column(array_column($tracker['lines'][0]['colours'][0]['rows'], 'size'), 'label');
    }],
    'Start batch' => [fn (Product $product): array => array_column(actingAs(test()->user)->getJson(route('progress.products.line', $product))->assertOk()->json('sizes'), 'label')],
    'Progress and its work ticket' => [function (Product $product): array {
        actingAs(test()->user)->post(route('progress.batches.store'), ['product_id' => $product->id, 'units' => restockLine($product, ['White' => ['S' => 1, 'M' => 1, 'L' => 1, 'XL' => 1]])['units']])->assertSessionHasNoErrors();
        $batches = ProductionBatch::query()->where('product_id', $product->id)->orderBy('id')->get();
        $rows = loadedPageProp(actingAs(test()->user)->get(route('progress.index')), 'products.data.0.rows');
        $tickets = actingAs(test()->user)->get(route('progress.tickets', ['batches' => $batches->pluck('id')->all()]))->inertiaProps('tickets');

        // Progress and the tickets agree on the four sizes, in order; the variant SKU names each.
        expect(array_column($tickets, 'sku'))->toBe(array_map(fn (string $label): string => "{$product->sku}-WHT-{$label}", ['S', 'M', 'L', 'XL']));

        return array_column(array_column(array_filter($rows, fn (array $row): bool => $row['size'] !== null), 'size'), 'label');
    }],
]);

it('offers a product exactly the sizes it was given, in order, wherever sizes are offered (AC-7)', function (Closure $offered) {
    $slim = Product::factory()->withSizes(['S', 'M', 'L', 'XL'])->create(['sku' => 'PRD-JKT-077', 'name' => 'Denim Jacket Slim', 'category' => 'apparel']);
    $slim->colours()->attach(demoColour('WHT')->id, ['workshop_id' => $slim->workshop_id]);
    $order = Order::factory()->create();
    OrderLine::factory()->sized(['S' => 4, 'M' => 6, 'L' => 3, 'XL' => 1])->create(['order_id' => $order->id, 'product_id' => $slim->id]);

    expect($offered($slim, $order))->toBe(['S', 'M', 'L', 'XL']);
})->with('places that offer sizes');

it('still shows a restock line on a size its product no longer has', function () {
    $slim = Product::factory()->withSizes(['S', 'M', 'L', 'XL'])->create(['sku' => 'PRD-JKT-077', 'name' => 'Denim Jacket Slim', 'category' => 'apparel']);
    $line = ProductRestockLine::factory()->create(['product_id' => $slim->id, 'colour_id' => demoColour('WHT')->id, 'size_id' => sizeId('XXL'), 'units' => 3]);
    // Restocks don't lock a size: it comes off the product afterwards (ADR-0028 consequences).
    $slim->sizes()->detach(sizeId('XXL'));

    $block = actingAs($this->user)->get(route('restocks.index', ['restock' => $line->product_restock_id]))->assertOk()->inertiaProps('restock.blocks.0');

    expect(array_column($block['sizes'], 'label'))->toBe(['S', 'M', 'L', 'XL', 'XXL'])
        ->and($block['rows'][0]['units'])->toBe([0, 0, 0, 0, 3]);
});
