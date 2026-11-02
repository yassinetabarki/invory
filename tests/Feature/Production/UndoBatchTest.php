<?php

use App\Enums\ProductionBatchMoveKind;
use App\Enums\ProductionBatchStatus;
use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionCut;
use App\Models\ProductionPhase;
use App\Models\StockMovement;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Undo batch (INV-5-e, AC-6)
|--------------------------------------------------------------------------
|
| `POST progress/batches/{batch}/undo`, only while the batch is In cutting, gives back what its Start batch took for its units, from
| the amounts remembered then. The batches are started through the real route, on the demo stock (Denim Jacket: 1.98 m denim, 8
| buttons, 126 m thread and 1 label a jacket).
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    ['jacket' => $this->jacket] = restockCatalogue();
});

/** Starts Indigo units of the Denim Jacket (size label → units) and returns the batches, in number order. */
function startJackets(object $test, array $bySize): array
{
    actingAs($test->user)->post(route('progress.batches.store'), [
        'product_id' => $test->jacket->id,
        'units' => restockLine($test->jacket, ['Indigo' => $bySize])['units'],
    ])->assertSessionHasNoErrors();

    return ProductionBatch::query()->orderBy('number')->get()->all();
}

/** The Denim Jacket's four materials, by SKU → stock now. */
function undoJacketMaterials(): array
{
    return Material::query()->whereIn('sku', ['MAT-DEN-012', 'MAT-BTN-017', 'MAT-THR-PES', 'MAT-LBL-WOV'])->orderBy('sku')->pluck('quantity_on_hand', 'sku')->all();
}

it('lists what comes back and gives it back: 4 jackets return 7.92 m, 32 pc, 504 m and 4 pc (AC-6)', function () {
    $before = undoJacketMaterials();
    [$batch] = startJackets($this, ['M' => 4]);

    $rows = actingAs($this->user)->getJson(route('progress.batches.undo-preview', $batch))
        ->assertOk()
        ->assertJsonPath('nothing_taken', false)
        ->json('rows');
    expect(array_map(fn (array $row): array => [$row['material']['name'], $row['quantity']], $rows))->toBe([
        ['Denim 12oz Indigo', '7.9200'],
        ['Metal shank button 17mm', '32.0000'],
        ['Polyester thread', '504.0000'],
        ['Woven brand label', '4.0000'],
    ]);

    actingAs($this->user)->post(route('progress.batches.undo', $batch))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Batch B-0001 undone. Materials returned to stock.']);

    $batch->refresh();
    expect(undoJacketMaterials())->toBe($before)
        ->and([$batch->status, $batch->undone_by])->toBe([ProductionBatchStatus::Undone, $this->user->id])
        ->and($batch->undone_at)->not->toBeNull()
        ->and($batch->moves()->latest('id')->first()->kind)->toBe(ProductionBatchMoveKind::Undo)
        ->and(StockMovement::query()->where('type', StockMovementType::CuttingReturn)->count())->toBe(4)
        ->and(ProductionCut::query()->sole()->units_returned)->toBe(4);
});

it('gives back exactly what one start took when its batches are undone one by one, also after a split', function () {
    $before = undoJacketMaterials();
    // 3 denim per 7 jackets don't divide evenly: the rounding must still add up.
    Product::query()->whereKey($this->jacket->id)->first()->components()->where('material_id', demoMaterial('MAT-DEN-012')->id)->update(['quantity' => '1.3333']);
    [$s, $m] = startJackets($this, ['S' => 3, 'M' => 4]);

    // Split M: 1 unit moves to Sewing and back to In cutting, as a batch of its own.
    $sewing = ProductionPhase::factory()->create(['name' => 'Sewing', 'position' => 1]);
    actingAs($this->user)->post(route('progress.batches.moves.store', $m), ['from_phase' => 'cutting', 'to_phase' => $sewing->id, 'units' => 1])->assertSessionHasNoErrors();
    $split = ProductionBatch::query()->where('split_from_id', $m->id)->sole();
    actingAs($this->user)->post(route('progress.batches.moves.store', $split), ['from_phase' => $sewing->id, 'to_phase' => 'cutting', 'units' => 1])->assertSessionHasNoErrors();

    foreach ([$m, $split, $s] as $batch) {
        actingAs($this->user)->post(route('progress.batches.undo', $batch))->assertSessionHasNoErrors();
    }

    expect(undoJacketMaterials())->toBe($before)
        ->and(ProductionCut::query()->sole()->units_returned)->toBe(7);
});

it('gives nothing back for a material deleted since the start, and the rest as usual', function () {
    [$batch] = startJackets($this, ['M' => 4]);
    $label = demoMaterial('MAT-LBL-WOV');
    $label->delete();

    actingAs($this->user)->post(route('progress.batches.undo', $batch))->assertSessionHasNoErrors();

    expect(Material::withTrashed()->findOrFail($label->id)->quantity_on_hand)->toBe($label->quantity_on_hand)
        ->and(StockMovement::query()->where('type', StockMovementType::CuttingReturn)->count())->toBe(3);
});

it('refuses a batch that is no longer In cutting, and changes nothing', function (Closure $meanwhile) {
    [$batch] = startJackets($this, ['M' => 4]);
    $meanwhile($batch);
    $stock = undoJacketMaterials();

    actingAs($this->user)->post(route('progress.batches.undo', $batch))
        ->assertSessionHasErrors(['undo' => "B-0001 has moved on since you opened this page, so it can't be undone. Move it back to In cutting first."]);

    expect(undoJacketMaterials())->toBe($stock);
})->with([
    'moved to Sewing' => [fn (ProductionBatch $batch) => $batch->update(['phase_id' => ProductionPhase::factory()->create(['name' => 'Sewing'])->id])],
    'already undone' => [fn (ProductionBatch $batch) => $batch->update(['status' => ProductionBatchStatus::Undone])],
    'put in stock' => [fn (ProductionBatch $batch) => $batch->update(['status' => ProductionBatchStatus::InStock])],
]);

it('answers 404 for another workshop\'s batch', function () {
    $theirs = CurrentWorkshop::run(Workshop::factory()->create(), fn () => ProductionBatch::factory()->create());

    actingAs($this->user)->post(route('progress.batches.undo', $theirs->id))->assertNotFound();
    actingAs($this->user)->getJson(route('progress.batches.undo-preview', $theirs->id))->assertNotFound();
});
