<?php

use App\Domain\Barcodes\NameKey;
use App\Models\Barcode;
use App\Models\BarcodeName;
use App\Models\Product;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

/*
|--------------------------------------------------------------------------
| Scan or type a barcode on New restock (INV-3-e, AC-4)
|--------------------------------------------------------------------------
|
| `GET restocks/barcode?number=` says which products, colour and size a number stands for (ADR-0020: a number stands for names, not
| for one product). The card's catalogue; the numbers are given straight in the tables.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    ['jacket' => $this->jacket, 'jeans' => $this->jeans, 'tote' => $this->tote] = restockCatalogue();
});

/** A number standing for this colour and size of every product named `$name`. */
function numberFor(string $number, string $name, string $colourCode, string $sizeLabel, string $list = 's_to_xl'): Barcode
{
    $size = array_key_first(sizeIds([$sizeLabel => 0], $list));
    $barcode = Barcode::factory()->create(['number' => $number, 'colour_id' => demoColour($colourCode)->id, 'size_id' => $size]);
    BarcodeName::factory()->create(['barcode_id' => $barcode->id, 'name_key' => NameKey::of($name), 'colour_id' => $barcode->colour_id, 'size_id' => $size]);

    return $barcode;
}

function lookUp(string $number): array
{
    return test()->actingAs(test()->user)->getJson(route('restocks.barcode', ['number' => $number]))->assertOk()->json();
}

it('finds the product, colour and size of a scanned label, with its line (AC-4)', function () {
    numberFor('2000000000015', 'Denim Jacket', 'IND', 'M');

    $found = lookUp(' 2000000000015 ');

    expect($found['result'])->toBe('found')
        ->and($found['colour_id'])->toBe(demoColour('IND')->id)
        ->and($found['size_id'])->toBe(array_key_first(sizeIds(['M' => 0])))
        ->and(collect($found['matches'])->pluck('product.sku')->all())->toBe(['PRD-JKT-001'])
        ->and($found['matches'][0]['stock']['units'])->toBe(10);
});

it('finds every product of a shared name, by SKU, any status (AC-4)', function () {
    $second = Product::factory()->archived()->create(['name' => 'Denim Jacket', 'sku' => 'PRD-JKT-002', 'category' => 'apparel']);
    $second->colours()->sync([demoColour('IND')->id => ['workshop_id' => $second->workshop_id]]);
    numberFor('2000000000022', 'Denim Jacket', 'IND', 'M');

    expect(collect(lookUp('2000000000022')['matches'])->pluck('product.sku')->all())->toBe(['PRD-JKT-001', 'PRD-JKT-002']);
});

it('answers with the card\'s message when the number finds no product to restock (AC-4)', function (Closure $setUp, string $typed, string $result, string $message) {
    $setUp($this);

    expect(lookUp($typed))->toBe(['result' => $result, 'message' => $message]);
})->with([
    'not 13 digits' => [fn () => null, '12345', 'invalid', 'A barcode has 13 digits. Check the number.'],
    'letters' => [fn () => null, '200000000001A', 'invalid', 'A barcode has 13 digits. Check the number.'],
    'unknown' => [fn () => null, '2000000000992', 'unknown', 'No product has the barcode 2000000000992. Check the label, or enter the units below.'],
    'its colour taken off' => [
        function ($test) {
            numberFor('2000000000077', 'Denim Jacket', 'LWH', 'L');
            $test->jacket->colours()->detach(demoColour('LWH')->id);
        },
        '2000000000077', 'colour_gone', '2000000000077 is Light wash L of Denim Jacket, which no longer comes in Light wash.',
    ],
    'its size no longer on the product' => [
        fn () => numberFor('2000000000088', 'Leather Tote', 'CAM', 'M'),
        '2000000000088', 'size_gone', '2000000000088 is Camel M of Leather Tote, which no longer comes in size M.',
    ],
    'its only product deleted' => [
        function ($test) {
            numberFor('2000000000046', 'Denim Jeans 5-pocket', 'IND', 'S');
            $test->jeans->delete();
        },
        '2000000000046', 'unknown', 'No product has the barcode 2000000000046. Check the label, or enter the units below.',
    ],
]);

it('treats another workshop\'s number as unknown', function () {
    CurrentWorkshop::run(Workshop::factory()->create(), fn () => Barcode::factory()->create(['number' => '2000000000015']));

    expect(lookUp('2000000000015')['result'])->toBe('unknown');
});

it('names a "One size" product\'s variant by its colour', function () {
    numberFor('2000000000053', 'Leather Tote', 'CAM', 'One size', 'one_size');

    $found = lookUp('2000000000053');

    expect($found['result'])->toBe('found')
        ->and($found['matches'][0]['one_size'])->toBeTrue();
});

it('sends a guest to the login page', function () {
    $this->get(route('restocks.barcode', ['number' => '2000000000015']))->assertRedirect(route('login'));
});
