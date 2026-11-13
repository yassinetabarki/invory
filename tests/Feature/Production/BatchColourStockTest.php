<?php

use App\Actions\Materials\MaterialStockWriter;
use App\Enums\MaterialCostMode;
use App\Enums\MaterialStockMode;
use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\MaterialColour;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductComponentQuantity;
use App\Models\ProductionBatch;
use App\Models\ProductionCut;
use App\Models\ProductionCutMaterial;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Inventory\StockLedgerVerifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Tests\Support\VariantJacket;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Start batch and Undo batch per colour of a material (INV-21-q, AC-14, AC-18, ADR-0029 section 6)
|--------------------------------------------------------------------------
|
| INV-16's Denim Jacket (Indigo and Black, S · M · L · XL; denim by colour and size S 1.6 · M 1.8 · L 2.0 · XL 2.2 with 10 % waste) on ONE
| Denim 12oz kept per colour: Indigo 250 m, Black 40 m, saved through the material form so its ledger opens with its stock. Button, thread
| and label are one stock, as in INV-16. The maths is in tests/Unit/Domain/Stock/CuttingColourStockTest; these tests check what the
| Actions and the preview write and say.
*/

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

/**
 * The jacket with its two one-stock denim lines swapped for one line on a Denim 12oz kept per colour (Indigo 250 m, Black 40 m, one cost).
 *
 * @return array{jacket: VariantJacket, denim: Material, line: ProductComponent}
 */
function bcsJacket(): array
{
    $jacket = VariantJacket::create();
    $lineIds = ProductComponent::query()->whereIn('material_id', [$jacket->materials['indigo_denim']->id, $jacket->materials['black_denim']->id])->pluck('id');
    ProductComponentQuantity::query()->whereIn('product_component_id', $lineIds)->delete();
    ProductComponent::query()->whereIn('id', $lineIds)->delete();

    actingAs(test()->user)->post(route('materials.store'), [
        ...Arr::except(validMaterialPayload(['sku' => 'FAB-BCS-DEN', 'name' => 'Denim 12oz']), ['quantity_on_hand', 'unit_cost']),
        'stock_mode' => 'per_colour',
        'cost_mode' => 'same',
        'unit_cost' => '8.5',
        'colours' => [
            ['colour_id' => $jacket->colours['Indigo'], 'quantity_on_hand' => '250', 'unit_cost' => ''],
            ['colour_id' => $jacket->colours['Black'], 'quantity_on_hand' => '40', 'unit_cost' => ''],
        ],
    ])->assertSessionHasNoErrors();

    $denim = Material::query()->where('sku', 'FAB-BCS-DEN')->firstOrFail();
    $line = ProductComponent::factory()->byVariant()->create([
        'product_id' => $jacket->product->id, 'material_id' => $denim->id, 'waste_percent' => '10.00', 'position' => 0, 'notes' => null,
    ]);
    bcsCells($jacket, $line, ['Indigo', 'Black']);

    return ['jacket' => $jacket, 'denim' => $denim, 'line' => $line];
}

/** @param  list<string>  $colours */
function bcsCells(VariantJacket $jacket, ProductComponent $line, array $colours): void
{
    foreach ($colours as $colour) {
        foreach (['S' => '1.6000', 'M' => '1.8000', 'L' => '2.0000', 'XL' => '2.2000'] as $label => $quantity) {
            ProductComponentQuantity::factory()->create([
                'product_component_id' => $line->id, 'colour_id' => $jacket->colours[$colour], 'size_id' => $jacket->sizes[$label], 'quantity' => $quantity,
            ]);
        }
    }
}

/**
 * The Start batch body of a product: every colour (list order) × the given sizes, '' unless typed.
 *
 * @param  array<string, int>  $sizes  size ids by label
 * @param  array<string, array<string, int>>  $typed  colour name → size label → units
 * @return array{product_id: int, units: array<int, array<int, int|string>>}
 */
function bcsBody(Product $product, array $sizes, array $typed): array
{
    $units = [];

    foreach ($product->colours()->get() as $colour) {
        foreach ($sizes as $label => $sizeId) {
            $units[$colour->id][$sizeId] = $typed[$colour->name][$label] ?? '';
        }
    }

    return ['product_id' => $product->id, 'units' => $units];
}

/** The preview's query: the fields typed above 0 only. */
function bcsGrid(array $body): array
{
    return array_filter(array_map(fn (array $bySize): array => array_filter($bySize, fn ($units): bool => (int) $units > 0), $body['units']));
}

/** The stock of every material and of every colour row, as it is now. */
function bcsStock(): array
{
    return [
        Material::query()->orderBy('id')->pluck('quantity_on_hand', 'id')->all(),
        MaterialColour::query()->orderBy('id')->pluck('quantity_on_hand', 'id')->all(),
    ];
}

/** Starts the grid through the real route and returns the batches in number order. */
function bcsStart(Product $product, array $sizes, array $typed): array
{
    actingAs(test()->user)->post(route('progress.batches.store'), bcsBody($product, $sizes, $typed))->assertSessionHasNoErrors();

    return ProductionBatch::query()->where('product_id', $product->id)->orderBy('number')->get()->all();
}

/** The `cutting` or `cutting_return` entries of a material, in order, as [colour id, quantity, before, after]. */
function bcsEntries(Material $material, StockMovementType $type): array
{
    return StockMovement::query()->where('material_id', $material->id)->where('type', $type)->orderBy('id')->get()
        ->map(fn (StockMovement $entry): array => [$entry->colour_id, $entry->quantity, $entry->quantity_before, $entry->quantity_after])->all();
}

/** The ledger drifts of one material (the others of the jacket have no opening entry). */
function bcsDrifts(Material $material): array
{
    return array_values(array_filter(app(StockLedgerVerifier::class)->verify()['drifts'], fn (array $drift): bool => $drift['material_id'] === $material->id));
}

it('starts per colour and undoes the Indigo batch back into Indigo only (AC-14)', function () {
    ['jacket' => $jacket, 'denim' => $denim] = bcsJacket();
    [$indigo, $black] = [$jacket->colours['Indigo'], $jacket->colours['Black']];
    $typed = ['Indigo' => ['M' => 6, 'L' => 10], 'Black' => ['S' => 5]];

    // The materials table has one row per material and colour; a one-stock material has one row without a colour.
    $rows = actingAs($this->user)->getJson(route('progress.products.materials', [$jacket->product, 'units' => bcsGrid(bcsBody($jacket->product, $jacket->sizes, $typed))]))
        ->assertOk()->assertJsonPath('short_message', null)->json('rows');
    expect(array_map(fn (array $row): array => [$row['material']['name'], $row['colour']['name'] ?? null, $row['quantity'], $row['in_stock'], $row['missing']], $rows))->toBe([
        ['Denim 12oz', 'Indigo', '33.8800', '250.0000', false],
        ['Denim 12oz', 'Black', '8.8000', '40.0000', false],
        ['Metal shank button 17mm', null, '168.0000', '900.0000', false],
        ['Polyester thread', null, '2646.0000', '20000.0000', false],
        ['Woven brand label', null, '21.0000', '400.0000', false],
    ]);

    $batches = bcsStart($jacket->product, $jacket->sizes, $typed);

    // Indigo takes 6 × 1.98 + 10 × 2.2 = 33.88 m, Black 5 × 1.76 = 8.8 m: Indigo 216.12, Black 31.2.
    $colourStock = fn (): array => MaterialColour::query()->where('material_id', $denim->id)->pluck('quantity_on_hand', 'colour_id')->all();
    expect($colourStock())->toEqual([$indigo => '216.1200', $black => '31.2000'])
        ->and($denim->fresh()->quantity_on_hand)->toBe('247.3200')
        ->and(bcsEntries($denim, StockMovementType::Cutting))->toBe([
            [$indigo, '33.8800', '250.0000', '216.1200'],
            [$black, '8.8000', '40.0000', '31.2000'],
        ])
        ->and(StockMovement::query()->where('type', StockMovementType::Cutting)->where('material_id', $jacket->materials['button']->id)->value('colour_id'))->toBeNull()
        ->and(ProductionCutMaterial::query()->where('material_id', $denim->id)->orderBy('id')->get()->map(fn ($take): array => [$take->colour_id, $take->quantity_taken])->all())
        ->toBe([[$indigo, '33.8800'], [$black, '8.8000']])
        ->and(ProductionCut::query()->sole()->materials()->whereNull('colour_id')->count())->toBe(3)
        ->and(bcsDrifts($denim))->toBe([]);

    // Undoing Indigo L (10 units) gives back 22 m to Indigo and nothing to Black.
    $indigoL = collect($batches)->first(fn (ProductionBatch $batch): bool => $batch->colour_id === $indigo && $batch->size_id === $jacket->sizes['L']);
    $undo = actingAs($this->user)->getJson(route('progress.batches.undo-preview', $indigoL))->assertOk()->json('rows');
    expect(array_map(fn (array $row): array => [$row['material']['name'], $row['colour']['name'] ?? null, $row['quantity'], $row['not_returned']], $undo))->toBe([
        ['Denim 12oz', 'Indigo', '22.0000', null],
        ['Metal shank button 17mm', null, '80.0000', null],
        ['Polyester thread', null, '1260.0000', null],
        ['Woven brand label', null, '10.0000', null],
    ]);

    actingAs($this->user)->post(route('progress.batches.undo', $indigoL))->assertSessionHasNoErrors();

    expect($colourStock())->toEqual([$indigo => '238.1200', $black => '31.2000'])
        ->and(bcsEntries($denim, StockMovementType::CuttingReturn))->toBe([[$indigo, '22.0000', '216.1200', '238.1200']])
        ->and($jacket->materials['button']->fresh()->quantity_on_hand)->toBe('812.0000')
        ->and(bcsDrifts($denim))->toBe([]);

    // The preview reads the colours once, however many colours its rows name (a resource never queries): 1 colour vs 3 (Light wash missing).
    $jacket->product->colours()->attach($jacket->colours['Light wash'], ['workshop_id' => $jacket->workshop->id]);
    bcsCells($jacket, ProductComponent::query()->where('material_id', $denim->id)->firstOrFail(), ['Light wash']);
    $queries = function (array $typed) use ($jacket): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        actingAs($this->user)->getJson(route('progress.products.materials', [$jacket->product, 'units' => bcsGrid(bcsBody($jacket->product, $jacket->sizes, $typed))]))->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };
    $one = $queries(['Indigo' => ['M' => 1]]);
    $three = $queries(['Indigo' => ['M' => 1], 'Black' => ['S' => 1], 'Light wash' => ['S' => 1]]);
    expect($three)->toBe($one);
});

it('refuses a short start with the card\'s sentence per colour, and writes nothing (AC-14)', function (string $case, string $message, ?array $missingRow) {
    ['jacket' => $jacket, 'denim' => $denim, 'line' => $line] = bcsJacket();
    $product = $jacket->product;
    $sizes = $jacket->sizes;
    $typed = ['Indigo' => ['M' => 6, 'L' => 10], 'Black' => ['S' => 30]]; // Black needs 52.8 m of its 40

    if ($case === 'Black and the button') {
        $jacket->materials['button']->update(['quantity_on_hand' => '100.0000']);
    } elseif ($case === 'Black and Light wash') {
        // Light wash is on the product, with denim cells, but the denim has no Light wash: a stock of 0.
        $product->colours()->attach($jacket->colours['Light wash'], ['workshop_id' => $jacket->workshop->id]);
        bcsCells($jacket, $line, ['Light wash']);
        $typed = ['Black' => ['S' => 30], 'Light wash' => ['S' => 5]];
    } elseif ($case === 'same lines') {
        // A product with only "Same for every variant" lines on the per-colour denim is mixed too: no "at most N".
        $sizes = Arr::only($jacket->sizes, ['S', 'M']);
        $product = Product::factory()->withSizes(['S', 'M'])->withComponents([['material' => $denim, 'quantity' => '1.8000', 'waste_percent' => '10.00']])
            ->create(['workshop_id' => $jacket->workshop->id]);
        $product->colours()->syncWithPivotValues([$jacket->colours['Indigo'], $jacket->colours['Black']], ['workshop_id' => $jacket->workshop->id]);
        $typed = ['Indigo' => ['S' => 5], 'Black' => ['S' => 30]]; // Black 59.4 m of its 40
    } elseif ($case === 'one colour and size') {
        $typed = ['Black' => ['XL' => 17]]; // 2.42 m each: 16 at most
    }

    $body = bcsBody($product, $sizes, $typed);
    $before = bcsStock();

    actingAs($this->user)->post(route('progress.batches.store'), $body)->assertSessionHasErrors(['units' => $message]);

    expect(bcsStock())->toBe($before)
        ->and([ProductionBatch::query()->count(), ProductionCut::query()->count(), StockMovement::query()->where('type', StockMovementType::Cutting)->count()])->toBe([0, 0, 0]);

    // The window says the same, and a colour the material lacks reads "No stock", short by the whole need.
    $preview = actingAs($this->user)->getJson(route('progress.products.materials', [$product, 'units' => bcsGrid($body)]))->assertOk()->assertJsonPath('short_message', $message);
    $missing = collect($preview->json('rows'))->firstWhere('missing', true);
    expect($missing === null ? null : [$missing['colour']['name'], $missing['in_stock'], $missing['short_by']])->toBe($missingRow);
})->with([
    'Black' => ['Black', 'Short on Denim 12oz in Black. Restock it first, or lower the units.', null],
    'Black and the button' => ['Black and the button', 'Short on Denim 12oz in Black and Metal shank button 17mm. Restock them first, or lower the units.', null],
    'Black and Light wash' => ['Black and Light wash', 'Short on Denim 12oz in Black and Light wash. Restock them first, or lower the units.', ['Light wash', '0.0000', '8.8000']],
    'same lines only' => ['same lines', 'Short on Denim 12oz in Black. Restock it first, or lower the units.', null],
    'one colour and size' => ['one colour and size', 'Not enough stock to cut 17 units. You can cut at most 16 now: Denim 12oz in Black runs short. Restock it first, or cut 16 or fewer.', null],
]);

it('gives back into the stock the material has now, and nothing for a colour it no longer has (AC-14, AC-18)', function (string $case) {
    $writer = app(MaterialStockWriter::class);
    $before = null;

    if ($case === 'started before INV-21' || $case === 'one-stock take, per colour now') {
        // Every material one stock: a take without a colour, as every batch started before INV-21 holds.
        $jacket = VariantJacket::create();
        $denim = $jacket->materials['indigo_denim'];
        $before = bcsStock();
        $batch = bcsStart($jacket->product, $jacket->sizes, ['Indigo' => ['L' => 10]])[0];
    } else {
        ['jacket' => $jacket, 'denim' => $denim] = bcsJacket();
        $typed = $case === 'colour gone' ? ['Black' => ['S' => 5]] : ['Indigo' => ['L' => 10]];
        $batch = bcsStart($jacket->product, $jacket->sizes, $typed)[0];
    }

    [$indigo, $black] = [$jacket->colours['Indigo'], $jacket->colours['Black']];

    if ($case === 'per-colour take, one stock now') {
        DB::transaction(fn () => $writer->switchMode($denim->fresh(), MaterialStockMode::Single, MaterialCostMode::Same));
    } elseif ($case === 'one-stock take, per colour now') {
        // The Indigo denim becomes per colour, with its 228 m under Indigo: the batch's colour.
        DB::transaction(function () use ($writer, $denim, $indigo): void {
            $material = $denim->fresh();
            $writer->switchMode($material, MaterialStockMode::PerColour, MaterialCostMode::Same);
            $writer->addColour($material, $indigo, '228.0000', null);
        });
    } elseif ($case === 'colour gone') {
        DB::transaction(fn () => $writer->removeColour($denim->fresh(), $black));
    }

    $preview = actingAs($this->user)->getJson(route('progress.batches.undo-preview', $batch))->assertOk()->json('rows');
    $denimRow = collect($preview)->first(fn (array $row): bool => $row['material']['id'] === $denim->id);

    actingAs($this->user)->post(route('progress.batches.undo', $batch))->assertSessionHasNoErrors();

    $denim = $denim->fresh();

    expect([
        $denim->quantity_on_hand,
        MaterialColour::query()->where('material_id', $denim->id)->pluck('quantity_on_hand', 'colour_id')->all(),
        bcsEntries($denim, StockMovementType::CuttingReturn),
        [$denimRow['colour']['name'] ?? null, $denimRow['quantity'], $denimRow['not_returned']],
    ])->toBe(match ($case) {
        // The same figures as before: 22 m back into the one stock, an entry without a colour.
        'started before INV-21' => ['250.0000', [], [[null, '22.0000', '228.0000', '250.0000']], [null, '22.0000', null]],
        // Indigo's 22 m go into the one stock (268 m after the switch, Black's 40 included).
        'per-colour take, one stock now' => ['290.0000', [], [[null, '22.0000', '268.0000', '290.0000']], [null, '22.0000', null]],
        // The one-stock take goes into the batch's colour.
        'one-stock take, per colour now' => ['250.0000', [$indigo => '250.0000'], [[$indigo, '22.0000', '228.0000', '250.0000']], ['Indigo', '22.0000', null]],
        // Black is gone from the material: nothing back for it, the row says so; the other materials come back.
        'colour gone' => ['250.0000', [$indigo => '250.0000'], [], ['Black', '8.8000', 'colour_missing']],
    });

    if ($case === 'started before INV-21') {
        expect(bcsStock())->toBe($before);
    }

    if ($case === 'colour gone') {
        expect($jacket->materials['button']->fresh()->quantity_on_hand)->toBe('900.0000');
    }
})->with(['started before INV-21', 'per-colour take, one stock now', 'one-stock take, per colour now', 'colour gone']);
