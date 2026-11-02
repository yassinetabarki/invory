<?php

use App\Enums\ProductionBatchMoveKind;
use App\Enums\ProductionBatchStatus;
use App\Models\Material;
use App\Models\ProductionBatch;
use App\Models\ProductRestock;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Put in stock (INV-5-e, AC-6)
|--------------------------------------------------------------------------
|
| `POST progress/batches/{batch}/stock` adds units of a batch at the end of its path to the shelf, as a restock "From production"
| (Reference = the batch number). The card's seven batches, on 30 Oct 2026: B-0148 holds 4 Denim Jacket Indigo M in Finishing, and
| the jacket's Indigo M shelf is 0.
*/

beforeEach(function () {
    $example = progressExample($this);
    $this->user = $example['user'];
    $this->jacket = $example['jacket'];
    $this->sewing = $example['sewing'];
    $this->finishing = $example['finishing'];
    $this->batches = $example['batches'];
});

function putInStock(object $test, ProductionBatch $batch, mixed $from, int|string $units)
{
    return actingAs($test->user)->post(route('progress.batches.stock', $batch), ['from_phase' => $from, 'units' => $units]);
}

it('puts a whole batch in stock as a restock "From production"; it leaves Progress and the materials stay (AC-6)', function () {
    $materials = Material::query()->orderBy('id')->pluck('quantity_on_hand')->all();
    $b148 = $this->batches['B-0148'];

    $response = putInStock($this, $b148, $this->finishing->id, 4)->assertSessionHasNoErrors();

    $restock = ProductRestock::query()->sole();
    $response->assertInertiaFlash('toast', [
        'type' => 'success',
        'message' => '4 units of Denim Jacket · Indigo M put in stock.',
        'action' => ['label' => 'See the restock', 'href' => route('restocks.index', ['restock' => $restock->id], false)],
    ]);

    expect(shelfByColour($this->jacket)['Indigo'])->toBe(['S' => 2, 'M' => 4, 'L' => 3, 'XL' => 1])
        ->and([$restock->restocked_on->toDateString(), $restock->reference, $restock->note, $restock->units, $restock->user_id])
        ->toBe(['2026-10-30', 'B-0148', 'From production', 4, $this->user->id])
        ->and($restock->lines()->count())->toBe(1)
        ->and(Material::query()->orderBy('id')->pluck('quantity_on_hand')->all())->toBe($materials);

    $b148->refresh();
    expect([$b148->status, $b148->phase_id, $b148->product_restock_id, $b148->units])->toBe([ProductionBatchStatus::InStock, null, $restock->id, 4])
        ->and($b148->finished_at)->not->toBeNull()
        ->and($b148->moves()->sole()->only(['kind', 'from_label', 'to_label']))
        ->toBe(['kind' => ProductionBatchMoveKind::Stock, 'from_label' => 'Finishing', 'to_label' => 'Put in stock']);
});

it('puts part of a batch in stock: the rest keeps its number and its phase (AC-6)', function () {
    putInStock($this, $this->batches['B-0148'], $this->finishing->id, 3)->assertSessionHasNoErrors();

    expect(shelfByColour($this->jacket)['Indigo']['M'])->toBe(3)
        ->and($this->batches['B-0148']->fresh()->only(['units', 'phase_id', 'number']))
        ->toBe(['units' => 1, 'phase_id' => $this->finishing->id, 'number' => 148]);
});

it('puts a batch in stock from In cutting when the workshop has no phase of its own', function () {
    ProductionBatch::query()->whereNotNull('phase_id')->update(['phase_id' => null]);
    $this->sewing->delete();
    $this->finishing->delete();

    putInStock($this, $this->batches['B-0151'], 'cutting', 2)->assertSessionHasNoErrors();

    expect(shelfByColour($this->jacket)['Light wash'])->toBe(['L' => 2]);
});

it('leaves the batch put in stock, and its materials used, when that restock is undone in Restocks', function () {
    putInStock($this, $this->batches['B-0148'], $this->finishing->id, 4)->assertSessionHasNoErrors();

    actingAs($this->user)->post(route('restocks.undo', ProductRestock::query()->sole()))->assertSessionHasNoErrors();

    expect(shelfByColour($this->jacket)['Indigo'])->toBe(['S' => 2, 'L' => 3, 'XL' => 1])
        ->and($this->batches['B-0148']->fresh()->status)->toBe(ProductionBatchStatus::InStock);
});

it('refuses with the card\'s sentence and changes nothing', function (Closure $arrange, Closure $post, string $field, string $message) {
    $arrange($this);

    $post($this)->assertSessionHasErrors([$field => $message]);

    expect(ProductRestock::query()->count())->toBe(0);
})->with([
    'more than it holds' => [fn () => null, fn ($t) => putInStock($t, $t->batches['B-0148'], $t->finishing->id, 5), 'units', 'You can put at most 4 units of this batch in stock.'],
    'zero' => [fn () => null, fn ($t) => putInStock($t, $t->batches['B-0148'], $t->finishing->id, 0), 'units', 'Enter at least one unit to put in stock.'],
    'not whole' => [fn () => null, fn ($t) => putInStock($t, $t->batches['B-0148'], $t->finishing->id, '1.5'), 'units', 'Enter a whole number of units.'],
    'not the last phase' => [fn () => null, fn ($t) => putInStock($t, $t->batches['B-0149'], $t->sewing->id, 6), 'moved', 'These units have moved on since you opened this page.'],
    'moved meanwhile' => [
        fn ($t) => $t->batches['B-0148']->update(['phase_id' => $t->sewing->id]),
        fn ($t) => putInStock($t, $t->batches['B-0148'], $t->finishing->id, 4), 'moved', 'These units have moved on since you opened this page.',
    ],
    'a deleted product' => [
        fn ($t) => $t->jacket->delete(),
        fn ($t) => putInStock($t, $t->batches['B-0148'], $t->finishing->id, 4),
        'units', "Denim Jacket was deleted, so it can't be put in stock. Move the batch back to In cutting and undo it to return its materials.",
    ],
    'over 1,000,000 on the shelf' => [
        fn ($t) => setShelf($t->jacket, ['Indigo' => ['M' => 999998]]),
        fn ($t) => putInStock($t, $t->batches['B-0148'], $t->finishing->id, 4), 'units', "Indigo M can't go over 1,000,000 units on the shelf.",
    ],
]);

it('accepts the shelf up to exactly 1,000,000', function () {
    setShelf($this->jacket, ['Indigo' => ['M' => 999996]]);

    putInStock($this, $this->batches['B-0148'], $this->finishing->id, 4)->assertSessionHasNoErrors();

    expect(shelfByColour($this->jacket)['Indigo']['M'])->toBe(1000000);
});

it('answers 404 for another workshop\'s batch', function () {
    $theirs = CurrentWorkshop::run(Workshop::factory()->create(), fn () => ProductionBatch::factory()->create());

    actingAs($this->user)->post(route('progress.batches.stock', $theirs->id), ['from_phase' => 'cutting', 'units' => 1])->assertNotFound();
});
