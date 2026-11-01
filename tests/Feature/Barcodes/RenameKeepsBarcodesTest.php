<?php

use App\Actions\Barcodes\GenerateBarcodes;
use App\Domain\Barcodes\NameKey;
use App\Models\Barcode;
use App\Models\BarcodeName;
use App\Models\Colour;
use App\Models\Product;
use App\Models\Size;
use App\Services\Products\ProductBarcodes;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| A rename keeps the barcodes (INV-8-d, AC-4, ADR-0020)
|--------------------------------------------------------------------------
|
| A product owns no barcode: it looks up the numbers of its name. Saving a product under a new name gives the new name the barcodes of the
| old one for the colours and sizes the product has once the save is done; the old name keeps them, so PRD-JKT-002, still "Denim Jacket",
| keeps its own. The Denim Jacket (PRD-JKT-001) has Indigo and Light wash here, each with a barcode in all four sizes. The barcodes the
| tests plant use the factory's numbers (outside the in-store range), so the real Generate can go on after them.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    $this->jacket = keepColours(demoProduct('PRD-JKT-001'), 'IND', 'LWH');
    $this->twin = keepColours(Product::factory()->create(['sku' => 'PRD-JKT-002', 'name' => 'Denim Jacket', 'category' => 'apparel']), 'IND', 'LWH');

    foreach (['IND', 'LWH'] as $code) {
        foreach (['S', 'M', 'L', 'XL'] as $size) {
            keepMake('Denim Jacket', $code, $size);
        }
    }

    $this->baseline = keepStored('Denim Jacket');
});

/** Gives the product exactly these colours (by code). */
function keepColours(Product $product, string ...$codes): Product
{
    $product->colours()->syncWithPivotValues(Colour::query()->whereIn('code', $codes)->pluck('id')->all(), ['workshop_id' => $product->workshop_id]);

    return $product;
}

/** A barcode of the current workshop that stands for this name in this colour (code) and size (label). */
function keepMake(string $name, string $colourCode, string $sizeLabel): Barcode
{
    $barcode = Barcode::factory()->create(['colour_id' => demoColour($colourCode)->id, 'size_id' => Size::query()->where('label', $sizeLabel)->value('id')]);
    BarcodeName::factory()->create(['barcode_id' => $barcode->id, 'name_key' => NameKey::of($name)]);

    return $barcode;
}

/**
 * What is stored for a name: "IND S" => the number, in colour and size order.
 *
 * @return array<string, string>
 */
function keepStored(string $name): array
{
    return BarcodeName::query()->where('name_key', NameKey::of($name))->with(['barcode', 'colour', 'size'])->get()
        ->sortBy(fn (BarcodeName $row): string => sprintf('%05d-%05d', $row->colour->position, $row->size_id))
        ->mapWithKeys(fn (BarcodeName $row): array => ["{$row->colour->code} {$row->size->label}" => $row->barcode->number])->all();
}

/**
 * What the product page shows for a product: "IND S" => the number or null, for every colour it has.
 *
 * @return array<string, string|null>
 */
function keepShown(Product $product): array
{
    actingAs(test()->user);
    $shown = [];

    foreach (app(ProductBarcodes::class)->of($product)->colours as $colour) {
        foreach ($colour['variants'] as $variant) {
            $shown["{$colour['colour']->code} {$variant['size']->label}"] = $variant['barcode'];
        }
    }

    return $shown;
}

/** Edit product's save of a product under this name with exactly these colours (by code); the composition differs from the seeded one. */
function keepSave(Product $product, string $name, array $codes)
{
    return actingAs(test()->user)->from(route('products.edit', $product))->put(route('products.update', $product), validProductPayload([
        'sku' => $product->sku, 'name' => $name, 'status' => 'active',
        'colours' => Colour::query()->whereIn('code', $codes)->pluck('id')->all(),
    ]));
}

/** The numbers of a name restricted to some colours. */
function keepOnly(array $numbers, array $codes): array
{
    return array_filter($numbers, fn (string $variant): bool => in_array(explode(' ', $variant)[0], $codes, true), ARRAY_FILTER_USE_KEY);
}

it('carries the barcodes of the old name to the new one for the colours it keeps, and nothing for a change of capitals (AC-4)', function (string $name, array $codes, array $carried, ?Closure $then) {
    keepSave($this->jacket, $name, $codes)->assertSessionHasNoErrors();

    $newKey = keepStored($name);

    // The new name has the carried numbers (and, for a change of capitals or spaces only, no row more); the old name keeps all of its own.
    expect($newKey)->toBe(keepOnly($this->baseline, $carried))
        ->and(BarcodeName::query()->count())->toBe(8 + (NameKey::of($name) === 'denim jacket' ? 0 : count($newKey)))
        ->and(keepStored('Denim Jacket'))->toBe($this->baseline)
        // The product shows them, and PRD-JKT-002, still "Denim Jacket", keeps its own.
        ->and(keepShown($this->jacket->fresh()))->toBe(keepOnly($this->baseline, $codes))
        ->and(keepShown($this->twin))->toBe($this->baseline);

    $then === null || $then->call($this, $name);
})->with([
    'a new name' => ['Trucker Jacket', ['IND', 'LWH'], ['IND', 'LWH'], null],
    'only other capitals and spaces' => ['denim  JACKET', ['IND', 'LWH'], ['IND', 'LWH'], null],
    'a colour taken off in the same save is not carried, and added back later shows no barcode under the new name' => ['Trucker Jacket', ['IND'], ['IND'], function (string $name) {
        keepSave($this->jacket, $name, ['IND', 'LWH'])->assertSessionHasNoErrors();

        expect(keepShown($this->jacket->fresh()))->toBe([...keepStored($name), 'LWH S' => null, 'LWH M' => null, 'LWH L' => null, 'LWH XL' => null])
            ->and(keepStored('Denim Jacket'))->toBe($this->baseline);
    }],
    'a new colour shares the barcodes of its colour under the new name' => ['Trucker Jacket', ['IND', 'LWH'], ['IND', 'LWH'], function (string $name) {
        // It then gets Navy; Navy S is generated under the current name; a second Trucker Jacket with Navy shows the same number.
        keepSave($this->jacket, $name, ['IND', 'LWH', 'NAV'])->assertSessionHasNoErrors();
        $ask = [['colour_id' => demoColour('NAV')->id, 'size_id' => Size::query()->where('label', 'S')->value('id')]];
        app(GenerateBarcodes::class)->handle($this->jacket->fresh(), $ask, $this->user);

        $second = keepColours(Product::factory()->create(['sku' => 'PRD-JKT-009', 'name' => 'Trucker Jacket', 'category' => 'apparel']), 'NAV');

        expect(keepShown($second)['NAV S'])->toBe(keepStored($name)['NAV S'])
            ->and(keepShown($this->jacket->fresh())['NAV S'])->toBe(keepStored($name)['NAV S'])
            ->and(keepStored('Denim Jacket'))->toBe($this->baseline);
    }],
]);

it('shows the old numbers again when a colour is added back under the same name, and a deleted product\'s barcodes on a new product of that name (AC-4)', function () {
    // Light wash taken off (nothing else changes) keeps its barcodes under the name; added back, it shows them.
    keepSave($this->jacket, 'Denim Jacket', ['IND'])->assertSessionHasNoErrors();

    expect(array_keys(keepShown($this->jacket->fresh())))->toBe(['IND S', 'IND M', 'IND L', 'IND XL'])
        ->and(keepStored('Denim Jacket'))->toBe($this->baseline);

    keepSave($this->jacket, 'Denim Jacket', ['IND', 'LWH'])->assertSessionHasNoErrors();

    expect(keepShown($this->jacket->fresh()))->toBe($this->baseline);

    // Delete product keeps the barcodes: a new product named Denim Jacket, in the same colours, shows them.
    $this->jacket->delete();
    $new = keepColours(Product::factory()->create(['sku' => 'PRD-JKT-050', 'name' => 'Denim Jacket', 'category' => 'apparel']), 'IND', 'LWH');

    expect(keepShown($new))->toBe($this->baseline)
        ->and(BarcodeName::query()->count())->toBe(8);
});
