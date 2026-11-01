<?php

use App\Actions\Products\UpdateProduct;
use App\Domain\Barcodes\NameKey;
use App\Models\Barcode;
use App\Models\BarcodeName;
use App\Models\Colour;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\Size;
use App\Services\Products\ProductBarcodes;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| A rename that would make two different numbers meet is refused (INV-8-d, AC-5, ADR-0020)
|--------------------------------------------------------------------------
|
| Renaming the Denim Jacket (PRD-JKT-001) to a name that already has its own barcode in a colour and size the jacket has after the save,
| where the Denim Jacket has a different one, saves nothing and puts one sentence on `name`. The same number under both names, a number
| under one name only, and a clash on a colour taken off in the same save are no clash. The barcodes the tests plant use the factory's
| numbers. A barcode spec is "IND S" (colour code, size label).
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    $this->jacket = demoProduct('PRD-JKT-001');
});

/** Gives the product exactly these colours (by code). */
function clashColours(Product $product, array $codes): void
{
    $product->colours()->syncWithPivotValues(Colour::query()->whereIn('code', $codes)->pluck('id')->all(), ['workshop_id' => $product->workshop_id]);
}

/** A barcode of the current workshop for a variant spec ("IND S") that stands for these names (`[]` for none yet). */
function clashMake(string $variant, string ...$names): Barcode
{
    [$code, $size] = explode(' ', $variant);
    $barcode = Barcode::factory()->create(['colour_id' => demoColour($code)->id, 'size_id' => Size::query()->where('label', $size)->value('id')]);

    foreach ($names as $name) {
        BarcodeName::factory()->create(['barcode_id' => $barcode->id, 'name_key' => NameKey::of($name)]);
    }

    return $barcode;
}

/** What a refused save must leave alone: name, composition and colours. */
function clashState(Product $jacket): array
{
    return [
        $jacket->fresh()->name,
        ProductComponent::query()->where('product_id', $jacket->id)->orderBy('position')->pluck('quantity', 'material_id')->all(),
        $jacket->colours()->pluck('colours.code')->all(),
        BarcodeName::query()->count(),
    ];
}

/** The Edit product save of the jacket under this name, with exactly these colours (by code). */
function clashSave(Product $jacket, string $name, array $codes)
{
    return actingAs(test()->user)->from(route('products.edit', $jacket))->put(route('products.update', $jacket), validProductPayload([
        'sku' => $jacket->sku, 'name' => $name, 'status' => 'active',
        'colours' => Colour::query()->whereIn('code', $codes)->pluck('id')->all(),
    ]));
}

it('refuses a rename that makes two different numbers meet, with the card\'s sentence, and renames when nothing clashes (AC-5)', function (array $denim, array $trucker, array $shared, array $saved, string $typed, ?string $refusal) {
    clashColours($this->jacket, ['IND', 'LWH', 'BLK', 'NAV', 'LIL']);
    array_map(fn (string $variant): Barcode => clashMake($variant, 'Denim Jacket'), $denim);
    array_map(fn (string $variant): Barcode => clashMake($variant, 'Trucker Jacket'), $trucker);
    array_map(fn (string $variant): Barcode => clashMake($variant, 'Denim Jacket', 'Trucker Jacket'), $shared);
    $before = clashState($this->jacket);

    $response = clashSave($this->jacket, $typed, $saved);

    if ($refusal !== null) {
        $response->assertRedirect(route('products.edit', $this->jacket))->assertSessionHasErrors(['name' => $refusal]);
        expect(clashState($this->jacket))->toBe($before);

        return;
    }

    // It goes through; the product shows the new name's own number where it has one, else the one it carried over.
    $response->assertSessionHasNoErrors();
    $shown = [];

    foreach (app(ProductBarcodes::class)->of($this->jacket->fresh())->colours as $colour) {
        foreach ($colour['variants'] as $variant) {
            $shown["{$colour['colour']->code} {$variant['size']->label}"] = $variant['barcode'];
        }
    }

    expect($this->jacket->fresh()->name)->toBe($typed);

    foreach ($shown as $variant => $number) {
        [$code, $size] = explode(' ', $variant);
        $for = fn (string $name): ?string => BarcodeName::query()->where('name_key', NameKey::of($name))->where('colour_id', demoColour($code)->id)
            ->where('size_id', Size::query()->where('label', $size)->value('id'))->with('barcode')->first()?->barcode->number;

        expect($number)->toBe($for($typed) ?? $for('Denim Jacket'));
    }
})->with([
    'two variants of one colour' => [['IND S', 'IND L', 'IND M'], ['IND S', 'IND L'], [], ['IND', 'LWH'], 'Trucker Jacket',
        'Trucker Jacket already has its own barcodes for Indigo S and L, so Denim Jacket can\'t take that name. Choose another name.'],
    'one variant' => [['IND S', 'IND M'], ['IND S', 'IND L'], [], ['IND'], 'Trucker Jacket',
        'Trucker Jacket already has its own barcode for Indigo S, so Denim Jacket can\'t take that name. Choose another name.'],
    'five colours name three, then "and 2 more colours"' => [['BLK S', 'NAV S', 'IND S', 'LWH S', 'LIL S'], ['BLK S', 'NAV S', 'IND S', 'LWH S', 'LIL S'], [], ['IND', 'LWH', 'BLK', 'NAV', 'LIL'], 'Trucker Jacket',
        'Trucker Jacket already has its own barcodes for Black S, Navy S, Indigo S, and 2 more colours, so Denim Jacket can\'t take that name. Choose another name.'],
    'four colours name three, then "and 1 more colour"' => [['BLK S', 'NAV S', 'IND S', 'LWH S'], ['BLK S', 'NAV S', 'IND S', 'LWH S'], [], ['IND', 'LWH', 'BLK', 'NAV'], 'Trucker Jacket',
        'Trucker Jacket already has its own barcodes for Black S, Navy S, Indigo S, and 1 more colour, so Denim Jacket can\'t take that name. Choose another name.'],
    'the new name as typed with the extra spaces taken out' => [['IND S'], ['IND S'], [], ['IND'], 'Trucker  Jacket ',
        'Trucker Jacket already has its own barcode for Indigo S, so Denim Jacket can\'t take that name. Choose another name.'],
    'a barcode of the new name only, in a colour the Denim Jacket has none' => [['IND S'], ['LWH S'], [], ['IND', 'LWH'], 'Trucker Jacket', null],
    'the same number under both names' => [[], ['LWH S'], ['IND S', 'IND L'], ['IND', 'LWH'], 'Trucker Jacket', null],
    'a clash on a colour taken off in the same save' => [['LWH S', 'IND M'], ['LWH S', 'IND S'], [], ['IND'], 'Trucker Jacket', null],
    'a change of capitals or spaces only is no rename' => [['IND S'], ['IND S'], [], ['IND', 'LWH'], 'denim  JACKET', null],
]);

it('checks again under the product\'s and the workshop\'s locks, when the clash was made after the request, and saves nothing (AC-5)', function () {
    actingAs($this->user);
    clashColours($this->jacket, ['IND']);
    clashMake('IND S', 'Denim Jacket');
    $stale = demoProduct('PRD-JKT-001');

    // The request passed with no Trucker Jacket barcode; before the save, another person made one.
    clashMake('IND S', 'Trucker Jacket');
    $before = clashState($this->jacket);

    $save = fn () => app(UpdateProduct::class)->handle(
        $stale,
        validProductPayload(['sku' => $stale->sku, 'name' => 'Trucker Jacket', 'colours' => [demoColour('IND')->id]]),
        $this->user,
    );

    expect($save)->toThrow(fn (ValidationException $e) => expect($e->errors())->toBe([
        'name' => ['Trucker Jacket already has its own barcode for Indigo S, so Denim Jacket can\'t take that name. Choose another name.'],
    ]));
    expect(clashState($this->jacket))->toBe($before);
});
