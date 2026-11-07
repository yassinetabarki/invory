<?php

use App\Enums\ProductionBatchStatus;
use App\Models\Colour;
use App\Models\ProductionBatch;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Edit product while units are in production (INV-5-g, AC-7)
|--------------------------------------------------------------------------
|
| A colour with units in production can't be taken off its product. The shelf sentence wins when a colour has both (the human's answer,
| 2026-11-02); the existing refusals stay. (The category lock for sizes went with INV-18-c: a category changes none of a product's sizes.)
| INV-3's catalogue: the Denim Jacket in Black, Indigo and Light wash, Light wash with nothing on the shelf.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    ['jacket' => $this->jacket] = restockCatalogue();
});

/** Light wash batches in production: size label → units. */
function lightWashInProduction(object $test, array $bySize, ProductionBatchStatus $status = ProductionBatchStatus::InProduction): void
{
    foreach (sizeIds($bySize) as $sizeId => $units) {
        ProductionBatch::factory()->create([
            'product_id' => $test->jacket->id, 'colour_id' => demoColour('LWH')->id, 'size_id' => $sizeId, 'units' => $units, 'status' => $status,
        ]);
    }
}

/** Saves the jacket from its edit page with these colours (names) and overrides. */
function saveJacket(object $test, array $colours, array $overrides = []): TestResponse
{
    return actingAs($test->user)
        ->from(route('products.edit', $test->jacket))
        ->put(route('products.update', $test->jacket), validProductPayload([
            'sku' => $test->jacket->sku, 'name' => $test->jacket->name, 'status' => 'active',
            'colours' => Colour::query()->whereIn('name', $colours)->pluck('id')->all(), ...$overrides,
        ]));
}

it('refuses to take off a colour with units in production (AC-7)', function (array $bySize, string $message) {
    lightWashInProduction($this, $bySize);

    saveJacket($this, ['Black', 'Indigo'])->assertSessionHasErrors(['colours.removed.'.demoColour('LWH')->id => $message]);

    expect($this->jacket->colours()->pluck('colours.name')->all())->toContain('Light wash');
})->with([
    '3 units' => [['L' => 2, 'XL' => 1], "Light wash has 3 units in production, so it can't be removed from Denim Jacket."],
    '1 unit' => [['L' => 1], "Light wash has 1 unit in production, so it can't be removed from Denim Jacket."],
]);

it('shows the shelf sentence when a colour has units on the shelf and in production', function () {
    ProductionBatch::factory()->create(['product_id' => $this->jacket->id, 'colour_id' => demoColour('IND')->id, 'units' => 5]);

    saveJacket($this, ['Black', 'Light wash'])->assertSessionHasErrors([
        'colours.removed.'.demoColour('IND')->id => "Indigo has 6 units in stock, so it can't be removed from Denim Jacket. Set its stock to 0 first.",
    ]);
});

it('takes off a colour whose batches were all put in stock or undone', function () {
    lightWashInProduction($this, ['L' => 2], ProductionBatchStatus::InStock);
    lightWashInProduction($this, ['XL' => 1], ProductionBatchStatus::Undone);

    saveJacket($this, ['Black', 'Indigo'])->assertSessionHasNoErrors();

    expect($this->jacket->colours()->pluck('colours.name')->all())->not->toContain('Light wash');
});
