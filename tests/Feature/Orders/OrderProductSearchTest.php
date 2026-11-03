<?php

use App\Domain\Barcodes\NameKey;
use App\Models\Barcode;
use App\Models\BarcodeName;
use App\Models\Colour;
use App\Models\Product;
use App\Models\Size;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

/*
|--------------------------------------------------------------------------
| Search products on New order and Edit order (INV-13-c, AC-1)
|--------------------------------------------------------------------------
|
| `GET orders/products/search?q=` answers with at most 6 active products, by name, each with its shelf stock per colour and size, and
| how many products the search found. No costing: only what is on the shelf.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    $this->jacket = demoProduct('PRD-JKT-001');
    $this->jeans = demoProduct('PRD-JNS-005');

    // The card's shelf (XS · S · M · L · XL · XXL · XXXL); White, from the seeder, holds nothing.
    setFinishedStock($this->jacket, ['Black' => [0, 1, 0, 2, 1, 0, 0], 'Indigo' => [0, 2, 0, 3, 1, 0, 0], 'Light wash' => [0, 0, 0, 0, 0, 0, 0]]);
    setFinishedStock($this->jeans, ['Indigo' => [0, 0, 5, 2, 0, 0, 0], 'Black' => [0, 1, 2, 0, 0, 0, 0]]);
});

/** @return array<string, mixed> */
function searchOrderProducts(string $q): array
{
    return test()->actingAs(test()->user)->getJson(route('orders.products.search', ['q' => $q]))->assertOk()->json();
}

/** @return list<string> */
function foundNames(array $found): array
{
    return array_column(array_column($found['products'], 'product'), 'name');
}

it('finds the Denim Jacket and the Denim Jeans with their stock per colour and size (AC-1)', function () {
    $found = searchOrderProducts('den');

    expect($found['total'])->toBe(2)
        ->and(foundNames($found))->toBe(['Denim Jacket', 'Denim Jeans 5-pocket']);

    $jacket = $found['products'][0];
    $units = fn (array $colour): array => array_column($colour['sizes'], 'units');

    expect($jacket['product'])->toMatchArray(['id' => $this->jacket->id, 'sku' => 'PRD-JKT-001', 'name' => 'Denim Jacket', 'deleted' => false])
        ->and($jacket['product'])->toHaveKey('photo_url')
        ->and($jacket['one_size'])->toBeFalse()
        ->and(array_column($jacket['sizes'], 'label'))->toBe(['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'])
        ->and($jacket['stock']['units'])->toBe(10)
        ->and(array_column(array_column($jacket['stock']['colours'], 'colour'), 'name'))->toBe(['White', 'Black', 'Indigo', 'Light wash'])
        ->and($units($jacket['stock']['colours'][1]))->toBe([0, 1, 0, 2, 1, 0, 0])
        ->and($units($jacket['stock']['colours'][2]))->toBe([0, 2, 0, 3, 1, 0, 0])
        ->and($units($jacket['stock']['colours'][3]))->toBe([0, 0, 0, 0, 0, 0, 0])
        ->and($found['products'][1]['stock']['units'])->toBe(10)
        // No costing: the search shows only the shelf.
        ->and($jacket)->not->toHaveKey('can_produce');
});

it('finds a product by its SKU, a variant SKU typed in full, a colour name or a barcode number (AC-1)', function (Closure $q, array $names) {
    expect(foundNames(searchOrderProducts($q())))->toBe($names);
})->with([
    'part of a SKU' => [fn () => 'jkt-001', ['Denim Jacket']],
    'a variant SKU' => [fn () => 'PRD-JKT-001-IND-M', ['Denim Jacket']],
    'a variant SKU in small letters' => [fn () => 'prd-jns-005-blk-s', ['Denim Jeans 5-pocket']],
    'a colour name' => [fn () => 'light w', ['Denim Jacket']],
    'a barcode number' => [function () {
        $size = Size::query()->where('label', 'M')->whereIn('size_list_id', DB::table('product_categories')->where('id', test()->jeans->category_id)->select('size_list_id'))->value('id');
        $barcode = Barcode::factory()->create(['number' => '2000000000015', 'colour_id' => demoColour('IND')->id, 'size_id' => $size]);
        BarcodeName::factory()->create(['barcode_id' => $barcode->id, 'name_key' => NameKey::of('Denim Jeans 5-pocket'), 'colour_id' => $barcode->colour_id, 'size_id' => $size]);

        return '2000000000015';
    }, ['Denim Jeans 5-pocket']],
]);

it('leaves out draft, archived and deleted products (AC-1)', function () {
    $this->jeans->update(['status' => 'archived']);
    Product::factory()->create(['name' => 'Denim Vest', 'status' => 'draft']);
    Product::factory()->create(['name' => 'Denim Skirt'])->delete();

    expect(foundNames(searchOrderProducts('den')))->toBe(['Denim Jacket']);
});

it('shows at most 6 products, by name, and counts every match (AC-1)', function () {
    foreach (range(1, 12) as $n) {
        Product::factory()->create(['name' => sprintf('Denim Shirt %02d', $n), 'status' => 'active']);
    }

    $found = searchOrderProducts('den');

    expect($found['total'])->toBe(14)
        ->and(foundNames($found))->toBe(['Denim Jacket', 'Denim Jeans 5-pocket', 'Denim Shirt 01', 'Denim Shirt 02', 'Denim Shirt 03', 'Denim Shirt 04']);
});

it('answers nothing to an empty search or a search with no match (AC-1)', function (string $q) {
    expect(searchOrderProducts($q))->toBe(['total' => 0, 'products' => []]);
})->with(['empty' => '', 'spaces' => '   ', 'no match' => 'denm xyz']);

it('gives a "One size" product one column per colour (AC-1)', function () {
    $found = searchOrderProducts('PRD-TOT-004');

    expect($found['products'][0]['one_size'])->toBeTrue()
        ->and(array_column($found['products'][0]['sizes'], 'label'))->toBe(['One size']);
});

it('never finds another workshop\'s product, by name, SKU or barcode (ADR-0013)', function () {
    CurrentWorkshop::run(Workshop::factory()->create(), function (): void {
        $product = Product::factory()->create(['name' => 'Denim Overalls', 'sku' => 'FOREIGN-DEN', 'status' => 'active']);
        $product->colours()->attach(Colour::query()->where('code', 'IND')->value('id'), ['workshop_id' => $product->workshop_id]);
        Barcode::factory()->create(['number' => '2000000000022']);
    });

    expect(foundNames(searchOrderProducts('den')))->toBe(['Denim Jacket', 'Denim Jeans 5-pocket'])
        ->and(searchOrderProducts('FOREIGN-DEN')['total'])->toBe(0)
        ->and(searchOrderProducts('2000000000022')['total'])->toBe(0);
});

it('sends a guest to sign in', function () {
    $this->get(route('orders.products.search', ['q' => 'den']))->assertRedirect(route('login'));
});

it('reads the stock of every product found in one query', function () {
    foreach (range(1, 4) as $n) {
        Product::factory()->create(['name' => "Denim Shirt {$n}", 'status' => 'active']);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();
    searchOrderProducts('den');

    expect(collect(DB::getQueryLog())->filter(fn (array $query): bool => str_contains($query['query'], 'product_size_stocks'))->count())->toBe(1);
});
