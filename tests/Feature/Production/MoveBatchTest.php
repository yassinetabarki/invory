<?php

use App\Enums\ProductionBatchMoveKind;
use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\ProductionBatch;
use App\Models\ProductionPhase;
use App\Models\ProductRestock;
use App\Models\StockMovement;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Moving a batch, and splitting it (INV-5-d, AC-5)
|--------------------------------------------------------------------------
|
| `POST progress/batches/{batch}/moves` moves units of a batch one phase on or back. Moving part of it splits it: the units moved
| become a new batch with the next number. Moves never touch materials or the shelf. The card's seven batches, on 30 Oct 2026.
*/

beforeEach(function () {
    $example = progressExample($this);
    $this->user = $example['user'];
    $this->jacket = $example['jacket'];
    $this->sewing = $example['sewing'];
    $this->finishing = $example['finishing'];
    $this->batches = $example['batches'];
});

/** POST a move of `$units` units of a batch from one phase to another ('cutting' or a phase). */
function moveBatch(object $test, ProductionBatch $batch, string|ProductionPhase $from, string|ProductionPhase $to, int|string $units)
{
    return actingAs($test->user)->post(route('progress.batches.moves.store', $batch), [
        'from_phase' => $from instanceof ProductionPhase ? $from->id : $from,
        'to_phase' => $to instanceof ProductionPhase ? $to->id : $to,
        'units' => $units,
    ]);
}

/** What moves must leave alone: the materials, the shelf and the restocks. */
function untouchedByMoves(): array
{
    return [
        Material::query()->orderBy('id')->pluck('quantity_on_hand')->all(),
        ProductRestock::query()->count(),
        StockMovement::query()->where('type', StockMovementType::Cutting)->count(),
    ];
}

it('moves a whole batch to the next phase, keeping its number, since today (AC-5)', function () {
    $before = untouchedByMoves();
    $shelf = shelfByColour($this->jacket);

    moveBatch($this, $this->batches['B-0149'], $this->sewing, $this->finishing, 6)
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '6 units of Denim Jacket · Indigo M moved to Finishing.']);

    $b149 = $this->batches['B-0149']->fresh();
    expect($b149->phase_id)->toBe($this->finishing->id)
        ->and($b149->units)->toBe(6)
        ->and($b149->phase_since->toDateString())->toBe('2026-10-30')
        ->and(ProductionBatch::query()->count())->toBe(7)
        ->and(untouchedByMoves())->toBe($before)
        ->and(shelfByColour($this->jacket))->toBe($shelf);

    $entry = $b149->moves()->sole();
    expect([$entry->kind, $entry->units, $entry->from_label, $entry->to_label, $entry->user_id])
        ->toBe([ProductionBatchMoveKind::Forward, 6, 'Sewing', 'Finishing', $this->user->id]);
});

it('splits a batch when part of it moves: the units moved get the next number, the rest keep theirs (AC-5)', function () {
    moveBatch($this, $this->batches['B-0149'], $this->sewing, $this->finishing, 4)
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => '4 units of Denim Jacket · Indigo M moved to Finishing. Batch B-0149 was split: the units moved are on B-0155.',
            // INV-15-b (AC-2): the new batch's ticket, printed in a new tab.
            'action' => [
                'label' => 'Print ticket B-0155',
                'href' => route('progress.tickets', ['batches' => [ProductionBatch::query()->where('number', 155)->value('id')]], false),
                'new_tab' => true,
            ],
        ]);

    $b149 = $this->batches['B-0149']->fresh();
    $split = ProductionBatch::query()->where('split_from_id', $b149->id)->sole();

    expect([$b149->units, $b149->phase_id, $b149->phase_since->toDateString()])->toBe([2, $this->sewing->id, '2026-10-28'])
        ->and([$split->label, $split->units, $split->phase_id, $split->phase_since->toDateString()])->toBe(['B-0155', 4, $this->finishing->id, '2026-10-30'])
        ->and([$split->product_id, $split->colour_id, $split->size_id, $split->production_cut_id, $split->started_on->toDateString()])
        ->toBe([$b149->product_id, $b149->colour_id, $b149->size_id, $b149->production_cut_id, $b149->started_on->toDateString()]);
});

it('moves back one phase, and back to In cutting (AC-5)', function () {
    moveBatch($this, $this->batches['B-0148'], $this->finishing, $this->sewing, 4)
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '4 units of Denim Jacket · Indigo M moved to Sewing.']);
    moveBatch($this, $this->batches['B-0147'], $this->sewing, 'cutting', 1)
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', '1 unit of Denim Jacket · Indigo S moved to In cutting. Batch B-0147 was split: the units moved are on B-0155.');

    expect($this->batches['B-0148']->fresh()->phase_id)->toBe($this->sewing->id)
        ->and(ProductionBatch::query()->where('number', 155)->sole()->phase_id)->toBeNull()
        ->and($this->batches['B-0148']->fresh()->moves()->sole()->kind)->toBe(ProductionBatchMoveKind::Back);
});

it('refuses with the card\'s sentence and moves nothing', function (int|string $units, string $message) {
    moveBatch($this, $this->batches['B-0149'], $this->sewing, $this->finishing, $units)->assertSessionHasErrors(['units' => $message]);

    expect($this->batches['B-0149']->fresh()->only(['units', 'phase_id']))->toBe(['units' => 6, 'phase_id' => $this->sewing->id]);
})->with([
    'more than it holds' => [7, 'You can move at most 6 units of this batch.'],
    'zero' => [0, 'Enter at least one unit to move.'],
    'empty' => ['', 'Enter at least one unit to move.'],
    'not whole' => ['2.5', 'Enter a whole number of units.'],
]);

it('refuses a stale page: the batch moved, or the phases changed, since it was opened', function (Closure $meanwhile, Closure $move) {
    $meanwhile($this);

    $move($this)->assertSessionHasErrors(['moved' => 'These units have moved on since you opened this page.']);
})->with([
    'the batch moved on' => [
        fn ($test) => $test->batches['B-0149']->update(['phase_id' => $test->finishing->id]),
        fn ($test) => moveBatch($test, $test->batches['B-0149'], $test->sewing, $test->finishing, 6),
    ],
    'the batch was put in stock' => [
        fn ($test) => $test->batches['B-0148']->update(['status' => 'in_stock', 'phase_id' => null]),
        fn ($test) => moveBatch($test, $test->batches['B-0148'], $test->finishing, $test->sewing, 4),
    ],
    'a phase was inserted between' => [
        function ($test) {
            $test->finishing->update(['position' => 3]);
            ProductionPhase::factory()->create(['name' => 'Quality check', 'position' => 2]);
        },
        fn ($test) => moveBatch($test, $test->batches['B-0149'], $test->sewing, $test->finishing, 6),
    ],
    'two phases skipped' => [
        fn ($test) => null,
        fn ($test) => moveBatch($test, $test->batches['B-0150'], 'cutting', $test->finishing, 4),
    ],
    'the next phase was removed' => [
        function ($test) {
            $check = ProductionPhase::factory()->create(['name' => 'Quality check', 'position' => 3]);
            $test->checkId = $check->id;
            $check->delete();
        },
        fn ($test) => actingAs($test->user)->post(route('progress.batches.moves.store', $test->batches['B-0148']), [
            'from_phase' => $test->finishing->id, 'to_phase' => $test->checkId, 'units' => 4,
        ]),
    ],
]);

it('answers 404 for another workshop\'s batch', function () {
    $theirs = CurrentWorkshop::run(Workshop::factory()->create(), fn () => ProductionBatch::factory()->create());

    actingAs($this->user)->post(route('progress.batches.moves.store', $theirs->id), ['from_phase' => 'cutting', 'to_phase' => 'cutting', 'units' => 1])
        ->assertNotFound();
});
