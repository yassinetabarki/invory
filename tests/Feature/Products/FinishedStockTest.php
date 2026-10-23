<?php

use App\Models\Product;
use App\Models\ProductSizeStock;
use App\Models\Size;
use App\Services\Products\FinishedStock;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The finished stock of a product, sent to its page (S-56-a, AC-1, AC-5)
|--------------------------------------------------------------------------
|
| `finished_stock` is a deferred prop in its own group `stock`, rescued: a failure of the read touches only this prop.
| The demo products are in the database: Denim Jacket (Apparel, S/M/L/XL), Leather Tote (Bag, "One size"), Canvas Low Sneaker (Footwear).
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/** The size of a list by its label (the label is unique within a list; the lists "36 to 46" and "S, M, L, XL" share none). */
function sizeIdOf(string $label): int
{
    return (int) Size::query()->where('label', $label)->value('id');
}

/** Puts finished units of the product on the shelf: `['S' => 3, 'L' => 5]`. */
function putOnShelf(Product $product, array $unitsByLabel): void
{
    foreach ($unitsByLabel as $label => $units) {
        ProductSizeStock::factory()->create(['product_id' => $product->id, 'size_id' => sizeIdOf((string) $label), 'units' => $units]);
    }
}

it('sends every size of the list in order with its units, 0 included, and the total (AC-1)', function (string $sku, array $onShelf, array $expected) {
    putOnShelf(demoProduct($sku), $onShelf);
    $stock = null;

    actingAs($this->user)->get(route('products.show', demoProduct($sku)))
        ->assertOk()
        ->assertInertia(function (Assert $page) use (&$stock) {
            // Nothing of the stock in the first response: it is deferred, in its own group.
            $page->missing('finished_stock')->loadDeferredProps('stock', function (Assert $reloaded) use (&$stock) {
                $stock = $reloaded->toArray()['props']['finished_stock'];
            });
        });

    expect(array_map(fn (array $row): array => [$row['size']['label'], $row['units']], $stock['sizes']))->toBe($expected['sizes'])
        ->and($stock['units'])->toBe($expected['units'])
        ->and($stock['sizes'][0]['size']['id'])->toBe(sizeIdOf($expected['sizes'][0][0]));
})->with([
    'Denim Jacket with S 3, L 5 and XL 2' => ['PRD-JKT-001', ['S' => 3, 'L' => 5, 'XL' => 2], ['units' => 10, 'sizes' => [['S', 3], ['M', 0], ['L', 5], ['XL', 2]]]],
    'a product with nothing in stock' => ['PRD-JKT-006', [], ['units' => 0, 'sizes' => [['S', 0], ['M', 0], ['L', 0], ['XL', 0]]]],
    'a "One size" product with 4' => ['PRD-TOT-004', ['One size' => 4], ['units' => 4, 'sizes' => [['One size', 4]]]],
]);

it('keeps the page when only the stock fails to load: the default group loads and the prop is rescued (AC-5)', function () {
    putOnShelf(demoProduct('PRD-JKT-001'), ['S' => 3]);
    // The reader is resolved inside the deferred closure, so a binding that throws is the stock failing alone.
    $this->app->bind(FinishedStock::class, fn () => throw new RuntimeException('The stock could not be read.'));
    $url = route('products.show', demoProduct('PRD-JKT-001'));

    $visit = actingAs($this->user)->get($url)->assertOk();

    $visit->assertInertia(fn (Assert $page) => $page->loadDeferredProps('default', fn (Assert $reloaded) => $reloaded
        ->has('product')->where('product.name', 'Denim Jacket')->has('sales_last_30_days')->has('recent_sales')));

    $reload = actingAs($this->user)->get($url, [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => $visit->inertiaPage()['version'],
        'X-Inertia-Partial-Component' => 'products/show',
        'X-Inertia-Partial-Data' => 'finished_stock',
    ])->assertOk();

    expect($reload->json('rescuedProps'))->toBe(['finished_stock'])
        ->and($reload->json('props.finished_stock'))->toBeNull();
});
