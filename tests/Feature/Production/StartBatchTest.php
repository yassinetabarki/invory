<?php

use App\Enums\ProductionBatchMoveKind;
use App\Enums\ProductionBatchStatus;
use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductionBatch;
use App\Models\ProductionCounter;
use App\Models\ProductionCut;
use App\Models\ProductionCutMaterial;
use App\Models\ProductionCutVariant;
use App\Models\Size;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Costing\ProductCostingService;
use App\Services\Workshops\CurrentWorkshop;
use Tests\Support\VariantJacket;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Start batch (INV-5-c, AC-2)
|--------------------------------------------------------------------------
|
| `POST progress/batches` takes the product's materials out of stock for the whole total, as one production cut, and writes one batch
| per colour and size, In cutting since today, numbered per workshop. On the demo stock the Denim Jacket can produce 112 (900 buttons
| at 8 a jacket); INV-3's catalogue gives it Black, Indigo and Light wash, with Indigo 2 · 0 · 3 · 1 on the shelf.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    ['jacket' => $this->jacket, 'jeans' => $this->jeans, 'tote' => $this->tote] = restockCatalogue();
});

/** The Start batch body: the product's grid, '' unless typed (colour NAME → size label → units). */
function startBody(Product $product, array $typed): array
{
    return ['product_id' => $product->id, 'units' => restockLine($product, $typed)['units']];
}

/** The window's preview query: the fields typed above 0 only, `units[colourId][sizeId]` (colour NAME → size label → units). */
function previewGrid(Product $product, array $typed): array
{
    $grid = [];

    foreach (restockLine($product, $typed)['units'] as $colourId => $bySize) {
        foreach ($bySize as $sizeId => $value) {
            if ((int) $value > 0) {
                $grid[$colourId][$sizeId] = (int) $value;
            }
        }
    }

    return $grid;
}

/** The Start batch body of INV-16's Denim Jacket: Indigo and Black × S M L XL, '' unless typed (colour name → size label → units). */
function variantJacketBody(VariantJacket $jacket, array $typed): array
{
    $units = [];

    foreach (['Indigo', 'Black'] as $colour) {
        foreach ($jacket->sizes as $label => $sizeId) {
            $units[$jacket->colours[$colour]][$sizeId] = $typed[$colour][$label] ?? '';
        }
    }

    return ['product_id' => $jacket->product->id, 'units' => $units];
}

/** INV-16's Denim Jacket materials, by key → stock now. */
function variantJacketStock(VariantJacket $jacket): array
{
    return array_map(fn (Material $material): string => $material->fresh()->quantity_on_hand, $jacket->materials);
}

/** The four materials of the Denim Jacket, by SKU → stock now. */
function startedJacketStock(): array
{
    return Material::query()->whereIn('sku', ['MAT-DEN-012', 'MAT-BTN-017', 'MAT-THR-PES', 'MAT-LBL-WOV'])->orderBy('sku')->pluck('quantity_on_hand', 'sku')->all();
}

it('starts one batch per colour and size, takes the materials for the total and leaves the shelf alone (AC-2)', function () {
    $before = startedJacketStock();
    $shelf = shelfByColour($this->jacket);

    actingAs($this->user)
        ->post(route('progress.batches.store'), startBody($this->jacket, ['Indigo' => ['M' => 6, 'L' => 10]]))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('batches_started', [
            'product' => 'Denim Jacket',
            'units' => 16,
            'batches' => [
                ['id' => ProductionBatch::query()->where('number', 1)->value('id'), 'number' => 'B-0001', 'colour' => 'Indigo', 'size' => 'M', 'units' => 6],
                ['id' => ProductionBatch::query()->where('number', 2)->value('id'), 'number' => 'B-0002', 'colour' => 'Indigo', 'size' => 'L', 'units' => 10],
            ],
        ]);

    $batches = ProductionBatch::query()->orderBy('number')->with(['colour', 'size'])->get();
    expect($batches->map(fn (ProductionBatch $b): array => [$b->label, $b->colour->name, $b->size->label, $b->units, $b->status, $b->phase_id, $b->phase_since->toDateString()])->all())
        ->toBe([
            ['B-0001', 'Indigo', 'M', 6, ProductionBatchStatus::InProduction, null, '2026-10-08'],
            ['B-0002', 'Indigo', 'L', 10, ProductionBatchStatus::InProduction, null, '2026-10-08'],
        ])
        ->and($batches->pluck('production_cut_id')->unique()->count())->toBe(1);

    $cut = ProductionCut::query()->sole();
    expect($cut->units)->toBe(16)
        ->and($cut->user_id)->toBe($this->user->id)
        ->and(ProductionCutMaterial::query()->count())->toBe(4);

    $after = startedJacketStock();
    expect([
        'MAT-BTN-017' => bcsub($before['MAT-BTN-017'], $after['MAT-BTN-017'], 4),
        'MAT-DEN-012' => bcsub($before['MAT-DEN-012'], $after['MAT-DEN-012'], 4),
        'MAT-LBL-WOV' => bcsub($before['MAT-LBL-WOV'], $after['MAT-LBL-WOV'], 4),
        'MAT-THR-PES' => bcsub($before['MAT-THR-PES'], $after['MAT-THR-PES'], 4),
    ])->toBe(['MAT-BTN-017' => '128.0000', 'MAT-DEN-012' => '31.6800', 'MAT-LBL-WOV' => '16.0000', 'MAT-THR-PES' => '2016.0000']);

    $movements = StockMovement::query()->where('type', StockMovementType::Cutting)->get();
    expect($movements)->toHaveCount(4)
        ->and($movements->pluck('units')->unique()->all())->toBe([16])
        ->and($movements->pluck('product_id')->unique()->all())->toBe([$this->jacket->id])
        ->and($movements->pluck('order_id')->unique()->all())->toBe([null]);

    expect(ProductionBatch::query()->first()->moves()->sole()->kind)->toBe(ProductionBatchMoveKind::Start)
        ->and(shelfByColour($this->jacket))->toBe($shelf)
        ->and(ProductionCounter::query()->sole()->last_batch_number)->toBe(2);

    // "Can produce now" goes from 112 to 96.
    actingAs($this->user)->getJson(route('progress.products.line', $this->jacket))->assertJsonPath('can_produce', 96);
});

it('refuses the whole start when stock is short, with "Short by" in the preview, and writes nothing', function () {
    Material::query()->where('sku', 'MAT-BTN-017')->update(['quantity_on_hand' => '100']);
    // Two colours and sizes, but a product with no By colour and size line: today's sentence, with "at most".
    $message = 'Not enough stock to cut 20 units. You can cut at most 12 now: Metal shank button 17mm runs short. Restock it first, or cut 12 or fewer.';

    actingAs($this->user)
        ->post(route('progress.batches.store'), startBody($this->jacket, ['Indigo' => ['M' => 10, 'L' => 10]]))
        ->assertSessionHasErrors(['units' => $message]);

    $preview = actingAs($this->user)->getJson(route('progress.products.materials', [$this->jacket, 'units' => previewGrid($this->jacket, ['Indigo' => ['M' => 10, 'L' => 10]])]))->assertOk();
    expect($preview->json('short_message'))->toBe($message)
        ->and(collect($preview->json('rows'))->firstWhere('material.name', 'Metal shank button 17mm')['short_by'])->toBe('60.0000');

    expect(ProductionBatch::query()->count())->toBe(0)
        ->and(ProductionCut::query()->count())->toBe(0)
        ->and(StockMovement::query()->where('type', StockMovementType::Cutting)->count())->toBe(0)
        ->and(demoMaterial('MAT-BTN-017')->quantity_on_hand)->toBe('100.0000');
});

it('keeps today\'s full sentence when nothing can be cut', function () {
    Material::query()->where('sku', 'MAT-BTN-017')->update(['quantity_on_hand' => '7']);

    actingAs($this->user)
        ->post(route('progress.batches.store'), startBody($this->jacket, ['Indigo' => ['M' => 20]]))
        ->assertSessionHasErrors(['units' => "Not enough stock to cut 20 units. You can't cut any now: restock Metal shank button 17mm first."]);
});

it('refuses with the card\'s sentence and writes nothing', function (Closure $body, string $field, string $message) {
    actingAs($this->user)->post(route('progress.batches.store'), $body($this))->assertSessionHasErrors([$field => $message]);

    expect(ProductionBatch::query()->count())->toBe(0);
})->with([
    'no product' => [fn ($test) => ['product_id' => null, 'units' => []], 'product_id', 'Choose a product.'],
    'another workshop\'s product' => [
        fn ($test) => ['product_id' => CurrentWorkshop::run(Workshop::factory()->create(), fn () => Product::factory()->create())->id] + startBody($test->jacket, ['Indigo' => ['M' => 1]]),
        'product_id', 'Choose a product.',
    ],
    'nothing typed' => [fn ($test) => startBody($test->jacket, []), 'units', 'Enter at least one unit to start.'],
]);

it('names the variant of a field that isn\'t a whole number from 0 to 1,000,000', function (string $typed) {
    $field = 'units.'.demoColour('IND')->id.'.'.array_key_first(sizeIds(['M' => 0]));

    actingAs($this->user)->post(route('progress.batches.store'), startBody($this->jacket, ['Indigo' => ['M' => $typed]]))
        ->assertSessionHasErrors([$field => 'Indigo M: Enter a whole number from 0 to 1,000,000.']);
})->with(['2.5', '1000001', '-1', 'abc']);

it('refuses a grid whose colours changed since the window opened, and a product deleted meanwhile', function () {
    $body = startBody($this->jacket, ['Indigo' => ['M' => 2]]);
    $this->jacket->colours()->detach(demoColour('LWH')->id);

    actingAs($this->user)->post(route('progress.batches.store'), $body)
        ->assertSessionHasErrors(['units' => 'The colours or sizes of Denim Jacket have changed. Enter the units again.']);

    $body = startBody($this->jacket, ['Indigo' => ['M' => 2]]);
    $this->jacket->delete();

    actingAs($this->user)->post(route('progress.batches.store'), $body)
        ->assertSessionHasErrors(['product_id' => "Denim Jacket was deleted, so it can't be started."]);

    expect(ProductionBatch::query()->count())->toBe(0);
});

it('starts a product with no composition, taking nothing, and draft and archived products', function () {
    ProductComponent::query()->where('product_id', $this->jeans->id)->delete();
    $this->jeans->update(['status' => 'draft']);
    $this->tote->update(['status' => 'archived']);

    actingAs($this->user)->post(route('progress.batches.store'), startBody($this->jeans, ['Indigo' => ['S' => 5]]))->assertSessionHasNoErrors();
    actingAs($this->user)->post(route('progress.batches.store'), startBody($this->tote, ['Camel' => ['One size' => 3]]))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('batches_started.batches', [['id' => ProductionBatch::query()->where('number', 2)->value('id'), 'number' => 'B-0002', 'colour' => 'Camel', 'size' => null, 'units' => 3]]);

    expect(ProductionBatch::query()->count())->toBe(2)
        ->and(ProductionCut::query()->where('product_id', $this->jeans->id)->sole()->materials()->count())->toBe(0);
});

it('numbers per workshop, never twice, and past B-9999 with five digits', function () {
    ProductionCounter::query()->insert(['workshop_id' => $this->user->workshop_id, 'last_batch_number' => 9999, 'created_at' => now(), 'updated_at' => now()]);

    actingAs($this->user)->post(route('progress.batches.store'), startBody($this->jacket, ['Indigo' => ['S' => 1, 'M' => 1]]))->assertSessionHasNoErrors();

    expect(ProductionBatch::query()->orderBy('number')->get()->pluck('label')->all())->toBe(['B-10000', 'B-10001']);

    $other = CurrentWorkshop::run(Workshop::factory()->create(), fn () => ProductionBatch::factory()->create());
    expect($other->label)->toBe('B-0001');
});

it('serves the window\'s grid with the shelf and "Can produce now", and the materials table (AC-2)', function () {
    actingAs($this->user)->getJson(route('progress.products.line', $this->jacket))
        ->assertOk()
        ->assertJsonPath('product.name', 'Denim Jacket')
        ->assertJsonPath('can_produce', 112)
        ->assertJsonPath('bottleneck', 'Metal shank button 17mm')
        ->assertJsonPath('one_size', false);

    $rows = actingAs($this->user)->getJson(route('progress.products.materials', [$this->jacket, 'units' => previewGrid($this->jacket, ['Indigo' => ['M' => 6, 'L' => 10]])]))
        ->assertOk()
        ->assertJsonPath('units', 16)
        ->assertJsonPath('short_message', null)
        ->json('rows');

    expect(array_map(fn (array $row): array => [$row['material']['name'], $row['quantity']], $rows))->toBe([
        ['Denim 12oz Indigo', '31.6800'],
        ['Metal shank button 17mm', '128.0000'],
        ['Polyester thread', '2016.0000'],
        ['Woven brand label', '16.0000'],
    ]);
});

it('answers 404 for another workshop\'s or a deleted product\'s grid and materials', function () {
    $theirs = CurrentWorkshop::run(Workshop::factory()->create(), fn () => Product::factory()->create());

    actingAs($this->user)->getJson(route('progress.products.line', $theirs->id))->assertNotFound();
    actingAs($this->user)->getJson(route('progress.products.materials', [$theirs->id, 'units' => [1 => [1 => 1]]]))->assertNotFound();

    $this->jacket->delete();
    actingAs($this->user)->getJson(route('progress.products.line', $this->jacket->id))->assertNotFound();
});

/*
| INV-16-k, AC-6: the Denim Jacket of the card (Indigo and Black × S M L XL, stock: Indigo denim 250 m, Black denim 40 m, 900 buttons,
| 20,000 m thread, 400 labels). "Can produce now" is the guaranteed 16 (Black XL, the Black denim).
*/

it('starts a mixed grid: takes per colour and size, three batches In cutting, what each unit took, then 12 and Indigo 89 (INV-16 AC-6)', function () {
    $jacket = VariantJacket::create();
    $body = variantJacketBody($jacket, ['Indigo' => ['M' => 6, 'L' => 10], 'Black' => ['S' => 5]]);

    actingAs($this->user)->getJson(route('progress.products.line', $jacket->product))
        ->assertOk()->assertJsonPath('can_produce', 16)->assertJsonPath('bottleneck', 'Denim 12oz Black');

    actingAs($this->user)->post(route('progress.batches.store'), $body)->assertSessionHasNoErrors();

    // 6 × 1.98 + 10 × 2.2 = 33.88 Indigo denim; 5 × 1.76 = 8.8 Black denim; 21 units of the rest.
    expect(variantJacketStock($jacket))->toBe([
        'indigo_denim' => '216.1200', 'black_denim' => '31.2000', 'button' => '732.0000', 'thread' => '17354.0000', 'label' => '379.0000',
    ]);

    $batches = ProductionBatch::query()->where('product_id', $jacket->product->id)->orderBy('number')->get();
    expect($batches->map(fn (ProductionBatch $b): array => [$b->colour_id, $b->size_id, $b->units, $b->status])->all())->toBe([
        [$jacket->colours['Indigo'], $jacket->sizes['M'], 6, ProductionBatchStatus::InProduction],
        [$jacket->colours['Indigo'], $jacket->sizes['L'], 10, ProductionBatchStatus::InProduction],
        [$jacket->colours['Black'], $jacket->sizes['S'], 5, ProductionBatchStatus::InProduction],
    ])->and($batches->every(fn (ProductionBatch $b): bool => $b->phase_id === null))->toBeTrue()
        ->and(StockMovement::query()->where('type', StockMovementType::Cutting)->where('units', 21)->count())->toBe(5);

    // The cut remembers what each unit took per colour and size, and nothing per material (ADR-0027 section 7).
    $cut = ProductionCut::query()->where('product_id', $jacket->product->id)->sole();
    $ids = array_map(fn (Material $material): int => $material->id, $jacket->materials);
    $variants = $cut->variants()->orderBy('id')->with('materials')->get();

    expect($cut->units)->toBe(21)
        ->and($cut->materials()->get()->mapWithKeys(fn (ProductionCutMaterial $m): array => [$m->material_id => [$m->quantity_per_unit, $m->quantity_taken]])->all())->toEqual([
            $ids['indigo_denim'] => [null, '33.8800'], $ids['black_denim'] => [null, '8.8000'], $ids['button'] => [null, '168.0000'],
            $ids['thread'] => [null, '2646.0000'], $ids['label'] => [null, '21.0000'],
        ])
        ->and($variants->map(fn ($v): array => [$v->colour_id, $v->size_id, $v->units, $v->units_returned])->all())->toBe([
            [$jacket->colours['Indigo'], $jacket->sizes['M'], 6, 0], [$jacket->colours['Indigo'], $jacket->sizes['L'], 10, 0], [$jacket->colours['Black'], $jacket->sizes['S'], 5, 0],
        ])
        ->and($variants->map(fn ($v): array => $v->materials->mapWithKeys(fn ($m): array => [$m->material_id => $m->quantity_per_unit])->all())->all())->toEqual([
            [$ids['indigo_denim'] => '1.98000000', $ids['button'] => '8.00000000', $ids['thread'] => '126.00000000', $ids['label'] => '1.00000000'],
            [$ids['indigo_denim'] => '2.20000000', $ids['button'] => '8.00000000', $ids['thread'] => '126.00000000', $ids['label'] => '1.00000000'],
            [$ids['black_denim'] => '1.76000000', $ids['button'] => '8.00000000', $ids['thread'] => '126.00000000', $ids['label'] => '1.00000000'],
        ]);

    // "Can produce now" goes from 16 to 12 (Black: 31.2 / 2.42) and Indigo's guaranteed from 103 to 89 (216.12 / 2.42).
    $costing = app(ProductCostingService::class)->cost($jacket->fresh());
    $indigo = collect($costing->variants->colours)->firstWhere('colourId', $jacket->colours['Indigo']);
    expect([$costing->result->capacity->maxProducible, $indigo->guaranteed])->toBe([12, 89]);
    actingAs($this->user)->getJson(route('progress.products.line', $jacket->product))->assertJsonPath('can_produce', 12);
});

it('refuses a short mixed grid by naming the short materials in composition order, and writes nothing', function (array $stock, string $message) {
    $jacket = VariantJacket::create();

    foreach ($stock as $key => $quantity) {
        $jacket->materials[$key]->update(['quantity_on_hand' => $quantity]);
    }

    $before = variantJacketStock($jacket);

    // Black S 10 · Black XL 10 · Indigo M 10 needs 41.8 m of Black denim against 40 (and 240 buttons).
    actingAs($this->user)
        ->post(route('progress.batches.store'), variantJacketBody($jacket, ['Black' => ['S' => 10, 'XL' => 10], 'Indigo' => ['M' => 10]]))
        ->assertSessionHasErrors(['units' => $message]);

    expect(variantJacketStock($jacket))->toBe($before)
        ->and([ProductionBatch::query()->count(), ProductionCut::query()->count(), ProductionCutVariant::query()->count()])->toBe([0, 0, 0])
        ->and(StockMovement::query()->where('type', StockMovementType::Cutting)->count())->toBe(0);
})->with([
    'the Black denim' => [[], 'Short on Denim 12oz Black. Restock it first, or lower the units.'],
    'the Black denim and the button' => [['button' => '100.0000'], 'Short on Denim 12oz Black and Metal shank button 17mm. Restock them first, or lower the units.'],
]);

it('checks the exact total: Black S 10 · Black XL 9 (39.38 m) starts, and one colour and size keeps "at most"', function () {
    $jacket = VariantJacket::create();

    actingAs($this->user)
        ->post(route('progress.batches.store'), variantJacketBody($jacket, ['Black' => ['XL' => 17]]))
        ->assertSessionHasErrors(['units' => 'Not enough stock to cut 17 units. You can cut at most 16 now: Denim 12oz Black runs short. Restock it first, or cut 16 or fewer.']);
    expect(ProductionCut::query()->count())->toBe(0);

    actingAs($this->user)
        ->post(route('progress.batches.store'), variantJacketBody($jacket, ['Black' => ['S' => 10, 'XL' => 9]]))
        ->assertSessionHasNoErrors();

    expect($jacket->materials['black_denim']->fresh()->quantity_on_hand)->toBe('0.6200')
        ->and(ProductionBatch::query()->count())->toBe(2);
});

it('previews a grid: the takes, "Short by" 1.8000 on the Black denim, no "at most" and the mixed sentence', function () {
    $jacket = VariantJacket::create();
    $grid = [
        $jacket->colours['Black'] => [$jacket->sizes['S'] => 10, $jacket->sizes['XL'] => 10],
        $jacket->colours['Indigo'] => [$jacket->sizes['M'] => 10],
    ];

    $preview = actingAs($this->user)->getJson(route('progress.products.materials', [$jacket->product, 'units' => $grid]))
        ->assertOk()
        ->assertJsonPath('units', 30)
        ->assertJsonPath('max_cuttable', null)
        ->assertJsonPath('short_message', 'Short on Denim 12oz Black. Restock it first, or lower the units.');

    expect(array_map(fn (array $row): array => [$row['material']['name'], $row['quantity'], $row['short_by'] ?? null], $preview->json('rows')))->toBe([
        ['Denim 12oz Black', '41.8000', '1.8000'],
        ['Denim 12oz Indigo', '19.8000', null],
        ['Metal shank button 17mm', '240.0000', null],
        ['Polyester thread', '3780.0000', null],
        ['Woven brand label', '30.0000', null],
    ]);
});

it('refuses a preview query that is not a grid of whole units the product has', function (Closure $arrange, string $message) {
    $jacket = VariantJacket::create();
    [$units, $key] = $arrange($jacket);

    actingAs($this->user)->getJson(route('progress.products.materials', [$jacket->product, ...($units === null ? [] : ['units' => $units])]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$key => $message]);
})->with([
    'missing units' => [fn () => [null, 'units'], 'Enter a whole number from 0 to 1,000,000.'],
    'a field that is not an integer' => [fn (VariantJacket $j) => [[$j->colours['Black'] => [$j->sizes['S'] => '2.5']], "units.{$j->colours['Black']}.{$j->sizes['S']}"], 'Enter a whole number from 0 to 1,000,000.'],
    'a negative field' => [fn (VariantJacket $j) => [[$j->colours['Black'] => [$j->sizes['S'] => '-1']], "units.{$j->colours['Black']}.{$j->sizes['S']}"], 'Enter a whole number from 0 to 1,000,000.'],
    'all zero' => [fn (VariantJacket $j) => [[$j->colours['Black'] => [$j->sizes['S'] => 0]], 'units'], 'Enter a whole number from 0 to 1,000,000.'],
    'a colour the product does not have' => [fn (VariantJacket $j) => [[$j->colours['Light wash'] => [$j->sizes['S'] => 1]], 'units'], 'The colours or sizes of Denim Jacket have changed. Enter the units again.'],
    'a size the product does not have' => [fn (VariantJacket $j) => [[$j->colours['Black'] => [Size::query()->where('label', 'XXL')->value('id') => 1]], 'units'], 'The colours or sizes of Denim Jacket have changed. Enter the units again.'],
]);

it('sends a guest to the login page', function () {
    $this->post(route('progress.batches.store'))->assertRedirect(route('login'));
    expect(User::query()->count())->toBeGreaterThan(0);
});
