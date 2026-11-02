<?php

use App\Domain\Barcodes\Ean13;
use App\Domain\Barcodes\NameKey;
use App\Enums\ProductStatus;
use App\Models\Barcode;
use App\Models\BarcodeName;
use App\Models\Colour;
use App\Models\Product;
use App\Models\Size;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The barcodes of a product (INV-8-b, AC-2, ADR-0020)
|--------------------------------------------------------------------------
|
| A product owns no barcode: the page looks up the number that stands for its name, each colour and each size. Read through the product
| page's deferred prop, so the Resource's shape is proved on the way. The demo catalogue gives the Denim Jacket (PRD-JKT-001), the Canvas
| Low Sneaker (PRD-SNK-003, footwear) and the Leather Tote (PRD-TOT-004, "One size").
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/** Gives the product exactly these colours (by code), those of the product's own workshop (outside a request the lookup is unscoped, and two workshops each have IND). */
function giveColours(Product $product, string ...$codes): Product
{
    CurrentWorkshop::run($product->workshop_id, fn () => $product->colours()->syncWithPivotValues(
        Colour::query()->whereIn('code', $codes)->pluck('id')->all(),
        ['workshop_id' => $product->workshop_id],
    ));

    return $product;
}

/** A barcode of the current workshop that stands for this name in this colour (code) and size (label). */
function makeBarcode(string $name, string $colourCode, string $sizeLabel, string $number): Barcode
{
    $barcode = Barcode::factory()->create([
        'number' => $number,
        'colour_id' => Colour::query()->where('code', $colourCode)->value('id'),
        'size_id' => Size::query()->where('label', $sizeLabel)->value('id'),
    ]);
    BarcodeName::factory()->create(['barcode_id' => $barcode->id, 'name_key' => NameKey::of($name)]);

    return $barcode;
}

/** The `barcodes` prop of the product's page. */
function barcodesOf(Product $product): array
{
    return loadedPageProp(actingAs(test()->user)->get(route('products.show', $product)), 'barcodes');
}

it('gives every variant its SKU, its number and what is missing, for sizes in letters, in numbers and "One size"', function () {
    $jacket = giveColours(demoProduct('PRD-JKT-001'), 'BLK', 'IND', 'LWH');

    // Indigo XS..XXXL are 1..7, Black XS..XXXL are 8..14; Light wash has none.
    foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'] as $position => $size) {
        makeBarcode('Denim Jacket', 'IND', $size, Ean13::inStore($position + 1));
        makeBarcode('Denim Jacket', 'BLK', $size, Ean13::inStore($position + 8));
    }

    $barcodes = barcodesOf($jacket);
    $indigo = $barcodes['colours'][1];

    expect($barcodes['missing'])->toBe(7)
        ->and($barcodes['with_barcode'])->toBe(14)
        ->and($barcodes['missing_names'])->toBe('Light wash XS, S, M, L, XL, XXL and XXXL')
        ->and(array_column(array_column($barcodes['colours'], 'colour'), 'name'))->toBe(['Black', 'Indigo', 'Light wash'])
        ->and(array_column($barcodes['colours'], 'missing'))->toBe([0, 0, 7])
        ->and(array_column(array_column($indigo['variants'], 'size'), 'label'))->toBe(['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'])
        ->and(array_column($indigo['variants'], 'barcode'))->toBe(['2000000000015', '2000000000022', '2000000000039', '2000000000046', '2000000000053', '2000000000060', '2000000000077'])
        ->and(array_column($indigo['variants'], 'sku'))->toBe(['PRD-JKT-001-IND-XS', 'PRD-JKT-001-IND-S', 'PRD-JKT-001-IND-M', 'PRD-JKT-001-IND-L', 'PRD-JKT-001-IND-XL', 'PRD-JKT-001-IND-XXL', 'PRD-JKT-001-IND-XXXL'])
        ->and(array_column($barcodes['colours'][0]['variants'], 'barcode'))->toBe(['2000000000084', '2000000000091', '2000000000107', '2000000000114', '2000000000121', '2000000000138', '2000000000145'])
        ->and(array_column($barcodes['colours'][2]['variants'], 'barcode'))->toBe(array_fill(0, 7, null))
        ->and($barcodes['colours'][2]['variants'][0]['sku'])->toBe('PRD-JKT-001-LWH-XS');

    // A shoe: the size is a number in the SKU and in the sentence ("sizes", not "size").
    $shoe = barcodesOf(giveColours(demoProduct('PRD-SNK-003'), 'BLK'));

    expect($shoe['missing'])->toBe(11)
        ->and($shoe['missing_names'])->toBe('Black sizes 36, 37, 38, 39, 40, 41, 42, 43, 44, 45 and 46')
        ->and($shoe['colours'][0]['variants'][2]['sku'])->toBe('PRD-SNK-003-BLK-38');

    // "One size": one variant per colour, no size part in the SKU, and the sentence names the colours.
    $tote = barcodesOf(giveColours(demoProduct('PRD-TOT-004'), 'CAM', 'RST'));

    expect($tote['missing'])->toBe(2)
        ->and($tote['with_barcode'])->toBe(0)
        ->and($tote['missing_names'])->toBe('Camel and Rust')
        ->and(array_map(fn (array $colour): int => count($colour['variants']), $tote['colours']))->toBe([1, 1])
        ->and(array_column(array_column($tote['colours'], 'variants'), 0))->each->toMatchArray(['barcode' => null, 'shared_with' => []])
        ->and($tote['colours'][0]['variants'][0]['sku'])->toBe('PRD-TOT-004-CAM')
        ->and($tote['colours'][0]['variants'][0]['size']['label'])->toBe('One size');

    // Everything made: nothing missing, and no sentence.
    foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'] as $position => $size) {
        makeBarcode('Denim Jacket', 'LWH', $size, Ean13::inStore($position + 15));
    }

    expect(barcodesOf($jacket))->toMatchArray(['missing' => 0, 'missing_names' => null, 'with_barcode' => 21]);
});

/** Another product that carries the same name, with Indigo (the colours can be said). */
function sharingProduct(string $sku, array $attributes = [], array $colours = ['IND']): Product
{
    return giveColours(Product::factory()->create(['sku' => $sku, 'name' => 'Denim Jacket', 'category' => 'apparel', ...$attributes]), ...$colours);
}

it('lists the other products that show the same barcode, by SKU, and no one else', function (Closure $arrange, array $expected, int $missing) {
    // The jacket has Indigo only, with a barcode in each of its seven sizes.
    $jacket = giveColours(demoProduct('PRD-JKT-001'), 'IND');

    foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'] as $position => $size) {
        makeBarcode('Denim Jacket', 'IND', $size, Ean13::inStore($position + 1));
    }

    $viewed = $arrange($jacket) ?? $jacket;
    $barcodes = barcodesOf($viewed);
    $variants = $barcodes['colours'][0]['variants'];

    expect($barcodes['missing'])->toBe($missing)
        ->and(array_column($variants, 'barcode'))->toBe(['2000000000015', '2000000000022', '2000000000039', '2000000000046', '2000000000053', '2000000000060', '2000000000077'])
        ->and(array_map(fn (array $variant): array => array_column($variant['shared_with'], 'sku'), $variants))->toBe(array_fill(0, 7, $expected));
})->with([
    'one other product' => [function () {
        sharingProduct('PRD-JKT-002');
    }, ['PRD-JKT-002'], 0],
    'five others, by SKU whatever order they were made in' => [function () {
        foreach (['PRD-JKT-007', 'PRD-JKT-003', 'PRD-JKT-005', 'PRD-JKT-002', 'PRD-JKT-004'] as $sku) {
            sharingProduct($sku);
        }
    }, ['PRD-JKT-002', 'PRD-JKT-003', 'PRD-JKT-004', 'PRD-JKT-005', 'PRD-JKT-007'], 0],
    '"denim  jacket" is the same name' => [function () {
        sharingProduct('PRD-JKT-002', ['name' => 'denim  jacket ']);
    }, ['PRD-JKT-002'], 0],
    'a deleted product, another workshop\'s product and a same-name product without Indigo are never listed' => [function () {
        sharingProduct('PRD-JKT-002')->delete();
        sharingProduct('PRD-JKT-003', colours: ['LWH']);
        CurrentWorkshop::run(Workshop::factory()->create(), fn () => sharingProduct('PRD-JKT-004'));
        sharingProduct('PRD-JKT-005', ['name' => 'Denim Jackets']);
    }, [], 0],
    'archived and draft products show it too' => [function () {
        sharingProduct('PRD-JKT-002', ['status' => ProductStatus::Archived]);
        sharingProduct('PRD-JKT-003', ['status' => ProductStatus::Draft]);
    }, ['PRD-JKT-002', 'PRD-JKT-003'], 0],
    'a product made after the barcode shows it and has nothing missing' => [fn () => sharingProduct('PRD-JKT-002'), ['PRD-JKT-001'], 0],
    'a draft Denim Jacket reads like an active one' => [function (Product $jacket) {
        $jacket->update(['status' => ProductStatus::Draft]);
    }, [], 0],
]);
